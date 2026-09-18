import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { type Quote, type QuoteEstado } from '@/types/quote';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, ChevronDown, Copy, Edit, ShoppingBag } from 'lucide-react';

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

export default function QuoteShow({ quote, status }: { quote: Quote; status?: string }) {
    const { auth } = usePage<SharedData>().props;
    const canUpdate = auth.permissions.includes('quotes.update');
    const canCreate = auth.permissions.includes('quotes.create');
    const canConvert = auth.permissions.includes('quotes.convert');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Cotizaciones', href: route('quotes.index') },
        { title: quote.numero, href: route('quotes.show', quote.id) },
    ];

    const canEdit = canUpdate && ['borrador', 'emitida'].includes(quote.estado);
    const canBeConverted = canConvert && ['borrador', 'emitida', 'enviada', 'aceptada'].includes(quote.estado) && !quote.sale;

    const changeStatus = (nextEstado: string) => {
        router.patch(route('quotes.status', quote.id), { estado: nextEstado });
    };

    const duplicateQuote = () => {
        router.post(route('quotes.duplicate', quote.id));
    };

    const convertToSale = () => {
        if (confirm('¿Deseas convertir esta cotización en una venta oficial?')) {
            router.post(route('quotes.convert', quote.id));
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Cotización ${quote.numero}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div className="flex items-center gap-4">
                        <Button asChild variant="outline" size="icon">
                            <Link href={route('quotes.index')}>
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                        <div>
                            <div className="flex items-center gap-3">
                                <HeadingSmall title={`Cotización ${quote.numero}`} description={`Emitida el ${quote.fecha}`} />
                                <Badge variant={ESTADO_BADGE_VARIANT[quote.estado]}>{ESTADO_LABEL[quote.estado]}</Badge>
                            </div>
                        </div>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        {canCreate && (
                            <Button variant="outline" size="sm" onClick={duplicateQuote}>
                                <Copy className="size-4 mr-1" /> Duplicar
                            </Button>
                        )}

                        {canEdit && (
                            <Button asChild variant="outline" size="sm">
                                <Link href={route('quotes.edit', quote.id)}>
                                    <Edit className="size-4 mr-1" /> Editar
                                </Link>
                            </Button>
                        )}

                        {canUpdate && quote.estado !== 'convertida' && (
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="secondary" size="sm">
                                        Cambiar estado <ChevronDown className="size-4 ml-1" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem onClick={() => changeStatus('emitida')}>Marcar Emitida</DropdownMenuItem>
                                    <DropdownMenuItem onClick={() => changeStatus('enviada')}>Marcar Enviada</DropdownMenuItem>
                                    <DropdownMenuItem onClick={() => changeStatus('aceptada')}>Marcar Aceptada</DropdownMenuItem>
                                    <DropdownMenuItem onClick={() => changeStatus('rechazada')}>Marcar Rechazada</DropdownMenuItem>
                                    <DropdownMenuItem onClick={() => changeStatus('anulada')}>Marcar Anulada</DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        )}

                        {canBeConverted && (
                            <Button onClick={convertToSale} size="sm">
                                <ShoppingBag className="size-4 mr-1" /> Convertir a venta
                            </Button>
                        )}
                    </div>
                </div>

                {status && (
                    <div role="status" className="rounded-lg border bg-muted/50 px-4 py-3 text-sm text-foreground flex items-center gap-2">
                        <CheckCircle2 className="size-4 text-emerald-600" />
                        {status}
                    </div>
                )}

                <div className="grid gap-6 md:grid-cols-3">
                    <Card className="md:col-span-2">
                        <CardHeader>
                            <CardTitle>Detalle del Cliente y Documento</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <h4 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Cliente</h4>
                                <p className="font-medium text-base mt-1">{quote.client?.razon_social}</p>
                                <p className="text-sm text-muted-foreground">{quote.client?.tipo_documento.toUpperCase()}: {quote.client?.numero_documento}</p>
                                {quote.client?.telefono && <p className="text-sm text-muted-foreground">Teléfono: {quote.client.telefono}</p>}
                                {quote.client?.email && <p className="text-sm text-muted-foreground">Email: {quote.client.email}</p>}
                            </div>

                            <div>
                                <h4 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Sede y Vehículo</h4>
                                <p className="text-sm mt-1 font-medium">{quote.site ? `${quote.site.nombre} (${quote.site.direccion})` : 'Sede principal / No especificada'}</p>
                                {quote.vehicle && (
                                    <p className="text-sm text-muted-foreground mt-1">
                                        Vehículo: <span className="font-medium text-foreground">{quote.vehicle.placa}</span> ({quote.vehicle.marca} {quote.vehicle.modelo})
                                    </p>
                                )}
                            </div>

                            <div>
                                <h4 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Vendedor</h4>
                                <p className="text-sm font-medium mt-1">{quote.vendedor?.name ?? 'Vendedor asignado'}</p>
                            </div>

                            <div>
                                <h4 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Condición propuesta</h4>
                                <p className="text-sm font-medium mt-1 capitalize">{quote.condicion_propuesta}</p>
                                <p className="text-xs text-muted-foreground">Vigencia hasta: {quote.vigencia}</p>
                            </div>

                            {quote.observaciones && (
                                <div className="sm:col-span-2">
                                    <h4 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Observaciones</h4>
                                    <p className="text-sm mt-1 whitespace-pre-wrap">{quote.observaciones}</p>
                                </div>
                            )}

                            {quote.sale && (
                                <div className="sm:col-span-2 rounded-lg border bg-muted/40 p-3 flex items-center justify-between">
                                    <div>
                                        <p className="text-xs font-semibold text-muted-foreground">Venta Generada</p>
                                        <p className="text-sm font-medium">{quote.sale.numero}</p>
                                    </div>
                                    <Button asChild variant="outline" size="sm">
                                        <Link href={route('sales.show', quote.sale.id)}>Ver Venta</Link>
                                    </Button>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Resumen Económico</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            <div className="flex justify-between text-sm">
                                <span className="text-muted-foreground">Subtotal</span>
                                <span className="font-medium">{formatCurrency(quote.subtotal)}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-muted-foreground">IGV (18%)</span>
                                <span className="font-medium">{formatCurrency(quote.igv)}</span>
                            </div>
                            <div className="flex justify-between text-base font-semibold border-t pt-2">
                                <span>Total</span>
                                <span>{formatCurrency(quote.total)}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Ítems Cotizados</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Código</TableHead>
                                    <TableHead>Descripción / Producto</TableHead>
                                    <TableHead className="text-right">Cantidad</TableHead>
                                    <TableHead className="text-right">Precio Unit.</TableHead>
                                    <TableHead className="text-right">Descuento</TableHead>
                                    <TableHead className="text-right">Subtotal</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {(quote.items ?? []).map((item) => (
                                    <TableRow key={item.id}>
                                        <TableCell className="font-mono text-xs">{item.catalog_item?.codigo}</TableCell>
                                        <TableCell>
                                            <div className="font-medium">{item.catalog_item?.nombre}</div>
                                            <div className="text-xs text-muted-foreground capitalize">{item.catalog_item?.tipo}</div>
                                        </TableCell>
                                        <TableCell className="text-right">{item.cantidad}</TableCell>
                                        <TableCell className="text-right">{formatCurrency(item.precio_unitario)}</TableCell>
                                        <TableCell className="text-right">{formatCurrency(item.descuento)}</TableCell>
                                        <TableCell className="text-right font-medium">{formatCurrency(item.subtotal)}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
