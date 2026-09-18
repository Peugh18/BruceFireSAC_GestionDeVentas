import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import InventoryField from '@/components/inventory-field';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Trash2 } from 'lucide-react';
import { type FormEventHandler } from 'react';

type UnitInput = { serie: string; marca: string; capacidad: string; anio: string; barcode: string; conforme: boolean };
type Item = { id: number; codigo: string; nombre: string; unidad: string; control_serializado: boolean };

export default function Receive({ items, today }: { items: Item[]; today: string }) {
    const form = useForm({
        proveedor: '',
        documento_referencia: '',
        fecha: today,
        catalog_item_id: '',
        cantidad: '',
        cantidad_conforme: '',
        cantidad_observada: '0',
        observacion: '',
        units: [] as UnitInput[],
    });
    const item = items.find((item) => String(item.id) === form.data.catalog_item_id);
    const errors = form.errors as Record<string, string>;
    const setUnits = (units: UnitInput[]) =>
        form.setData((data) => ({
            ...data,
            units,
            cantidad: String(units.length),
            cantidad_conforme: String(units.filter((unit) => unit.conforme).length),
            cantidad_observada: String(units.filter((unit) => !unit.conforme).length),
        }));
    const updateUnit = (index: number, change: Partial<UnitInput>) =>
        setUnits(form.data.units.map((unit, row) => (row === index ? { ...unit, ...change } : unit)));
    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.post(route('inventory.receive.store'), { preserveScroll: true });
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Inventario', href: route('inventory.index') },
                { title: 'Recepción de proveedor', href: route('inventory.receive') },
            ]}
        >
            <Head title="Recepción de proveedor" />
            <div className="space-y-6 p-4 md:p-6">
                <HeadingSmall
                    title="Recepción de proveedor"
                    description="Registra lo recibido y su conformidad. Solo las cantidades conformes aumentan el stock."
                />
                {items.length === 0 && (
                    <div role="status" className="bg-muted/50 rounded-lg border p-4 text-sm">
                        No hay artículos activos con control de stock habilitado en el catálogo.
                    </div>
                )}
                <form onSubmit={submit} className="max-w-4xl space-y-6">
                    <div className="grid gap-6 sm:grid-cols-2">
                        <InventoryField
                            id="proveedor"
                            label="Proveedor"
                            required
                            maxLength={255}
                            value={form.data.proveedor}
                            onChange={(e) => form.setData('proveedor', e.target.value)}
                            error={errors.proveedor}
                        />
                        <InventoryField
                            id="documento"
                            label="Documento de referencia"
                            required
                            maxLength={255}
                            placeholder="Factura o guía del proveedor"
                            value={form.data.documento_referencia}
                            onChange={(e) => form.setData('documento_referencia', e.target.value)}
                            error={errors.documento_referencia}
                        />
                        <InventoryField
                            id="fecha"
                            label="Fecha de recepción"
                            type="date"
                            required
                            max={today}
                            value={form.data.fecha}
                            onChange={(e) => form.setData('fecha', e.target.value)}
                            error={errors.fecha}
                        />
                        <div className="grid gap-2">
                            <Label htmlFor="item">Artículo del catálogo</Label>
                            <Select
                                value={form.data.catalog_item_id}
                                onValueChange={(value) =>
                                    form.setData((data) => ({
                                        ...data,
                                        catalog_item_id: value,
                                        units: [],
                                        cantidad: '',
                                        cantidad_conforme: '',
                                        cantidad_observada: '0',
                                    }))
                                }
                            >
                                <SelectTrigger id="item">
                                    <SelectValue placeholder="Selecciona un artículo" />
                                </SelectTrigger>
                                <SelectContent>
                                    {items.map((item) => (
                                        <SelectItem key={item.id} value={String(item.id)}>
                                            {item.nombre} · {item.codigo}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.catalog_item_id} />
                        </div>
                    </div>
                    <div className="grid gap-6 sm:grid-cols-3">
                        {(
                            [
                                ['cantidad', 'Cantidad recibida'],
                                ['cantidad_conforme', 'Cantidad conforme'],
                                ['cantidad_observada', 'Cantidad observada'],
                            ] as const
                        ).map(([key, label]) => (
                            <InventoryField
                                key={key}
                                id={key}
                                label={label}
                                type="number"
                                min={key === 'cantidad' ? '0.001' : '0'}
                                step="0.001"
                                max="999999.999"
                                required
                                readOnly={item?.control_serializado}
                                value={form.data[key]}
                                onChange={(e) => form.setData(key, e.target.value)}
                                error={errors[key]}
                            />
                        ))}
                    </div>
                    {item?.control_serializado && (
                        <section className="space-y-4 rounded-xl border p-4">
                            <HeadingSmall
                                title="Unidades serializadas"
                                description="Agrega una fila por unidad recibida (máximo 200). Desmarca Conforme para retener una unidad observada; las cantidades se calculan automáticamente."
                            />
                            <InputError message={errors.units} />
                            {form.data.units.map((unit, index) => (
                                <fieldset key={index} className="space-y-4 rounded-lg border p-4">
                                    <legend className="px-2 text-sm font-medium">Unidad {index + 1}</legend>
                                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                        {(
                                            [
                                                ['serie', 'Serie'],
                                                ['marca', 'Marca'],
                                                ['capacidad', 'Capacidad'],
                                                ['anio', 'Año'],
                                                ['barcode', 'Código de barras (opcional)'],
                                            ] as const
                                        ).map(([key, label]) => (
                                            <InventoryField
                                                key={key}
                                                id={'unit-' + index + '-' + key}
                                                label={label}
                                                type={key === 'anio' ? 'number' : 'text'}
                                                min={key === 'anio' ? 1900 : undefined}
                                                max={key === 'anio' ? Number(today.slice(0, 4)) : undefined}
                                                maxLength={100}
                                                required={key !== 'barcode'}
                                                value={unit[key]}
                                                onChange={(e) => updateUnit(index, { [key]: e.target.value })}
                                                error={errors['units.' + index + '.' + key]}
                                            />
                                        ))}
                                    </div>
                                    <div className="flex items-center justify-between gap-4">
                                        <div className="flex items-center gap-2">
                                            <Checkbox
                                                id={'conforme-' + index}
                                                checked={unit.conforme}
                                                onCheckedChange={(value) => updateUnit(index, { conforme: value === true })}
                                            />
                                            <Label htmlFor={'conforme-' + index}>Conforme</Label>
                                            <InputError message={errors['units.' + index + '.conforme']} />
                                        </div>
                                        <Button
                                            type="button"
                                            variant="ghost"
                                            size="sm"
                                            onClick={() => setUnits(form.data.units.filter((_, row) => row !== index))}
                                        >
                                            <Trash2 className="size-4" />
                                            Quitar unidad
                                        </Button>
                                    </div>
                                </fieldset>
                            ))}
                            <Button
                                type="button"
                                variant="outline"
                                disabled={form.data.units.length >= 200 || form.processing}
                                onClick={() =>
                                    setUnits([
                                        ...form.data.units,
                                        { serie: '', marca: '', capacidad: '', anio: today.slice(0, 4), barcode: '', conforme: true },
                                    ])
                                }
                            >
                                <Plus className="size-4" />
                                Agregar unidad
                            </Button>
                        </section>
                    )}
                    {!item?.control_serializado && <InputError message={errors.units} />}
                    <InventoryField
                        id="observacion"
                        label="Observación"
                        maxLength={5000}
                        required={Number(form.data.cantidad_observada) > 0}
                        placeholder="Describe diferencias, daños o motivos de retención"
                        value={form.data.observacion}
                        onChange={(e) => form.setData('observacion', e.target.value)}
                        error={errors.observacion}
                    />
                    <div className="flex gap-3">
                        <Button disabled={form.processing || !item}>{form.processing ? 'Registrando...' : 'Registrar recepción'}</Button>
                        <Button variant="outline" asChild>
                            <Link href={route('inventory.index')}>Cancelar</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
