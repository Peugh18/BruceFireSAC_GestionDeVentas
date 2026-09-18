import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated, type SharedData } from '@/types';
import { type Sale, type SaleEstado } from '@/types/sale';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Download, Eye, FileText, MoreHorizontal, Plus, Search, ShoppingBag } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Ventas', href: route('sales.index') }];

const ESTADO_BADGE_VARIANT: Record<SaleEstado, 'default' | 'secondary' | 'destructive'> = {
    pendiente: 'secondary',
    completada: 'default',
    anulada: 'destructive',
};

const formatCurrency = (amount: number | string) => {
    const numeric = typeof amount === 'string' ? parseFloat(amount) : amount;
    return new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(numeric || 0);
};

export default function SalesIndex({
    sales,
    filters,
    hasInternalPdf,
    status,
}: {
    sales: Paginated<Sale>;
    filters: { search: string; condicion: string };
    hasInternalPdf: boolean;
    status?: string;
}) {
    const { auth } = usePage<SharedData>().props;
    const canCreate = auth.permissions.includes('sales.create');

    const [search, setSearch] = useState(filters.search ?? '');
    const [condicion, setCondicion] = useState(filters.condicion || 'todos');

    const applyFilters = (nextSearch: string, nextCondicion: string) => {
        router.get(
            route('sales.index'),
            {
                search: nextSearch || undefined,
                condicion: nextCondicion === 'todos' ? undefined : nextCondicion,
            },
            { preserveState: true, replace: true },
        );
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilters(search, condicion);
    };

    const exportUrl = route('sales.export', {
        search: search || undefined,
        condicion: condicion === 'todos' ? undefined : condicion,
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Ventas" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <HeadingSmall title="Ventas" description="Registro comercial de ventas al contado y credito." />

                    <div className="flex flex-wrap gap-2">
                        <Button asChild variant="outline">
                            <a href={exportUrl}>
                                <Download className="mr-1 size-4" /> Exportar
                            </a>
                        </Button>

                        {canCreate && (
                            <Button asChild>
                                <Link href={route('sales.create')}>
                                    <Plus className="size-4 mr-1" /> Nueva venta
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                {status && (
                    <div role="status" className="rounded-lg border bg-muted/50 px-4 py-3 text-sm text-foreground">
                        {status}
                    </div>
                )}

                <form onSubmit={submit} className="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div className="relative flex-1 sm:max-w-xs">
                        <Search className="absolute left-3 top-2.5 size-4 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Buscar por numero o cliente"
                            className="pl-9"
                        />
                    </div>

                    <Select
                        value={condicion}
                        onValueChange={(value) => {
                            setCondicion(value);
                            applyFilters(search, value);
                        }}
                    >
                        <SelectTrigger className="sm:w-48">
                            <SelectValue placeholder="Condición de pago" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="todos">Todas las condiciones</SelectItem>
                            <SelectItem value="contado">Contado</SelectItem>
                            <SelectItem value="credito">Crédito</SelectItem>
                        </SelectContent>
                    </Select>

                    <Button type="submit" variant="secondary">
                        Buscar
                    </Button>
                </form>

                <div className="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Número</TableHead>
                                <TableHead>Cliente</TableHead>
                                <TableHead>Cotización Origen</TableHead>
                                <TableHead>Fecha</TableHead>
                                <TableHead>Condición</TableHead>
                                <TableHead className="text-right">Total</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead className="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {sales.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={8} className="text-center text-muted-foreground py-8">
                                        <ShoppingBag className="mx-auto size-8 mb-2 opacity-50" />
                                        No se encontraron ventas.
                                    </TableCell>
                                </TableRow>
                            )}

                            {sales.data.map((sale) => (
                                <TableRow key={sale.id}>
                                    <TableCell className="font-medium">{sale.numero}</TableCell>
                                    <TableCell>
                                        <div className="font-medium">{sale.client?.razon_social ?? '-'}</div>
                                        <div className="text-xs text-muted-foreground">{sale.client?.numero_documento}</div>
                                    </TableCell>
                                    <TableCell>{sale.quote ? sale.quote.numero : '-'}</TableCell>
                                    <TableCell>{sale.fecha}</TableCell>
                                    <TableCell className="capitalize">{sale.condicion_pago}</TableCell>
                                    <TableCell className="text-right font-semibold">{formatCurrency(sale.total)}</TableCell>
                                    <TableCell>
                                        <Badge variant={ESTADO_BADGE_VARIANT[sale.estado]}>{sale.estado.toUpperCase()}</Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger asChild>
                                                <Button variant="ghost" size="icon">
                                                    <MoreHorizontal className="size-4" />
                                                    <span className="sr-only">Abrir acciones</span>
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem asChild>
                                                    <Link href={route('sales.show', sale.id)}>
                                                        <Eye className="mr-2 size-4" />
                                                        Ver
                                                    </Link>
                                                </DropdownMenuItem>
                                                <DropdownMenuItem disabled={!hasInternalPdf}>
                                                    <FileText className="mr-2 size-4" />
                                                    Ver PDF
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {sales.last_page > 1 && (
                    <div className="flex flex-wrap items-center justify-center gap-1 pt-2">
                        {sales.links.map((link, index) => (
                            <Button
                                key={index}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
