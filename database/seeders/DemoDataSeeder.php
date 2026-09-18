<?php

namespace Database\Seeders;

use App\Models\CatalogItem;
use App\Models\Client;
use App\Models\ClientSite;
use App\Models\Equipment;
use App\Models\InventoryReception;
use App\Models\InventoryStock;
use App\Models\InventoryUnit;
use App\Models\Quote;
use App\Models\Sale;
use App\Models\SaleInstallment;
use App\Models\SaleItem;
use App\Models\SalePayment;
use App\Models\User;
use App\Models\Vehicle;
use Illuminate\Database\Seeder;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::query()->where('email', 'test@example.com')->first() ?? User::factory()->create();

        $clients = Client::factory()
            ->count(10)
            ->create()
            ->each(function (Client $client): void {
                ClientSite::factory()->count(2)->for($client)->create();
                Vehicle::factory()->for($client)->create();
            });

        $serializedExtinguisher = CatalogItem::factory()->create([
            'categoria' => 'Extintores',
            'nombre' => 'Extintor PQS ABC 6kg serializado',
            'precio' => 185.00,
            'controla_stock' => true,
            'control_serializado' => true,
            'genera_barcode' => true,
        ]);

        $standardProduct = CatalogItem::factory()->create([
            'nombre' => 'Extintor PQS ABC 10lb',
            'precio' => 150.00,
            'controla_stock' => true,
            'control_serializado' => false,
        ]);

        $service = CatalogItem::factory()->service()->create(['precio' => 45.00]);
        $sparePart = CatalogItem::factory()->sparePart()->create(['precio' => 12.00]);

        InventoryReception::receive([
            'catalog_item_id' => $serializedExtinguisher->id,
            'proveedor' => 'Demo Extintores SAC',
            'documento_referencia' => 'DEMO-SER-001',
            'fecha' => now()->toDateString(),
            'cantidad' => 6,
            'cantidad_conforme' => 6,
            'cantidad_observada' => 0,
            'units' => collect(range(1, 6))->map(fn (int $number): array => [
                'serie' => sprintf('DEMO-SER-%03d', $number),
                'marca' => 'Amerex',
                'capacidad' => '6 kg',
                'anio' => 2026,
                'barcode' => sprintf('DEMO-EXT-%03d', $number),
                'conforme' => true,
            ])->all(),
        ], $user);

        InventoryReception::receive([
            'catalog_item_id' => $standardProduct->id,
            'proveedor' => 'Demo Extintores SAC',
            'documento_referencia' => 'DEMO-STD-001',
            'fecha' => now()->toDateString(),
            'cantidad' => 12,
            'cantidad_conforme' => 12,
            'cantidad_observada' => 0,
        ], $user);

        collect(['borrador', 'emitida', 'enviada', 'aceptada', 'convertida'])->each(function (string $estado, int $index) use ($clients, $user, $standardProduct, $service, $sparePart): void {
            $lines = [
                ['item' => $standardProduct, 'cantidad' => 2],
                ['item' => $service, 'cantidad' => 1],
                ['item' => $sparePart, 'cantidad' => 3],
            ];

            $subtotal = 0;
            foreach ($lines as $line) {
                $subtotal += round($line['cantidad'] * (float) $line['item']->precio, 2);
            }
            $igv = round($subtotal * 0.18, 2);
            $total = round($subtotal + $igv, 2);

            $quote = Quote::factory()->create([
                'client_id' => $clients[$index]->id,
                'client_site_id' => $clients[$index]->sites()->inRandomOrder()->value('id'),
                'vehicle_id' => $clients[$index]->vehicles()->inRandomOrder()->value('id'),
                'vendedor_user_id' => $user->id,
                'estado' => $estado,
                'subtotal' => $subtotal,
                'igv' => $igv,
                'total' => $total,
            ]);

            foreach ($lines as $line) {
                $lineSubtotal = round($line['cantidad'] * (float) $line['item']->precio, 2);
                $quote->items()->create([
                    'catalog_item_id' => $line['item']->id,
                    'cantidad' => $line['cantidad'],
                    'precio_unitario' => $line['item']->precio,
                    'descuento' => 0,
                    'subtotal' => $lineSubtotal,
                ]);
            }
        });

        $this->createDemoSale($clients[0], $user, $standardProduct, 2, 'contado');
        $this->createDemoSale($clients[1], $user, $standardProduct, 1, 'credito');
        $this->createDemoSale($clients[2], $user, $service, 1, 'contado');
        $this->createDemoSale($clients[3], $user, $sparePart, 4, 'contado');
        $this->createDemoSale($clients[4], $user, $service, 2, 'credito');
        $this->createDemoSale($clients[5], $user, $standardProduct, 3, 'contado');
        $this->createDemoSale($clients[6], $user, $sparePart, 2, 'credito');
        $this->createSerializedDemoSale($clients[7], $user, $serializedExtinguisher);
    }

    private function createDemoSale(Client $client, User $user, CatalogItem $item, int $quantity, string $condition): Sale
    {
        $subtotal = round($quantity * (float) $item->precio, 2);
        $igv = round($subtotal * 0.18, 2);
        $total = round($subtotal + $igv, 2);

        $sale = Sale::factory()->create([
            'client_id' => $client->id,
            'client_site_id' => $client->sites()->inRandomOrder()->value('id'),
            'vehicle_id' => $client->vehicles()->inRandomOrder()->value('id'),
            'vendedor_user_id' => $user->id,
            'condicion_pago' => $condition,
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => $total,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'catalog_item_id' => $item->id,
            'cantidad' => $quantity,
            'precio_unitario' => $item->precio,
            'subtotal' => $subtotal,
        ]);

        if ($item->controla_stock) {
            InventoryStock::where('catalog_item_id', $item->id)->firstOrFail()->move([
                'tipo' => 'salida',
                'cantidad' => $quantity,
                'motivo' => 'venta demo',
                'referencia' => $sale->numero,
                'fecha' => $sale->fecha->toDateString(),
            ], $user);
        }

        if ($condition === 'contado') {
            SalePayment::factory()->create(['sale_id' => $sale->id, 'monto' => $total]);
        } else {
            SaleInstallment::factory()->create(['sale_id' => $sale->id, 'numero_cuota' => 1, 'monto' => $total / 2, 'monto_pendiente' => $total / 2]);
            SaleInstallment::factory()->create(['sale_id' => $sale->id, 'numero_cuota' => 2, 'monto' => $total / 2, 'monto_pendiente' => $total / 2, 'fecha_vencimiento' => now()->addDays(60)->toDateString()]);
        }

        return $sale;
    }

    private function createSerializedDemoSale(Client $client, User $user, CatalogItem $item): Sale
    {
        $unit = InventoryUnit::where('catalog_item_id', $item->id)->where('en_stock', true)->firstOrFail();
        $subtotal = (float) $item->precio;
        $igv = round($subtotal * 0.18, 2);
        $total = round($subtotal + $igv, 2);

        $sale = Sale::factory()->create([
            'client_id' => $client->id,
            'client_site_id' => $client->sites()->inRandomOrder()->value('id'),
            'vehicle_id' => $client->vehicles()->inRandomOrder()->value('id'),
            'vendedor_user_id' => $user->id,
            'condicion_pago' => 'contado',
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => $total,
        ]);

        SaleItem::factory()->create([
            'sale_id' => $sale->id,
            'catalog_item_id' => $item->id,
            'inventory_unit_id' => $unit->id,
            'cantidad' => 1,
            'precio_unitario' => $item->precio,
            'subtotal' => $subtotal,
        ]);
        SalePayment::factory()->create(['sale_id' => $sale->id, 'monto' => $total]);

        InventoryStock::where('catalog_item_id', $item->id)->firstOrFail()->move([
            'tipo' => 'salida',
            'cantidad' => 1,
            'motivo' => 'venta demo',
            'referencia' => $sale->numero,
            'fecha' => $sale->fecha->toDateString(),
            'unit_ids' => [$unit->id],
        ], $user);
        $unit->update(['estado' => 'vendido']);

        Equipment::create([
            'client_id' => $client->id,
            'client_site_id' => $sale->client_site_id,
            'vehicle_id' => $sale->vehicle_id,
            'origen' => 'vendido_bruce_fire',
            'tipo_equipo' => $item->nombre,
            'capacidad' => $unit->capacidad,
            'marca' => $unit->marca,
            'serie_fabricante' => $unit->serie,
            'anio_fabricacion' => (string) $unit->anio,
            'barcode' => $unit->barcode,
            'estado' => 'activo',
            'observaciones' => "Creado automaticamente por venta demo {$sale->numero}.",
        ]);

        return $sale;
    }
}
