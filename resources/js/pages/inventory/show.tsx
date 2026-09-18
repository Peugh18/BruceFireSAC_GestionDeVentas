import { CatalogInventoryTabs } from '@/components/catalog-inventory-tabs';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import InventoryField from '@/components/inventory-field';
import InventoryPagination from '@/components/inventory-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type InventoryPage, type InventoryStock, type InventoryUnit, quantity } from '@/types/inventory';
import { Head, Link, useForm } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

export default function Show({
    stock,
    units,
    canAdjust,
    today,
    status,
}: {
    stock: InventoryStock;
    units: InventoryPage<InventoryUnit>;
    canAdjust: boolean;
    today: string;
    status?: string;
}) {
    const minimum = useForm({ stock_minimo: stock.stock_minimo });
    const movement = useForm({ tipo: 'salida', cantidad: '', motivo: '', referencia: '', fecha: today, observacion: '', unit_ids: [] as number[] });
    const submitMinimum: FormEventHandler = (e) => {
        e.preventDefault();
        minimum.patch(route('inventory.update', stock.id), { preserveScroll: true });
    };
    const submitMovement: FormEventHandler = (e) => {
        e.preventDefault();
        movement.post(route('inventory.movements.store', stock.id), { preserveScroll: true, onSuccess: () => movement.reset() });
    };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Catálogo', href: route('catalog.index') },
                { title: 'Inventario', href: route('inventory.index') },
                { title: stock.catalog_item.nombre, href: route('inventory.show', stock.id) },
            ]}
        >
            <Head title={stock.catalog_item.nombre + ' · Inventario'} />
            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <HeadingSmall title={stock.catalog_item.nombre} description={stock.catalog_item.codigo} />
                    <Button variant="outline" asChild>
                        <Link href={route('inventory.movements', { catalog_item_id: stock.catalog_item_id })}>Ver movimientos</Link>
                    </Button>
                </div>
                <CatalogInventoryTabs active="inventory" />
                {status && (
                    <div role="status" className="bg-muted/50 rounded-lg border px-4 py-3 text-sm">
                        {status}
                    </div>
                )}
                <div className="flex flex-wrap gap-6 rounded-xl border p-4">
                    <div>
                        <p className="text-muted-foreground text-sm">Stock actual</p>
                        <p className="mt-1 text-2xl font-semibold tabular-nums">
                            {quantity(stock.stock_actual)}{' '}
                            <span className="text-muted-foreground text-sm font-normal">{stock.catalog_item.unidad}</span>
                        </p>
                    </div>
                    <div>
                        <p className="text-muted-foreground text-sm">Stock mínimo</p>
                        <p className="mt-1 text-2xl font-semibold tabular-nums">{quantity(stock.stock_minimo)}</p>
                    </div>
                    {Number(stock.stock_actual) < Number(stock.stock_minimo) && (
                        <Badge className="self-center" variant="destructive">
                            Bajo el mínimo
                        </Badge>
                    )}
                </div>
                {canAdjust && (
                    <form onSubmit={submitMinimum} className="flex max-w-md items-end gap-3">
                        <div className="flex-1">
                            <InventoryField
                                id="minimum"
                                label="Stock mínimo"
                                type="number"
                                min="0"
                                max="999999999.999"
                                step="0.001"
                                required
                                value={minimum.data.stock_minimo}
                                onChange={(e) => minimum.setData('stock_minimo', e.target.value)}
                                error={minimum.errors.stock_minimo}
                            />
                        </div>
                        <Button variant="secondary" disabled={minimum.processing}>
                            Guardar mínimo
                        </Button>
                    </form>
                )}
                {(stock.catalog_item.control_serializado || units.total > 0) && (
                    <section className="space-y-4">
                        <HeadingSmall
                            title="Unidades y disponibilidad"
                            description="Las unidades observadas permanecen retenidas y no forman parte del stock conforme. Las salidas conservan su trazabilidad."
                        />
                        {units.data.length === 0 ? (
                            <p className="text-muted-foreground rounded-xl border border-dashed p-8 text-center text-sm">
                                No se han recibido unidades serializadas.
                            </p>
                        ) : (
                            <div className="overflow-x-auto rounded-xl border">
                                <table className="w-full text-left text-sm">
                                    <thead className="bg-muted/50 text-muted-foreground">
                                        <tr>
                                            {['', 'Serie / Código de barras', 'Marca / Capacidad', 'Año', 'Disponibilidad'].map((label) => (
                                                <th key={label} className="px-4 py-3 font-medium">
                                                    {label}
                                                </th>
                                            ))}
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y">
                                        {units.data.map((unit) => (
                                            <tr key={unit.id}>
                                                <td className="px-4 py-4">
                                                    {canAdjust && unit.en_stock && unit.conforme && unit.estado === 'disponible' && (
                                                        <Checkbox
                                                            aria-label={'Seleccionar serie ' + unit.serie + ' para salida'}
                                                            checked={movement.data.unit_ids.includes(unit.id)}
                                                            onCheckedChange={(value) =>
                                                                movement.setData(
                                                                    'unit_ids',
                                                                    value === true
                                                                        ? [...movement.data.unit_ids, unit.id]
                                                                        : movement.data.unit_ids.filter((id) => id !== unit.id),
                                                                )
                                                            }
                                                        />
                                                    )}
                                                </td>
                                                <td className="px-4 py-4 font-mono text-xs">
                                                    <div>{unit.serie}</div>
                                                    <div className="text-muted-foreground mt-1">{unit.barcode || 'Sin código de barras'}</div>
                                                </td>
                                                <td className="px-4 py-4">
                                                    {unit.marca} · {unit.capacidad}
                                                </td>
                                                <td className="px-4 py-4">{unit.anio}</td>
                                                <td className="px-4 py-4">
                                                    <Badge variant={!unit.conforme ? 'destructive' : 'secondary'}>
                                                        {!unit.conforme
                                                            ? 'Observada / retenida'
                                                            : !unit.en_stock
                                                              ? 'Fuera de stock'
                                                              : { disponible: 'Disponible', reservado: 'Reservado', vendido: 'Vendido' }[unit.estado]}
                                                    </Badge>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                        <InventoryPagination page={units} preserveState />
                    </section>
                )}
                {canAdjust && (
                    <section className="max-w-2xl space-y-6 rounded-xl border p-4 md:p-6">
                        <HeadingSmall
                            title="Registrar salida o ajuste"
                            description="Indica el motivo de cada cambio. Un ajuste positivo aumenta el stock y uno negativo lo reduce. Las nuevas unidades serializadas se incorporan mediante recepción."
                        />
                        <form onSubmit={submitMovement} className="space-y-6">
                            <div className="grid gap-6 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="movement-type">Tipo</Label>
                                    <Select value={movement.data.tipo} onValueChange={(value) => movement.setData('tipo', value)}>
                                        <SelectTrigger id="movement-type">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="salida">Salida de almacén</SelectItem>
                                            <SelectItem value="ajuste">Ajuste autorizado</SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={movement.errors.tipo} />
                                </div>
                                <InventoryField
                                    id="movement-quantity"
                                    label={movement.data.tipo === 'ajuste' ? 'Variación de stock (+ / −)' : 'Cantidad de salida'}
                                    type="number"
                                    step={stock.catalog_item.control_serializado ? '1' : '0.001'}
                                    min={movement.data.tipo === 'salida' ? '0.001' : '-999999.999'}
                                    max="999999.999"
                                    required
                                    value={movement.data.cantidad}
                                    onChange={(e) => movement.setData('cantidad', e.target.value)}
                                    error={movement.errors.cantidad}
                                />
                                <InventoryField
                                    id="movement-date"
                                    label="Fecha"
                                    type="date"
                                    required
                                    max={today}
                                    value={movement.data.fecha}
                                    onChange={(e) => movement.setData('fecha', e.target.value)}
                                    error={movement.errors.fecha}
                                />
                                <InventoryField
                                    id="movement-reference"
                                    label="Referencia (opcional)"
                                    maxLength={255}
                                    value={movement.data.referencia}
                                    onChange={(e) => movement.setData('referencia', e.target.value)}
                                    error={movement.errors.referencia}
                                />
                            </div>
                            <InventoryField
                                id="movement-reason"
                                label="Motivo"
                                required
                                maxLength={255}
                                placeholder="Consumo interno, devolución, diferencia de conteo..."
                                value={movement.data.motivo}
                                onChange={(e) => movement.setData('motivo', e.target.value)}
                                error={movement.errors.motivo}
                            />
                            <InventoryField
                                id="movement-notes"
                                label="Observación (opcional)"
                                maxLength={5000}
                                value={movement.data.observacion}
                                onChange={(e) => movement.setData('observacion', e.target.value)}
                                error={movement.errors.observacion}
                            />
                            {stock.catalog_item.control_serializado && (
                                <p className="text-muted-foreground text-sm">
                                    Selecciona las series que salen en la tabla superior. Unidades seleccionadas: {movement.data.unit_ids.length}.
                                </p>
                            )}
                            <InputError message={movement.errors.unit_ids} />
                            <Button disabled={movement.processing}>{movement.processing ? 'Registrando...' : 'Registrar movimiento'}</Button>
                        </form>
                    </section>
                )}
            </div>
        </AppLayout>
    );
}
