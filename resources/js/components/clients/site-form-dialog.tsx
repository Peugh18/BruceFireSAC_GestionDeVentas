import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type Client, type ClientSite, type TipoSede } from '@/types';
import { useForm } from '@inertiajs/react';
import { FormEventHandler, ReactNode, useState } from 'react';

interface SiteFormData {
    tipo: TipoSede;
    nombre: string;
    direccion: string;
    ubigeo: string;
    referencia: string;
    contacto: string;
    telefono: string;
    email: string;
    activo: boolean;
    [key: string]: string | boolean;
}

const TIPOS_SEDE: { value: TipoSede; label: string }[] = [
    { value: 'oficina', label: 'Oficina' },
    { value: 'tienda', label: 'Tienda' },
    { value: 'planta', label: 'Planta' },
    { value: 'almacen', label: 'Almacen' },
    { value: 'local', label: 'Local' },
    { value: 'sucursal', label: 'Sucursal' },
    { value: 'otra', label: 'Otra' },
];

function emptyData(site?: ClientSite): SiteFormData {
    return {
        tipo: site?.tipo ?? 'oficina',
        nombre: site?.nombre ?? '',
        direccion: site?.direccion ?? '',
        ubigeo: site?.ubigeo ?? '',
        referencia: site?.referencia ?? '',
        contacto: site?.contacto ?? '',
        telefono: site?.telefono ?? '',
        email: site?.email ?? '',
        activo: site?.activo ?? true,
    };
}

export function SiteFormDialog({ client, site, trigger }: { client: Client; site?: ClientSite; trigger: ReactNode }) {
    const [open, setOpen] = useState(false);
    const { data, setData, post, put, errors, processing, reset } = useForm<SiteFormData>(emptyData(site));

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        const options = {
            onSuccess: () => {
                setOpen(false);
                reset();
            },
        };

        if (site) {
            put(route('clients.sites.update', [client.id, site.id]), options);
        } else {
            post(route('clients.sites.store', client.id), options);
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
                    <DialogTitle>{site ? 'Editar sede' : 'Nueva sede'}</DialogTitle>
                </DialogHeader>

                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="site-tipo">Tipo</Label>
                            <Select value={data.tipo} onValueChange={(value) => setData('tipo', value as TipoSede)}>
                                <SelectTrigger id="site-tipo">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {TIPOS_SEDE.map((tipo) => (
                                        <SelectItem key={tipo.value} value={tipo.value}>
                                            {tipo.label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.tipo} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="site-nombre">Nombre</Label>
                            <Input id="site-nombre" value={data.nombre} onChange={(e) => setData('nombre', e.target.value)} required />
                            <InputError message={errors.nombre} />
                        </div>
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="site-direccion">Direccion</Label>
                        <Input id="site-direccion" value={data.direccion} onChange={(e) => setData('direccion', e.target.value)} required />
                        <InputError message={errors.direccion} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="site-referencia">Referencia</Label>
                            <Input id="site-referencia" value={data.referencia} onChange={(e) => setData('referencia', e.target.value)} />
                            <InputError message={errors.referencia} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="site-ubigeo">Ubigeo</Label>
                            <Input id="site-ubigeo" value={data.ubigeo} onChange={(e) => setData('ubigeo', e.target.value)} maxLength={6} />
                            <InputError message={errors.ubigeo} />
                        </div>
                    </div>

                    <div className="grid gap-4 sm:grid-cols-3">
                        <div className="grid gap-2">
                            <Label htmlFor="site-contacto">Contacto</Label>
                            <Input id="site-contacto" value={data.contacto} onChange={(e) => setData('contacto', e.target.value)} />
                            <InputError message={errors.contacto} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="site-telefono">Telefono</Label>
                            <Input id="site-telefono" value={data.telefono} onChange={(e) => setData('telefono', e.target.value)} />
                            <InputError message={errors.telefono} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="site-email">Correo</Label>
                            <Input id="site-email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                            <InputError message={errors.email} />
                        </div>
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox id="site-activo" checked={data.activo} onCheckedChange={(checked) => setData('activo', checked === true)} />
                        <Label htmlFor="site-activo" className="cursor-pointer font-normal">
                            Sede activa
                        </Label>
                    </div>

                    <DialogFooter>
                        <Button type="submit" disabled={processing}>
                            {site ? 'Guardar cambios' : 'Registrar sede'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}
