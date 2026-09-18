import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type Paginated } from '@/types';
import { collectionStatusLabels, collectionStatusVariants, formatCollectionAmount, type CollectionDocument } from '@/types/collection';
import { Head, Link, useForm } from '@inertiajs/react';
import { Wallet } from 'lucide-react';
import { type FormEventHandler } from 'react';

interface Props {
    sales: Paginated<CollectionDocument>;
    summary: { total_por_cobrar: string; vencido: string; vence_esta_semana: string; cobrado_este_mes: string };
    filters: { search: string; estado: string };
    canRegisterPayment: boolean;
}

export default function CollectionsIndex({ sales, summary, filters, canRegisterPayment }: Props) {
    const { data, setData, get, errors, processing } = useForm({ search: filters.search, estado: filters.estado });
    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        get(route('collections.index'), { preserveState: true, preserveScroll: true });
    };
    const cards = [
        { label: 'Total por cobrar', amount: summary.total_por_cobrar, description: 'Saldo de las cuotas pendientes' },
        { label: 'Vencido', amount: summary.vencido, description: 'Cuotas vencidas antes de hoy' },
        { label: 'Vence esta semana', amount: summary.vence_esta_semana, description: 'Desde hoy hasta el domingo' },
        { label: 'Cobrado este mes', amount: summary.cobrado_este_mes, description: 'Pagos al contado y abonos a crédito' },
    ];

    return (
        <AppLayout breadcrumbs={[{ title: 'Cobranzas', href: route('collections.index') }]}>
            <Head title="Cobranzas" />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <HeadingSmall title="Cobranzas" description="Consulta los documentos pendientes y registra abonos por cuota." />
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    {cards.map((card) => (
                        <Card key={card.label}>
                            <CardHeader className="pb-3">
                                <CardTitle className="text-muted-foreground text-sm font-medium">{card.label}</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="text-2xl font-semibold tabular-nums">{formatCollectionAmount(card.amount)}</p>
                                <p className="text-muted-foreground mt-2 text-xs">{card.description}</p>
                            </CardContent>
                        </Card>
                    ))}
                </div>
                <p className="text-muted-foreground text-xs">Resumen general de ventas vigentes. Los filtros se aplican al listado.</p>
                <form onSubmit={submit} className="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div className="grid flex-1 gap-2">
                        <Label htmlFor="collection-search">Buscar documento o cliente</Label>
                        <Input
                            id="collection-search"
                            value={data.search}
                            maxLength={255}
                            placeholder="Número de venta, cliente o documento"
                            onChange={(event) => setData('search', event.target.value)}
                        />
                        <InputError message={errors.search} />
                    </div>
                    <div className="grid gap-2 sm:w-52">
                        <Label htmlFor="collection-status">Estado</Label>
                        <Select value={data.estado || 'todos'} onValueChange={(value) => setData('estado', value === 'todos' ? '' : value)}>
                            <SelectTrigger id="collection-status">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todos">Todos los pendientes</SelectItem>
                                <SelectItem value="pendiente">Pendiente</SelectItem>
                                <SelectItem value="parcial">Parcial</SelectItem>
                                <SelectItem value="vencida">Vencida</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError message={errors.estado} />
                    </div>
                    <Button type="submit" variant="secondary" disabled={processing}>
                        {processing ? 'Buscando...' : 'Filtrar'}
                    </Button>
                    <Button variant="ghost" asChild>
                        <Link href={route('collections.index')}>Limpiar</Link>
                    </Button>
                </form>
                {sales.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed px-6 py-16 text-center">
                        <Wallet className="text-muted-foreground size-8" aria-hidden="true" />
                        <h2 className="font-medium">No hay documentos pendientes para mostrar</h2>
                        <p className="text-muted-foreground text-sm">Las ventas saldadas dejan de aparecer aquí. Prueba otros filtros.</p>
                    </div>
                ) : (
                    <>
                        <div className="hidden rounded-lg border md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        {['Documento / Cliente', 'Total', 'Saldo', 'Vencimiento', 'Cuotas pendientes', 'Estado', 'Acciones'].map(
                                            (label) => (
                                                <TableHead key={label}>{label}</TableHead>
                                            ),
                                        )}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {sales.data.map((sale) => (
                                        <TableRow key={sale.id}>
                                            <TableCell>
                                                <Link className="font-medium hover:underline" href={route('collections.show', sale.id)}>
                                                    {sale.numero}
                                                </Link>
                                                <p>{sale.client.razon_social}</p>
                                                <p className="text-muted-foreground text-xs">{sale.client.numero_documento}</p>
                                            </TableCell>
                                            <TableCell className="whitespace-nowrap tabular-nums">{formatCollectionAmount(sale.total)}</TableCell>
                                            <TableCell className="font-semibold whitespace-nowrap tabular-nums">
                                                {formatCollectionAmount(sale.saldo)}
                                            </TableCell>
                                            <TableCell>{sale.vencimiento}</TableCell>
                                            <TableCell>
                                                {sale.cuotas_pendientes} de {sale.cuotas}
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={collectionStatusVariants[sale.estado]}>{collectionStatusLabels[sale.estado]}</Badge>
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex flex-wrap gap-2">
                                                    <Button size="sm" variant="ghost" asChild>
                                                        <Link href={route('collections.show', sale.id)}>Ver detalle</Link>
                                                    </Button>
                                                    {canRegisterPayment && (
                                                        <Button size="sm" variant="outline" asChild>
                                                            <Link href={route('collections.show', { sale: sale.id, pagar: 1 })}>Registrar pago</Link>
                                                        </Button>
                                                    )}
                                                </div>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                        <div className="grid gap-3 md:hidden">
                            {sales.data.map((sale) => (
                                <Card key={sale.id}>
                                    <CardHeader className="pb-3">
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <CardTitle className="text-base">{sale.numero}</CardTitle>
                                            <Badge variant={collectionStatusVariants[sale.estado]}>{collectionStatusLabels[sale.estado]}</Badge>
                                        </div>
                                        <p className="text-sm">{sale.client.razon_social}</p>
                                    </CardHeader>
                                    <CardContent className="space-y-3">
                                        <p className="text-lg font-semibold">Saldo: {formatCollectionAmount(sale.saldo)}</p>
                                        <p className="text-muted-foreground text-sm">
                                            Total: {formatCollectionAmount(sale.total)} · {sale.cuotas_pendientes} de {sale.cuotas} cuotas pendientes
                                        </p>
                                        <p className="text-sm">Vencimiento: {sale.vencimiento}</p>
                                        <div className="flex flex-wrap gap-2">
                                            <Button variant="outline" size="sm" asChild>
                                                <Link href={route('collections.show', sale.id)}>Ver detalle</Link>
                                            </Button>
                                            {canRegisterPayment && (
                                                <Button size="sm" asChild>
                                                    <Link href={route('collections.show', { sale: sale.id, pagar: 1 })}>Registrar pago</Link>
                                                </Button>
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </>
                )}
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <p className="text-muted-foreground text-sm">
                        {sales.total} documentos · Página {sales.current_page} de {sales.last_page}
                    </p>
                    {sales.last_page > 1 && (
                        <nav aria-label="Paginación de cobranzas" className="flex flex-wrap gap-1">
                            {sales.links.map((link, index) => (
                                <Button key={index} size="sm" asChild={!!link.url} disabled={!link.url} variant={link.active ? 'default' : 'outline'}>
                                    {link.url ? (
                                        <Link href={link.url} dangerouslySetInnerHTML={{ __html: link.label }} />
                                    ) : (
                                        <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                    )}
                                </Button>
                            ))}
                        </nav>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
