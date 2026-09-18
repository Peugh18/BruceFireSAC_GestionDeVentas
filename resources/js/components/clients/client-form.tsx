import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { type TipoDocumento } from '@/types';
import { FormEventHandler } from 'react';

export interface ClientFormData {
    tipo_documento: TipoDocumento;
    numero_documento: string;
    razon_social: string;
    nombre_comercial: string;
    telefono: string;
    whatsapp: string;
    email: string;
    direccion_fiscal: string;
    departamento: string;
    provincia: string;
    distrito: string;
    ubigeo: string;
    activo: boolean;
    observaciones: string;
    [key: string]: string | boolean;
}

const TIPOS_DOCUMENTO: { value: TipoDocumento; label: string }[] = [
    { value: 'dni', label: 'DNI' },
    { value: 'ruc', label: 'RUC' },
    { value: 'ce', label: 'Carnet de extranjeria' },
    { value: 'pasaporte', label: 'Pasaporte' },
];

export function ClientForm({
    data,
    setData,
    errors,
    processing,
    onSubmit,
    submitLabel,
}: {
    data: ClientFormData;
    setData: (key: keyof ClientFormData, value: string | boolean) => void;
    errors: Partial<Record<keyof ClientFormData, string>>;
    processing: boolean;
    onSubmit: FormEventHandler;
    submitLabel: string;
}) {
    return (
        <form onSubmit={onSubmit} className="space-y-8">
            <div className="grid gap-6 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="tipo_documento">Tipo de documento</Label>
                    <Select value={data.tipo_documento} onValueChange={(value) => setData('tipo_documento', value)}>
                        <SelectTrigger id="tipo_documento">
                            <SelectValue placeholder="Selecciona un tipo" />
                        </SelectTrigger>
                        <SelectContent>
                            {TIPOS_DOCUMENTO.map((tipo) => (
                                <SelectItem key={tipo.value} value={tipo.value}>
                                    {tipo.label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.tipo_documento} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="numero_documento">Numero de documento</Label>
                    <Input
                        id="numero_documento"
                        value={data.numero_documento}
                        onChange={(e) => setData('numero_documento', e.target.value)}
                        required
                    />
                    <InputError message={errors.numero_documento} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="razon_social">Razon social / Nombres</Label>
                    <Input id="razon_social" value={data.razon_social} onChange={(e) => setData('razon_social', e.target.value)} required />
                    <InputError message={errors.razon_social} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="nombre_comercial">Nombre comercial</Label>
                    <Input
                        id="nombre_comercial"
                        value={data.nombre_comercial}
                        onChange={(e) => setData('nombre_comercial', e.target.value)}
                    />
                    <InputError message={errors.nombre_comercial} />
                </div>
            </div>

            <div className="grid gap-6 sm:grid-cols-3">
                <div className="grid gap-2">
                    <Label htmlFor="telefono">Telefono</Label>
                    <Input id="telefono" value={data.telefono} onChange={(e) => setData('telefono', e.target.value)} />
                    <InputError message={errors.telefono} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="whatsapp">WhatsApp</Label>
                    <Input id="whatsapp" value={data.whatsapp} onChange={(e) => setData('whatsapp', e.target.value)} />
                    <InputError message={errors.whatsapp} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="email">Correo electronico</Label>
                    <Input id="email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                    <InputError message={errors.email} />
                </div>
            </div>

            <div className="grid gap-6 sm:grid-cols-2">
                <div className="grid gap-2 sm:col-span-2">
                    <Label htmlFor="direccion_fiscal">Direccion fiscal</Label>
                    <Input
                        id="direccion_fiscal"
                        value={data.direccion_fiscal}
                        onChange={(e) => setData('direccion_fiscal', e.target.value)}
                    />
                    <InputError message={errors.direccion_fiscal} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="departamento">Departamento</Label>
                    <Input id="departamento" value={data.departamento} onChange={(e) => setData('departamento', e.target.value)} />
                    <InputError message={errors.departamento} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="provincia">Provincia</Label>
                    <Input id="provincia" value={data.provincia} onChange={(e) => setData('provincia', e.target.value)} />
                    <InputError message={errors.provincia} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="distrito">Distrito</Label>
                    <Input id="distrito" value={data.distrito} onChange={(e) => setData('distrito', e.target.value)} />
                    <InputError message={errors.distrito} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="ubigeo">Ubigeo</Label>
                    <Input id="ubigeo" value={data.ubigeo} onChange={(e) => setData('ubigeo', e.target.value)} maxLength={6} />
                    <InputError message={errors.ubigeo} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="observaciones">Observaciones</Label>
                <Textarea id="observaciones" value={data.observaciones} onChange={(e) => setData('observaciones', e.target.value)} />
                <InputError message={errors.observaciones} />
            </div>

            <div className="flex items-center gap-2">
                <Checkbox id="activo" checked={data.activo} onCheckedChange={(checked) => setData('activo', checked === true)} />
                <Label htmlFor="activo" className="cursor-pointer font-normal">
                    Cliente activo
                </Label>
            </div>

            <div className="flex items-center gap-4">
                <Button type="submit" disabled={processing}>
                    {submitLabel}
                </Button>
            </div>
        </form>
    );
}
