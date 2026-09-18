import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { type ElectronicDocumentData, type ElectronicDocumentEstado } from '@/types/electronic-document';
import { type InstallmentEstado, type Sale, type SaleEstado } from '@/types/sale';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { AlertCircle, ArrowLeft, CheckCircle2, Clock, RefreshCw, ShieldAlert, XCircle } from 'lucide-react';

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

const DOCUMENT_BADGE_VARIANT: Record<ElectronicDocumentEstado, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    pendiente: 'secondary',
    aceptado: 'default',
    rechazado: 'destructive',
    error: 'destructive',
};

const DOCUMENT_ICON: Record<ElectronicDocumentEstado, typeof CheckCircle2> = {
    pendiente: Clock,
    aceptado: CheckCircle2,
    rechazado: XCircle,
    error: AlertCircle,
};

const formatCurrency = (amount: number | string) => {
    const numeric = typeof amount === 'string' ? parseFloat(amount) : amount;
    return new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(numeric || 0);
};

function BillingCard({
    sale,
    electronicDocument,
    tipoLabels,
    estadoLabels,
}: {
    sale: Sale;
    electronicDocument: ElectronicDocumentData | null;
    tipoLabels: Record<string, string>;
    estadoLabels: Record<string, string>;
}) {
    const { auth } = usePage<SharedData>().props;
    const canIssue = auth.permissions.includes('billing.issue');
    const canRetry = auth.permissions.includes('billing.retry');

    const issueForm = useForm({});
    const retryForm = useForm({});

    const issue = () => {
        issueForm.post(route('billing.issue', sale.id), { preserveScroll: true });
    };

    const retry = () => {
        if (!electronicDocument) return;
        retryForm.post(route('billing.retry', electronicDocument.id), { preserveScroll: true });
    };

    const refresh = () => {
        router.reload({ only: ['electronicDocument'] });
    };

    if (!electronicDocument) {
        return (
            <Card>
                <CardHeader>
                    <CardTitle>Comprobante Electrónico</CardTitle>
                </CardHeader>
                <CardContent className="space-y-3">
                    <p className="text-sm text-muted-foreground">
                        Esta venta aun no tiene un comprobante electronico. Se emitira{' '}
                        <span className="font-medium text-foreground">
                            {sale.client?.tipo_documento === 'ruc' ? 'factura' : 'boleta'}
                        </span>{' '}
                        segun el tipo de documento del cliente.
                    </p>
                    {canIssue ? (
                        <Button onClick={issue} disabled={issueForm.processing} className="w-full">
                            {issueForm.processing ? 'Enviando a cola...' : 'Emitir comprobante'}
                        </Button>
                    ) : (
                        <p className="text-xs text-muted-foreground">No tienes permiso para emitir comprobantes.</p>
                    )}
                </CardContent>
            </Card>
        );
    }

    const Icon = DOCUMENT_ICON[electronicDocument.estado];

    return (
        <Card>
            <CardHeader className="flex flex-row items-center justify-between gap-3">
                <CardTitle>Comprobante Electrónico</CardTitle>
                <Badge variant={DOCUMENT_BADGE_VARIANT[electronicDocument.estado]} className="gap-1.5">
                    <Icon className="size-3.5" />
                    {estadoLabels[electronicDocument.estado] ?? electronicDocument.estado}
                </Badge>
            </CardHeader>
            <CardContent className="space-y-3">
                <dl className="divide-y">
                    <div className="grid grid-cols-3 gap-2 py-2 text-sm">
                        <dt className="text-muted-foreground">Tipo</dt>
                        <dd className="col-span-2">{tipoLabels[electronicDocument.tipo] ?? electronicDocument.tipo}</dd>
                    </div>
                    <div className="grid grid-cols-3 gap-2 py-2 text-sm">
                        <dt className="text-muted-foreground">Numero</dt>
                        <dd className="col-span-2 font-mono">
                            {electronicDocument.serie}-{electronicDocument.correlativo}
                        </dd>
                    </div>
                    <div className="grid grid-cols-3 gap-2 py-2 text-sm">
                        <dt className="text-muted-foreground">Intentos</dt>
                        <dd className="col-span-2">{electronicDocument.intentos}</dd>
                    </div>
                    {electronicDocument.fecha_envio && (
                        <div className="grid grid-cols-3 gap-2 py-2 text-sm">
                            <dt className="text-muted-foreground">Ultimo envio</dt>
                            <dd className="col-span-2">{new Date(electronicDocument.fecha_envio).toLocaleString('es-PE')}</dd>
                        </div>
                    )}
                </dl>

                {electronicDocument.estado === 'pendiente' && (
                    <div className="flex items-center gap-2 rounded-lg border bg-muted/50 px-3 py-2 text-sm">
                        <Clock className="size-4 shrink-0 text-muted-foreground" />
                        En cola de envio a SUNAT. Actualiza para ver el resultado.
                    </div>
                )}

                {electronicDocument.respuesta_sunat && (
                    <div className="rounded-lg border px-3 py-2 text-sm">
                        <p className="text-xs font-medium text-muted-foreground">Respuesta SUNAT</p>
                        <p className="mt-1 break-words">{electronicDocument.respuesta_sunat}</p>
                    </div>
                )}

                {electronicDocument.error && (
                    <div className="flex items-start gap-2 rounded-lg border border-destructive/50 bg-destructive/5 px-3 py-2 text-sm">
                        <ShieldAlert className="mt-0.5 size-4 shrink-0 text-destructive" />
                        <p className="break-words text-destructive">{electronicDocument.error}</p>
                    </div>
                )}

                <div className="flex gap-2">
                    <Button variant="outline" size="sm" onClick={refresh} className="gap-1.5">
                        <RefreshCw className="size-3.5" /> Actualizar estado
                    </Button>
                    {electronicDocument.estado === 'error' && canRetry && (
                        <Button size="sm" onClick={retry} disabled={retryForm.processing}>
                            {retryForm.processing ? 'Reintentando...' : 'Reintentar envio'}
                        </Button>
                    )}
                </div>
            </CardContent>
        </Card>
    );
}

export default function SaleShow({
    sale,
    electronicDocument,
    tipoLabels,
    estadoLabels,
    status,
}: {
    sale: Sale;
    electronicDocument: ElectronicDocumentData | null;
    tipoLabels: Record<string, string>;
    estadoLabels: Record<string, string>;
    status?: string;
}) {
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
                        <CheckCircle2 className="size-4 text-success" />
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

                    <BillingCard sale={sale} electronicDocument={electronicDocument} tipoLabels={tipoLabels} estadoLabels={estadoLabels} />
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
