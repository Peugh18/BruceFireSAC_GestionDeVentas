import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated, type SharedData } from '@/types';
import { type Quote, type QuoteEstado } from '@/types/quote';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Eye, FileText, Plus, Search } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Cotizaciones', href: route('quotes.index') }];

const ESTADO_BADGE_VARIANT: Record<QuoteEstado, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    borrador: 'outline',
    emitida: 'secondary',
    enviada: 'secondary',
    aceptada: 'default',
    rechazada: 'destructive',
    vencida: 'destructive',
    convertida: 'default',
    anulada: 'destructive',
};

const ESTADO_LABEL: Record<QuoteEstado, string> = {
    borrador: 'Borrador',
    emitida: 'Emitida',
    enviada: 'Enviada',
    aceptada: 'Aceptada',
    rechazada: 'Rechazada',
    vencida: 'Vencida',
    convertida: 'Convertida a Venta',
    anulada: 'Anulada',
};

const formatCurrency = (amount: number | string) => {
    const numeric = typeof amount === 'string' ? parseFloat(amount) : amount;
    return new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(numeric || 0);
};

export default function QuotesIndex({
    quotes,
    filters,
    status,
}: {
    quotes: Paginated<Quote>;
    filters: { search: string; estado: string };
    status?: string;
}) {
    const { auth } = usePage<SharedData>().props;
    const canCreate = auth.permissions.includes('quotes.create');

    const [search, setSearch] = useState(filters.search ?? '');
    const [estado, setEstado] = useState(filters.estado || 'todos');

    const applyFilters = (nextSearch: string, nextEstado: string) => {
        router.get(
            route('quotes.index'),
            {
                search: nextSearch || undefined,
                estado: nextEstado === 'todos' ? undefined : nextEstado,
            },
            { preserveState: true, replace: true },
        );
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilters(search, estado);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cotizaciones" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <HeadingSmall title="Cotizaciones" description="Administra la emision, seguimiento y conversion de cotizaciones." />

                    {canCreate && (
                        <Button asChild>
                            <Link href={route('quotes.create')}>
                                <Plus className="size-4" />
                                Nueva cotización
                            </Link>
                        </Button>
                    )}
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
                        value={estado}
                        onValueChange={(value) => {
                            setEstado(value);
                            applyFilters(search, value);
                        }}
                    >
                        <SelectTrigger className="sm:w-48">
                            <SelectValue placeholder="Estado" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="todos">Todos los estados</SelectItem>
                            <SelectItem value="borrador">Borrador</SelectItem>
                            <SelectItem value="emitida">Emitida</SelectItem>
                            <SelectItem value="enviada">Enviada</SelectItem>
                            <SelectItem value="aceptada">Aceptada</SelectItem>
                            <SelectItem value="rechazada">Rechazada</SelectItem>
                            <SelectItem value="vencida">Vencida</SelectItem>
                            <SelectItem value="convertida">Convertida</SelectItem>
                            <SelectItem value="anulada">Anulada</SelectItem>
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
                                <TableHead>Fecha</TableHead>
                                <TableHead>Vigencia</TableHead>
                                <TableHead>Condición</TableHead>
                                <TableHead className="text-right">Total</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead className="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {quotes.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={8} className="text-center text-muted-foreground py-8">
                                        <FileText className="mx-auto size-8 mb-2 opacity-50" />
                                        No se encontraron cotizaciones.
                                    </TableCell>
                                </TableRow>
                            )}

                            {quotes.data.map((quote) => (
                                <TableRow key={quote.id}>
                                    <TableCell className="font-medium">{quote.numero}</TableCell>
                                    <TableCell>
                                        <div className="font-medium">{quote.client?.razon_social ?? '-'}</div>
                                        <div className="text-xs text-muted-foreground">{quote.client?.numero_documento}</div>
                                    </TableCell>
                                    <TableCell>{quote.fecha}</TableCell>
                                    <TableCell>{quote.vigencia}</TableCell>
                                    <TableCell className="capitalize">{quote.condicion_propuesta}</TableCell>
                                    <TableCell className="text-right font-semibold">{formatCurrency(quote.total)}</TableCell>
                                    <TableCell>
                                        <Badge variant={ESTADO_BADGE_VARIANT[quote.estado]}>{ESTADO_LABEL[quote.estado]}</Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button asChild variant="ghost" size="sm">
                                            <Link href={route('quotes.show', quote.id)}>
                                                <Eye className="size-4 mr-1" /> Ver
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {quotes.last_page > 1 && (
                    <div className="flex flex-wrap items-center justify-center gap-1 pt-2">
                        {quotes.links.map((link, index) => (
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
