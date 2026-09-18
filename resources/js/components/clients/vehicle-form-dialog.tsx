import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { type Client, type Vehicle } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler, ReactNode, useState } from 'react';

interface VehicleFormData {
    placa: string;
    marca: string;
    modelo: string;
    descripcion: string;
    activo: boolean;
    [key: string]: string | boolean;
}

function emptyData(vehicle?: Vehicle): VehicleFormData {
    return {
        placa: vehicle?.placa ?? '',
        marca: vehicle?.marca ?? '',
        modelo: vehicle?.modelo ?? '',
        descripcion: vehicle?.descripcion ?? '',
        activo: vehicle?.activo ?? true,
    };
}

export function VehicleFormDialog({ client, vehicle, trigger }: { client: Client; vehicle?: Vehicle; trigger: ReactNode }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, put, errors, processing, reset } = useForm<VehicleFormData>(emptyData(vehicle));

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        const options = {
            onSuccess: () => {
                setOpen(false);
                reset();
            },
        };

        if (vehicle) {
            put(route('clients.vehicles.update', [client.id, vehicle.id]), options);
        } else {
            post(route('clients.vehicles.store', client.id), options);
        }
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
                    <DialogTitle>{vehicle ? 'Editar vehiculo' : 'Nuevo vehiculo'}</DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="vehicle-placa">Placa</Label>
                            <Input
                                id="vehicle-placa"
                                value={data.placa}
                                onChange={(e) => setData('placa', e.target.value.toUpperCase())}
                                required
                            />
                            <InputError message={errors.placa} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="vehicle-marca">Marca</Label>
                            <Input id="vehicle-marca" value={data.marca} onChange={(e) => setData('marca', e.target.value)} />
                            <InputError message={errors.marca} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="vehicle-modelo">Modelo</Label>
                        <Input id="vehicle-modelo" value={data.modelo} onChange={(e) => setData('modelo', e.target.value)} />
                        <InputError message={errors.modelo} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="vehicle-descripcion">Descripcion</Label>
                        <Textarea
                            id="vehicle-descripcion"
                            value={data.descripcion}
                            onChange={(e) => setData('descripcion', e.target.value)}
                        />
                        <InputError message={errors.descripcion} />
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="vehicle-activo"
                            checked={data.activo}
                            onCheckedChange={(checked) => setData('activo', checked === true)}
                        />
                        <Label htmlFor="vehicle-activo" className="cursor-pointer font-normal">
                            Vehiculo activo
                        </Label>
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {vehicle ? 'Guardar cambios' : 'Registrar vehiculo'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
