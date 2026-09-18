import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { type EquipmentClientOption } from '@/types/equipment';
import { useForm } from '@inertiajs/react';
import { FormEventHandler, ReactNode, useMemo, useState } from 'react';

interface TransferFormData {
    destino_client_id: string;
    destino_client_site_id: string;
    fecha: string;
    motivo: string;
    observacion: string;
    [key: string]: string;
}

export function EquipmentTransferDialog({
    equipmentId,
    clients,
    trigger,
}: {
    equipmentId: number;
    clients: EquipmentClientOption[];
    trigger: ReactNode;
}) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, errors, processing, reset } = useForm<TransferFormData>({
        destino_client_id: '',
        destino_client_site_id: '',
        fecha: new Date().toISOString().slice(0, 10),
        motivo: '',
        observacion: '',
    });

    const selectedClient = useMemo(
        () => clients.find((client) => String(client.id) === data.destino_client_id),
        [clients, data.destino_client_id],
    );

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('equipment.transfer', equipmentId), {
            onSuccess: () => {
                setOpen(false);
                reset();
            },
        });
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                setOpen(next);
                if (!next) {
                    reset();
                }
            }}
        >
            <DialogTrigger asChild>{trigger}</DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Transferir equipo</DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="destino_client_id">Cliente destino</Label>
                            <Select
                                value={data.destino_client_id}
                                onValueChange={(value) => {
                                    setData('destino_client_id', value);
                                    setData('destino_client_site_id', '');
                                }}
                            >
                                <SelectTrigger id="destino_client_id">
                                    <SelectValue placeholder="Selecciona un cliente" />
                                </SelectTrigger>
                                <SelectContent>
                                    {clients.map((client) => (
                                        <SelectItem key={client.id} value={String(client.id)}>
                                            {client.razon_social} ({client.codigo})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.destino_client_id} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="destino_client_site_id">Sede destino (opcional)</Label>
                            <Select
                                value={data.destino_client_site_id || 'ninguna'}
                                onValueChange={(value) => setData('destino_client_site_id', value === 'ninguna' ? '' : value)}
                                disabled={!selectedClient}
                            >
                                <SelectTrigger id="destino_client_site_id">
                                    <SelectValue placeholder="Sin sede especifica" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="ninguna">Sin sede especifica</SelectItem>
                                    {(selectedClient?.sites ?? []).map((site) => (
                                        <SelectItem key={site.id} value={String(site.id)}>
                                            {site.nombre}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.destino_client_site_id} />
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="fecha">Fecha</Label>
                            <Input id="fecha" type="date" value={data.fecha} onChange={(e) => setData('fecha', e.target.value)} required />
                            <InputError message={errors.fecha} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="motivo">Motivo</Label>
                            <Input
                                id="motivo"
                                value={data.motivo}
                                onChange={(e) => setData('motivo', e.target.value)}
                                placeholder="Ej. Venta a otro cliente"
                                required
                            />
                            <InputError message={errors.motivo} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="observacion">Observacion</Label>
                        <Textarea id="observacion" value={data.observacion} onChange={(e) => setData('observacion', e.target.value)} />
                        <InputError message={errors.observacion} />
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            Confirmar transferencia
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
