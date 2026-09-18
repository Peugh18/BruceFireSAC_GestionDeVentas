import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import InventoryField from '@/components/inventory-field';
import InventoryPagination from '@/components/inventory-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type InventoryMovement, type InventoryPage, movementTypes, quantity } from '@/types/inventory';
import { Head, Link, useForm } from '@inertiajs/react';
import { History } from 'lucide-react';
import { type FormEventHandler } from 'react';

export default function Movements({
    movements,
    filters,
}: {
    movements: InventoryPage<InventoryMovement>;
    filters: { search: string; tipo: string; catalog_item_id: string; desde: string; hasta: string };
}) {
    const form = useForm(filters);
    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        form.get(route('inventory.movements'), { preserveState: false, preserveScroll: true });
    };
    const details = (movement: InventoryMovement) => (
        <details className="text-sm">
            <summary className="cursor-pointer font-medium">Ver detalle</summary>
            <div className="mt-3 space-y-2">
                <p>{movement.observacion || 'Sin observaciones.'}</p>
                {movement.reception && (
                    <>
                        <p>Proveedor: {movement.reception.proveedor}</p>
                        <p>Documento: {movement.reception.documento_referencia}</p>
                        <p>
                            Recibido: {quantity(movement.reception.cantidad)} · Conforme: {quantity(movement.reception.cantidad_conforme)} ·
                            Observado: {quantity(movement.reception.cantidad_observada)}
                        </p>
                    </>
                )}
                {movement.withdrawn_units.length > 0 && <p>Series retiradas: {movement.withdrawn_units.map((unit) => unit.serie).join(', ')}</p>}
            </div>
        </details>
    );

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Inventario', href: route('inventory.index') },
                { title: 'Movimientos', href: route('inventory.movements') },
            ]}
        >
            <Head title="Movimientos de inventario" />
            <div className="space-y-6 p-4 md:p-6">
                <HeadingSmall title="Movimientos de inventario" description="Consulta entradas, salidas y ajustes con su responsable y referencia." />
                <form onSubmit={submit} className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <div className="lg:col-span-2">
                        <InventoryField
                            id="search"
                            label="Buscar"
                            placeholder="Artículo, código, referencia, motivo o proveedor"
                            maxLength={255}
                            value={form.data.search}
                            onChange={(e) => form.setData('search', e.target.value)}
                            error={form.errors.search}
                        />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="type">Tipo</Label>
                        <Select value={form.data.tipo || 'todos'} onValueChange={(value) => form.setData('tipo', value === 'todos' ? '' : value)}>
                            <SelectTrigger id="type">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todos">Todos</SelectItem>
                                {Object.entries(movementTypes).map(([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={form.errors.tipo} />
                    </div>
                    <InventoryField
                        id="from"
                        label="Desde"
                        type="date"
                        value={form.data.desde}
                        onChange={(e) => form.setData('desde', e.target.value)}
                        error={form.errors.desde}
                    />
                    <InventoryField
                        id="to"
                        label="Hasta"
                        type="date"
                        value={form.data.hasta}
                        onChange={(e) => form.setData('hasta', e.target.value)}
                        error={form.errors.hasta}
                    />
                    <InputError message={form.errors.catalog_item_id} />
                    <div className="flex gap-2 sm:col-span-2 lg:col-span-5">
                        <Button type="submit" variant="secondary" disabled={form.processing}>
                            Filtrar
                        </Button>
                        <Button variant="ghost" asChild>
                            <Link href={route('inventory.movements')}>Limpiar filtros</Link>
                        </Button>
                        {filters.catalog_item_id && <Badge variant="outline">Filtrado por artículo</Badge>}
                    </div>
                </form>
                {movements.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed px-6 py-16 text-center">
                        <History className="text-muted-foreground size-8" />
                        <h2 className="font-medium">No hay movimientos para mostrar</h2>
                        <p className="text-muted-foreground text-sm">Las recepciones y ajustes aparecerán aquí. Prueba otros filtros.</p>
                    </div>
                ) : (
                    <>
                        <div className="hidden overflow-x-auto rounded-xl border md:block">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-muted/50 text-muted-foreground">
                                    <tr>
                                        {['Fecha / Responsable', 'Artículo', 'Movimiento', 'Stock antes → después', 'Motivo / Referencia'].map(
                                            (label) => (
                                                <th key={label} className="px-4 py-3 font-medium">
                                                    {label}
                                                </th>
                                            ),
                                        )}
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {movements.data.map((movement) => (
                                        <tr key={movement.id} className="hover:bg-muted/30">
                                            <td className="px-4 py-4 whitespace-nowrap">
                                                {movement.fecha}
                                                <p className="text-muted-foreground mt-1 text-xs">{movement.usuario?.name || 'Usuario eliminado'}</p>
                                            </td>
                                            <td className="px-4 py-4">
                                                <p className="font-medium">{movement.catalog_item.nombre}</p>
                                                <p className="text-muted-foreground mt-1 font-mono text-xs">{movement.catalog_item.codigo}</p>
                                            </td>
                                            <td className="px-4 py-4">
                                                <Badge variant={movement.tipo === 'salida' ? 'outline' : 'secondary'}>
                                                    {movementTypes[movement.tipo]}
                                                </Badge>
                                                <p className="mt-2 tabular-nums">
                                                    {movement.tipo === 'salida' ? '−' : ''}
                                                    {quantity(movement.cantidad)} {movement.catalog_item.unidad}
                                                </p>
                                            </td>
                                            <td className="px-4 py-4 whitespace-nowrap tabular-nums">
                                                {quantity(movement.stock_antes)} → {quantity(movement.stock_despues)}
                                            </td>
                                            <td className="max-w-md space-y-2 px-4 py-4 break-words">
                                                <p>{movement.motivo}</p>
                                                <p className="text-muted-foreground text-xs">{movement.referencia || 'Sin referencia'}</p>
                                                {details(movement)}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="grid gap-3 md:hidden">
                            {movements.data.map((movement) => (
                                <article key={movement.id} className="space-y-3 rounded-xl border p-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <h2 className="font-medium">{movement.catalog_item.nombre}</h2>
                                        <Badge variant="secondary">{movementTypes[movement.tipo]}</Badge>
                                    </div>
                                    <p className="text-muted-foreground text-sm">
                                        {movement.fecha} · {movement.usuario?.name || 'Usuario eliminado'}
                                    </p>
                                    <p className="text-sm">
                                        {movement.tipo === 'salida' ? '−' : ''}
                                        {quantity(movement.cantidad)} {movement.catalog_item.unidad} · Stock: {quantity(movement.stock_antes)} →{' '}
                                        {quantity(movement.stock_despues)}
                                    </p>
                                    <p className="text-sm">
                                        {movement.motivo} · {movement.referencia || 'Sin referencia'}
                                    </p>
                                    {details(movement)}
                                </article>
                            ))}
                        </div>
                    </>
                )}
                <InventoryPagination page={movements} />
            </div>
        </AppLayout>
    );
}
