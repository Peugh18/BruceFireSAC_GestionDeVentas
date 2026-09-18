import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { catalogTypes, type CatalogItem, type CatalogType } from '@/types/catalog';
import { Link, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

export default function CatalogForm({ item }: { item?: CatalogItem }) {
    const { data, setData, post, put, errors, processing } = useForm({
        tipo: item?.tipo ?? ('producto' as CatalogType),
        categoria: item?.categoria ?? '',
        nombre: item?.nombre ?? '',
        descripcion: item?.descripcion ?? '',
        unidad: item?.unidad ?? 'Unidad',
        precio: item?.precio ?? '',
        aplica_igv: item?.aplica_igv ?? true,
        activo: item?.activo ?? true,
        controla_stock: item?.controla_stock ?? false,
        control_serializado: item?.control_serializado ?? false,
        genera_barcode: item?.genera_barcode ?? false,
        tipo_tecnico: item?.tipo_tecnico ?? '',
        requiere_orden: item?.requiere_orden ?? false,
        requiere_certificado: item?.requiere_certificado ?? false,
        checklist_aplicable: item?.checklist_aplicable ?? '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        if (item) {
            put(route('catalog.update', item.id));
        } else {
            post(route('catalog.store'));
        }
    };

    const textField = (
        name: 'nombre' | 'categoria' | 'unidad' | 'tipo_tecnico' | 'checklist_aplicable',
        label: string,
        maxLength: number,
        required = true,
    ) => (
        <div className="grid gap-2">
            <Label htmlFor={name}>{label}</Label>
            <Input
                id={name}
                value={data[name]}
                onChange={(event) => setData(name, event.target.value)}
                maxLength={maxLength}
                required={required}
                aria-invalid={!!errors[name]}
                aria-describedby={errors[name] ? `${name}-error` : undefined}
            />
            <InputError id={`${name}-error`} message={errors[name]} />
        </div>
    );

    const checkbox = (
        name: 'aplica_igv' | 'activo' | 'controla_stock' | 'control_serializado' | 'genera_barcode' | 'requiere_orden' | 'requiere_certificado',
        label: string,
    ) => (
        <div className="space-y-2">
            <div className="flex items-center gap-3">
                <Checkbox
                    id={name}
                    checked={data[name]}
                    onCheckedChange={(checked) => setData(name, checked === true)}
                    aria-describedby={errors[name] ? `${name}-error` : undefined}
                />
                <Label htmlFor={name}>{label}</Label>
            </div>
            <InputError id={`${name}-error`} message={errors[name]} />
        </div>
    );

    return (
        <div className="max-w-3xl space-y-6">
            <HeadingSmall
                title={item ? 'Editar registro' : 'Nuevo registro de catálogo'}
                description="Define los datos comerciales y las opciones del producto, servicio o repuesto."
            />
            {item && (
                <p className="text-muted-foreground text-sm break-all">
                    Código: <span className="font-mono">{item.codigo}</span>
                </p>
            )}
            {!item && <p className="text-muted-foreground text-sm">El código se generará automáticamente al guardar.</p>}
            <form onSubmit={submit} className="space-y-6">
                <fieldset disabled={processing} className="space-y-6">
                    <legend className="mb-4 text-sm font-medium">Información general</legend>
                    <div className="grid gap-6 sm:grid-cols-2">
                        <div className="grid gap-2">
                            <Label htmlFor="tipo">Tipo</Label>
                            <Select value={data.tipo} onValueChange={(value: CatalogType) => setData('tipo', value)}>
                                <SelectTrigger id="tipo">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {Object.entries(catalogTypes).map(([value, label]) => (
                                        <SelectItem key={value} value={value}>
                                            {label}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.tipo} />
                        </div>
                        {textField('categoria', 'Categoría', 100)}
                        <div className="sm:col-span-2">{textField('nombre', 'Nombre', 255)}</div>
                        <div className="grid gap-2 sm:col-span-2">
                            <Label htmlFor="descripcion">Descripción (opcional)</Label>
                            <textarea
                                id="descripcion"
                                value={data.descripcion}
                                onChange={(event) => setData('descripcion', event.target.value)}
                                maxLength={5000}
                                rows={3}
                                className="border-input focus-visible:ring-ring flex w-full rounded-md border bg-transparent px-3 py-2 text-sm shadow-xs focus-visible:ring-2 focus-visible:outline-none disabled:opacity-50"
                                aria-invalid={!!errors.descripcion}
                            />
                            <InputError message={errors.descripcion} />
                        </div>
                        {textField('unidad', 'Unidad', 30)}
                        <div className="grid gap-2">
                            <Label htmlFor="precio">Precio (S/)</Label>
                            <Input
                                id="precio"
                                type="number"
                                inputMode="decimal"
                                min="0"
                                max="9999999999.99"
                                step="0.01"
                                value={data.precio}
                                onChange={(event) => setData('precio', event.target.value)}
                                required
                                aria-invalid={!!errors.precio}
                            />
                            <InputError message={errors.precio} />
                        </div>
                        {checkbox('aplica_igv', 'Aplica IGV')}
                        {checkbox('activo', 'Registro activo')}
                    </div>
                    {data.tipo === 'producto' && (
                        <>
                            <Separator />
                            <HeadingSmall title="Opciones del producto" description="Configura los controles aplicables a este producto." />
                            <div className="grid gap-4 sm:grid-cols-2">
                                {checkbox('controla_stock', 'Controla stock')}
                                {checkbox('control_serializado', 'Control serializado')}
                                {checkbox('genera_barcode', 'Genera código de barras')}
                            </div>
                        </>
                    )}
                    {data.tipo === 'servicio' && (
                        <>
                            <Separator />
                            <HeadingSmall title="Opciones del servicio" description="Define los requisitos para la atención técnica." />
                            <div className="grid gap-6 sm:grid-cols-2">
                                {textField('tipo_tecnico', 'Tipo de técnico', 100)}
                                {textField('checklist_aplicable', 'Checklist aplicable (opcional)', 255, false)}
                                {checkbox('requiere_orden', 'Requiere orden de servicio')}
                                {checkbox('requiere_certificado', 'Requiere certificado')}
                            </div>
                        </>
                    )}
                </fieldset>
                <div className="flex items-center gap-4 border-t pt-6">
                    <Button disabled={processing}>{processing ? 'Guardando...' : 'Guardar registro'}</Button>
                    <Button variant="outline" asChild>
                        <Link href={route('catalog.index')}>Cancelar</Link>
                    </Button>
                </div>
            </form>
        </div>
    );
}
