import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type InstallmentEstado, type Sale, type SaleEstado } from '@/types/sale';
import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2 } from 'lucide-react';

const ESTADO_BADGE_VARIANT: Record<SaleEstado, 'default' | 'secondary' | 'destructive'> = {
    pendiente: 'secondary',
    completada: 'default',
    anulada: 'destructive',
};

const INSTALLMENT_BADGE_VARIANT: Record<InstallmentEstado, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    pendiente: 'outline',
    pagado_parcial: 'secondary',
    pagado: 'default',
    vencido: 'destructive',
};

const formatCurrency = (amount: number | string) => {
    const numeric = typeof amount === 'string' ? parseFloat(amount) : amount;
    return new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(numeric || 0);
};

export default function SaleShow({ sale, status }: { sale: Sale; status?: string }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Ventas', href: route('sales.index') },
        { title: sale.numero, href: route('sales.show', sale.id) },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Venta ${sale.numero}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center justify-between gap-4">
                    <div className="flex items-center gap-4">
                        <Button asChild variant="outline" size="icon">
                            <Link href={route('sales.index')}>
                                <ArrowLeft className="size-4" />
                            </Link>
                        </Button>
                        <div>
                            <div className="flex items-center gap-3">
                                <HeadingSmall title={`Venta ${sale.numero}`} description={`Registrada el ${sale.fecha}`} />
                                <Badge variant={ESTADO_BADGE_VARIANT[sale.estado]}>{sale.estado.toUpperCase()}</Badge>
                            </div>
                        </div>
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
                            <CardTitle>Detalles Comerciales del Cliente</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2">
                            <div>
                                <h4 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Cliente</h4>
                                <p className="font-medium text-base mt-1">{sale.client?.razon_social}</p>
                                <p className="text-sm text-muted-foreground">{sale.client?.tipo_documento.toUpperCase()}: {sale.client?.numero_documento}</p>
                                {sale.client?.telefono && <p className="text-sm text-muted-foreground">Teléfono: {sale.client.telefono}</p>}
                                {sale.client?.email && <p className="text-sm text-muted-foreground">Email: {sale.client.email}</p>}
                            </div>

                            <div>
                                <h4 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Sede y Vehículo</h4>
                                <p className="text-sm mt-1 font-medium">{sale.site ? `${sale.site.nombre} (${sale.site.direccion})` : 'Sede principal / No especificada'}</p>
                                {sale.vehicle && (
                                    <p className="text-sm text-muted-foreground mt-1">
                                        Vehículo: <span className="font-medium text-foreground">{sale.vehicle.placa}</span> ({sale.vehicle.marca} {sale.vehicle.modelo})
                                    </p>
                                )}
                            </div>

                            <div>
                                <h4 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Vendedor</h4>
                                <p className="text-sm font-medium mt-1">{sale.vendedor?.name ?? 'Vendedor asignado'}</p>
                            </div>

                            <div>
                                <h4 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Condición de Pago</h4>
                                <p className="text-sm font-medium mt-1 capitalize">{sale.condicion_pago}</p>
                                {sale.quote && (
                                    <p className="text-xs text-muted-foreground mt-1">
                                        Cotización Origen: <Link href={route('quotes.show', sale.quote.id)} className="font-medium text-foreground underline">{sale.quote.numero}</Link>
                                    </p>
                                )}
                            </div>

                            {sale.observaciones && (
                                <div className="sm:col-span-2">
                                    <h4 className="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Observaciones</h4>
                                    <p className="text-sm mt-1 whitespace-pre-wrap">{sale.observaciones}</p>
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
                                <span className="font-medium">{formatCurrency(sale.subtotal)}</span>
                            </div>
                            <div className="flex justify-between text-sm">
                                <span className="text-muted-foreground">IGV (18%)</span>
                                <span className="font-medium">{formatCurrency(sale.igv)}</span>
                            </div>
                            <div className="flex justify-between text-base font-semibold border-t pt-2">
                                <span>Total</span>
                                <span>{formatCurrency(sale.total)}</span>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Ítems de la Venta</CardTitle>
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
                                {(sale.items ?? []).map((item) => (
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

                {sale.condicion_pago === 'contado' && (sale.payments ?? []).length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Pagos Registrados (Contado)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Forma de Pago</TableHead>
                                        <TableHead>Referencia / Operación</TableHead>
                                        <TableHead className="text-right">Monto</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {sale.payments!.map((payment) => (
                                        <TableRow key={payment.id}>
                                            <TableCell className="font-medium capitalize">{payment.forma_pago}</TableCell>
                                            <TableCell>{payment.referencia ?? '-'}</TableCell>
                                            <TableCell className="text-right font-medium">{formatCurrency(payment.monto)}</TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                {sale.condicion_pago === 'credito' && (sale.installments ?? []).length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Cronograma de Cuotas (Crédito)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Cuota #</TableHead>
                                        <TableHead>Monto Total</TableHead>
                                        <TableHead>Monto Pendiente</TableHead>
                                        <TableHead>Fecha Vencimiento</TableHead>
                                        <TableHead>Estado</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {sale.installments!.map((inst) => (
                                        <TableRow key={inst.id}>
                                            <TableCell className="font-medium">Cuota {inst.numero_cuota}</TableCell>
                                            <TableCell>{formatCurrency(inst.monto)}</TableCell>
                                            <TableCell>{formatCurrency(inst.monto_pendiente)}</TableCell>
                                            <TableCell>{inst.fecha_vencimiento}</TableCell>
                                            <TableCell>
                                                <Badge variant={INSTALLMENT_BADGE_VARIANT[inst.estado]}>{inst.estado.replace('_', ' ').toUpperCase()}</Badge>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
