import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { formatCollectionAmount, type CollectionInstallment } from '@/types/collection';
import { useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface Props {
    saleId: number;
    saleDate: string;
    installment: CollectionInstallment;
    paymentMethods: Record<string, string>;
    paymentKey: string;
    today: string;
    onClose: () => void;
}

export default function CollectionPaymentDialog({ saleId, saleDate, installment, paymentMethods, paymentKey, today, onClose }: Props) {
    const { data, setData, post, errors, processing } = useForm({
        monto: installment.monto_pendiente,
        forma_pago: 'efectivo',
        referencia: '',
        fecha: today,
        observaciones: '',
        idempotency_key: paymentKey,
    });
    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('collections.payments.store', { sale: saleId, installment: installment.id }), { preserveScroll: true, onSuccess: onClose });
    };

    return (
        <Dialog
            open
            onOpenChange={(open) => {
                if (!open && !processing) onClose();
            }}
        >
            <DialogContent className="max-h-[90vh] overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Registrar pago · Cuota {installment.numero_cuota}</DialogTitle>
                    <DialogDescription>
                        Saldo pendiente: {formatCollectionAmount(installment.monto_pendiente)}. Puedes registrar un abono parcial.
                    </DialogDescription>
                </DialogHeader>
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="payment-amount">Monto recibido (S/)</Label>
                        <Input
                            id="payment-amount"
                            type="number"
                            inputMode="decimal"
                            min="0.01"
                            step="0.01"
                            max={installment.monto_pendiente}
                            required
                            autoFocus
                            value={data.monto}
                            onChange={(event) => setData('monto', event.target.value)}
                        />
                        <InputError message={errors.monto} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="payment-method">Método de pago</Label>
                        <Select value={data.forma_pago} onValueChange={(value) => setData('forma_pago', value)}>
                            <SelectTrigger id="payment-method">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(paymentMethods).map(([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.forma_pago} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="payment-reference">Número de operación / Referencia</Label>
                        <Input
                            id="payment-reference"
                            maxLength={255}
                            value={data.referencia}
                            onChange={(event) => setData('referencia', event.target.value)}
                        />
                        <InputError message={errors.referencia} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="payment-date">Fecha del pago</Label>
                        <Input
                            id="payment-date"
                            type="date"
                            min={saleDate.slice(0, 10)}
                            max={today}
                            required
                            value={data.fecha}
                            onChange={(event) => setData('fecha', event.target.value)}
                        />
                        <InputError message={errors.fecha} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="payment-notes">Observación (opcional)</Label>
                        <Textarea
                            id="payment-notes"
                            rows={3}
                            maxLength={5000}
                            value={data.observaciones}
                            onChange={(event) => setData('observaciones', event.target.value)}
                        />
                        <InputError message={errors.observaciones} />
                    </div>
                    <InputError message={errors.idempotency_key} />
                    <DialogFooter className="gap-2">
                        <Button type="button" variant="outline" disabled={processing} onClick={onClose}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Registrando...' : 'Registrar pago'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
