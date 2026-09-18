import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import InventoryField from '@/components/inventory-field';
import InventoryPagination from '@/components/inventory-pagination';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type InventoryPage, type InventoryStock, quantity } from '@/types/inventory';
import { Head, Link, useForm } from '@inertiajs/react';
import { AlertTriangle, History, PackageSearch, Plus } from 'lucide-react';
import { type FormEventHandler } from 'react';

interface Props {
    stocks: InventoryPage<InventoryStock>;
    filters: { search: string; low_stock: boolean };
    lowStockCount: number;
    can: { receive: boolean; adjust: boolean };
    status?: string;
}

export default function Index({ stocks, filters, lowStockCount, can, status }: Props) {
    const form = useForm({ search: filters.search, low_stock: filters.low_stock ? '1' : '0' });
    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.get(route('inventory.index'), { preserveState: false, preserveScroll: true });
    };
    const stockBadge = (stock: InventoryStock) =>
        Number(stock.stock_actual) < Number(stock.stock_minimo) ? (
            <Badge variant="destructive">Bajo el mínimo</Badge>
        ) : (
            <Badge variant="secondary">{Number(stock.stock_actual) === 0 ? 'Sin existencias' : 'Stock suficiente'}</Badge>
        );

    return (
        <AppLayout breadcrumbs={[{ title: 'Inventario', href: route('inventory.index') }]}>
            <Head title="Inventario" />
            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <HeadingSmall title="Inventario" description="Controla existencias, recepciones y movimientos del almacén." />
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={route('inventory.movements')}>
                                <History className="size-4" />
                                Movimientos
                            </Link>
                        </Button>
                        {can.receive && (
                            <Button asChild>
                                <Link href={route('inventory.receive')}>
                                    <Plus className="size-4" />
                                    Recibir mercadería
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>
                {status && (
                    <div role="status" className="bg-muted/50 rounded-lg border px-4 py-3 text-sm">
                        {status}
                    </div>
                )}
                {lowStockCount > 0 && (
                    <div
                        role="status"
                        className="border-destructive/30 bg-destructive/5 text-destructive flex items-center gap-3 rounded-lg border px-4 py-3 text-sm"
                    >
                        <AlertTriangle className="size-5 shrink-0" />
                        <span>{lowStockCount} artículos tienen stock por debajo del mínimo.</span>
                    </div>
                )}
                <form onSubmit={submit} className="flex flex-col gap-4 sm:flex-row sm:items-end">
                    <div className="flex-1">
                        <InventoryField
                            id="search"
                            label="Buscar"
                            placeholder="Nombre, código o categoría"
                            maxLength={255}
                            value={form.data.search}
                            onChange={(event) => form.setData('search', event.target.value)}
                            error={form.errors.search}
                        />
                    </div>
                    <div className="grid gap-2 py-2">
                        <div className="flex items-center gap-2">
                            <Checkbox
                                id="low-stock"
                                checked={form.data.low_stock === '1'}
                                onCheckedChange={(value) => form.setData('low_stock', value === true ? '1' : '0')}
                            />
                            <Label htmlFor="low-stock">Solo bajo el mínimo</Label>
                        </div>
                        <InputError message={form.errors.low_stock} />
                    </div>
                    <Button type="submit" variant="secondary" disabled={form.processing}>
                        Filtrar
                    </Button>
                    <Button variant="ghost" asChild>
                        <Link href={route('inventory.index')}>Limpiar</Link>
                    </Button>
                </form>
                {stocks.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed px-6 py-16 text-center">
                        <PackageSearch className="text-muted-foreground size-8" />
                        <h2 className="font-medium">No hay existencias para mostrar</h2>
                        <p className="text-muted-foreground text-sm">
                            Prueba otra búsqueda. Los artículos deben tener el control de stock habilitado en el catálogo.
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="hidden overflow-x-auto rounded-xl border md:block">
                            <table className="w-full text-left text-sm">
                                <thead className="bg-muted/50 text-muted-foreground">
                                    <tr>
                                        {['Artículo', 'Stock actual', 'Stock mínimo', 'Estado', ''].map((heading) => (
                                            <th key={heading} className="px-4 py-3 font-medium">
                                                {heading}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {stocks.data.map((stock) => (
                                        <tr key={stock.id} className="hover:bg-muted/30">
                                            <td className="px-4 py-4">
                                                <div className="font-medium">{stock.catalog_item.nombre}</div>
                                                <div className="text-muted-foreground mt-1 font-mono text-xs">{stock.catalog_item.codigo}</div>
                                                <div className="text-muted-foreground mt-1 text-xs">
                                                    {stock.catalog_item.control_serializado ? 'Control por serie' : stock.catalog_item.categoria}
                                                    {!stock.catalog_item.activo && ' · Inactivo'}
                                                </div>
                                            </td>
                                            <td className="px-4 py-4 tabular-nums">
                                                {quantity(stock.stock_actual)} {stock.catalog_item.unidad}
                                            </td>
                                            <td className="px-4 py-4 tabular-nums">{quantity(stock.stock_minimo)}</td>
                                            <td className="px-4 py-4">{stockBadge(stock)}</td>
                                            <td className="px-4 py-4">
                                                <Button variant="ghost" size="sm" asChild>
                                                    <Link href={route('inventory.show', stock.id)}>Ver detalle</Link>
                                                </Button>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="grid gap-3 md:hidden">
                            {stocks.data.map((stock) => (
                                <article key={stock.id} className="space-y-3 rounded-xl border p-4">
                                    <div className="flex flex-wrap items-start justify-between gap-3">
                                        <h2 className="font-medium">{stock.catalog_item.nombre}</h2>
                                        {stockBadge(stock)}
                                    </div>
                                    <p className="text-muted-foreground font-mono text-xs">{stock.catalog_item.codigo}</p>
                                    <p className="text-sm">
                                        Stock: {quantity(stock.stock_actual)} {stock.catalog_item.unidad} · Mínimo: {quantity(stock.stock_minimo)}
                                    </p>
                                    <Button size="sm" variant="outline" asChild>
                                        <Link href={route('inventory.show', stock.id)}>Ver detalle</Link>
                                    </Button>
                                </article>
                            ))}
                        </div>
                    </>
                )}
                <InventoryPagination page={stocks} />
            </div>
        </AppLayout>
    );
}
