<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexCollectionRequest;
use App\Http\Requests\StoreCollectionPaymentRequest;
use App\Models\Sale;
use App\Models\SaleInstallment;
use App\Models\SalePayment;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class CollectionController extends Controller
{
    public function index(IndexCollectionRequest $request): Response
    {
        $search = $request->string('search')->trim()->toString();
        $estado = $request->string('estado')->toString();
        $today = now()->toDateString();
        $outstanding = fn (Builder $query): Builder => $query->where('monto_pendiente', '>', 0);
        $overdue = fn (Builder $query): Builder => $query->where('monto_pendiente', '>', 0)->where('fecha_vencimiento', '<', $today);
        $paidInPart = fn (Builder $query): Builder => $query->whereColumn('monto_pendiente', '<', 'monto');

        $sales = $this->collectibleSales()
            ->select(['id', 'numero', 'client_id', 'fecha', 'total'])
            ->with('client:id,razon_social,numero_documento')
            ->whereHas('installments', $outstanding)
            ->withCount(['installments', 'installments as cuotas_pendientes' => $outstanding])
            ->withSum(['installments as saldo' => $outstanding], 'monto_pendiente')
            ->withMin(['installments as vencimiento' => $outstanding], 'fecha_vencimiento')
            ->withExists(['installments as vencida' => $overdue, 'installments as parcial' => $paidInPart])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('numero', 'like', "%{$search}%")
                        ->orWhereHas('client', fn (Builder $query) => $query
                            ->where('razon_social', 'like', "%{$search}%")
                            ->orWhere('numero_documento', 'like', "%{$search}%"));
                });
            })
            ->when($estado === 'vencida', fn (Builder $query) => $query->whereHas('installments', $overdue))
            ->when(in_array($estado, ['pendiente', 'parcial'], true), fn (Builder $query) => $query->whereDoesntHave('installments', $overdue))
            ->when($estado === 'parcial', fn (Builder $query) => $query->whereHas('installments', $paidInPart))
            ->when($estado === 'pendiente', fn (Builder $query) => $query->whereDoesntHave('installments', $paidInPart))
            ->orderBy('vencimiento')->orderBy('id')
            ->paginate(15)->withQueryString()
            ->through(function (Sale $sale): array {
                return [
                    'id' => $sale->id,
                    'numero' => $sale->numero,
                    'client' => $sale->client,
                    'total' => $sale->total,
                    'saldo' => $this->formatAmount($sale->saldo),
                    'vencimiento' => $sale->vencimiento ? substr($sale->vencimiento, 0, 10) : null,
                    'cuotas' => $sale->installments_count,
                    'cuotas_pendientes' => $sale->cuotas_pendientes,
                    'estado' => $sale->vencida ? 'vencida' : ($sale->parcial ? 'parcial' : 'pendiente'),
                ];
            });

        return Inertia::render('collections/index', [
            'sales' => $sales,
            'summary' => $this->summary(),
            'filters' => ['search' => $search, 'estado' => $estado],
            'canRegisterPayment' => $request->user()->can('collections.register_payment'),
        ]);
    }

    public function show(Request $request, Sale $sale): Response
    {
        $this->authorize('viewAny', SaleInstallment::class);
        $sale->load([
            'client:id,razon_social,numero_documento',
            'installments' => fn ($query) => $query->orderBy('numero_cuota')->orderBy('id'),
        ]);
        $installments = $sale->installments->map(fn (SaleInstallment $installment): array => [
            'id' => $installment->id,
            'numero_cuota' => $installment->numero_cuota,
            'monto' => $installment->monto,
            'monto_pendiente' => $installment->monto_pendiente,
            'fecha_vencimiento' => $installment->fecha_vencimiento->toDateString(),
            'estado' => $this->installmentStatus($installment),
        ]);
        $payments = $sale->payments()->orderByDesc('id')->paginate(15)->withQueryString();

        return Inertia::render('collections/show', [
            'sale' => $sale->only(['id', 'numero', 'fecha', 'total', 'condicion_pago', 'estado', 'client']),
            'installments' => $installments,
            'payments' => $payments,
            'balance' => $this->formatAmount($sale->installments->sum('monto_pendiente')),
            'canRegisterPayment' => $sale->condicion_pago === 'credito' && $sale->estado !== 'anulada'
                && $request->user()->can('collections.register_payment'),
            'paymentMethods' => StoreCollectionPaymentRequest::PAYMENT_METHODS,
            'paymentKey' => (string) Str::uuid(),
            'today' => now()->toDateString(),
            'openPayment' => $request->boolean('pagar'),
            'status' => $request->session()->get('status'),
        ]);
    }

    public function storePayment(StoreCollectionPaymentRequest $request, Sale $sale, SaleInstallment $installment): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($request, $sale, $installment, $data): void {
            $lockedSale = Sale::query()->lockForUpdate()->findOrFail($sale->id);
            $lockedInstallment = $lockedSale->installments()->lockForUpdate()->findOrFail($installment->id);

            if ($lockedSale->condicion_pago !== 'credito' || $lockedSale->estado === 'anulada') {
                throw ValidationException::withMessages(['monto' => 'Solo se pueden cobrar cuotas de ventas a crédito vigentes.']);
            }

            $amount = $this->cents((string) $data['monto']);
            $previousPayment = SalePayment::query()->where('idempotency_key', $data['idempotency_key'])->first();

            if ($previousPayment) {
                if ((int) $previousPayment->sale_installment_id !== $lockedInstallment->id
                    || $this->cents($previousPayment->monto) !== $amount
                    || $previousPayment->forma_pago !== $data['forma_pago']
                    || $previousPayment->fecha !== $data['fecha']
                    || $previousPayment->referencia !== ($data['referencia'] ?? null)
                    || $previousPayment->observaciones !== ($data['observaciones'] ?? null)) {
                    throw ValidationException::withMessages(['monto' => 'Esta solicitud ya registró otro pago. Recarga la página.']);
                }

                return;
            }

            $balance = $this->cents($lockedInstallment->monto_pendiente);

            if ($balance === 0 || $amount > $balance) {
                throw ValidationException::withMessages(['monto' => 'El pago no puede superar el saldo pendiente de la cuota.']);
            }

            $remaining = $balance - $amount;
            $lockedInstallment->update([
                'monto_pendiente' => sprintf('%d.%02d', intdiv($remaining, 100), $remaining % 100),
                'estado' => $remaining === 0 ? 'pagado' : 'pagado_parcial',
            ]);

            $payment = $lockedSale->payments()->make([
                'forma_pago' => $data['forma_pago'],
                'monto' => $data['monto'],
                'referencia' => $data['referencia'] ?? null,
            ]);
            $payment->sale_installment_id = $lockedInstallment->id;
            $payment->fecha = $data['fecha'];
            $payment->observaciones = $data['observaciones'] ?? null;
            $payment->user_id = $request->user()->id;
            $payment->idempotency_key = $data['idempotency_key'];
            $payment->save();
        }, attempts: 3);

        return to_route('collections.show', $sale)->with('status', 'Pago registrado correctamente.');
    }

    /** @return Builder<Sale> */
    private function collectibleSales(): Builder
    {
        return Sale::query()->where('condicion_pago', 'credito')->where('estado', '!=', 'anulada');
    }

    /** @return array{total_por_cobrar: string, vencido: string, vence_esta_semana: string, cobrado_este_mes: string} */
    private function summary(): array
    {
        $today = now()->toDateString();
        $pending = SaleInstallment::query()->where('monto_pendiente', '>', 0)
            ->whereIn('sale_id', $this->collectibleSales()->select('id'));
        $payments = SalePayment::query()->whereHas('sale', fn (Builder $query) => $query->where('estado', '!=', 'anulada'))
            ->where(function (Builder $query): void {
                $query->whereBetween('fecha', [now()->startOfMonth()->toDateString(), now()->toDateString()])
                    ->orWhere(function (Builder $query): void {
                        $query->whereNull('fecha')->whereBetween('created_at', [now()->startOfMonth(), now()]);
                    });
            });

        return [
            'total_por_cobrar' => $this->formatAmount((clone $pending)->sum('monto_pendiente')),
            'vencido' => $this->formatAmount((clone $pending)->whereDate('fecha_vencimiento', '<', $today)->sum('monto_pendiente')),
            'vence_esta_semana' => $this->formatAmount((clone $pending)
                ->whereDate('fecha_vencimiento', '>=', $today)
                ->whereDate('fecha_vencimiento', '<=', now()->endOfWeek(CarbonInterface::SUNDAY)->toDateString())
                ->sum('monto_pendiente')),
            'cobrado_este_mes' => $this->formatAmount($payments->sum('monto')),
        ];
    }

    private function installmentStatus(SaleInstallment $installment): string
    {
        $balance = $this->cents($installment->monto_pendiente);

        if ($balance === 0) {
            return 'pagada';
        }

        if ($installment->fecha_vencimiento->lt(now()->startOfDay())) {
            return 'vencida';
        }

        return $balance < $this->cents($installment->monto) ? 'parcial' : 'pendiente';
    }

    private function cents(string $amount): int
    {
        [$whole, $fraction] = array_pad(explode('.', $amount, 2), 2, '');

        return ((int) $whole * 100) + (int) str_pad($fraction, 2, '0');
    }

    private function formatAmount(int|float|string $amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }
}
