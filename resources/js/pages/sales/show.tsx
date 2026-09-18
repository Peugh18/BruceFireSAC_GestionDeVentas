import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { type CreditDebitNoteEstado, type CreditDebitNoteTipo } from '@/types/credit-debit-note';
import { type ElectronicDocumentData, type ElectronicDocumentEstado } from '@/types/electronic-document';
import { type InstallmentEstado, type Sale, type SaleEstado } from '@/types/sale';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { AlertCircle, ArrowLeft, CheckCircle2, Clock, Download, FileMinus, MessageCircle, Printer, RefreshCw, ShieldAlert, XCircle } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

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

const NOTE_BADGE_VARIANT: Record<CreditDebitNoteEstado, 'default' | 'secondary' | 'destructive'> = {
    pendiente: 'secondary',
    aceptado: 'default',
    rechazado: 'destructive',
    error: 'destructive',
};

const formatCurrency = (amount: number | string) => {
    const numeric = typeof amount === 'string' ? parseFloat(amount) : amount;
    return new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(numeric || 0);
};

function CreditDebitNoteDialog({
    electronicDocument,
    noteTipoLabels,
    noteMotivoLabels,
    open,
    onOpenChange,
}: {
    electronicDocument: ElectronicDocumentData;
    noteTipoLabels: Record<string, string>;
    noteMotivoLabels: Record<string, Record<string, string>>;
    open: boolean;
    onOpenChange: (open: boolean) => void;
}) {
    const { data, setData, post, processing, errors, reset } = useForm({
        tipo: 'nota_credito' as CreditDebitNoteTipo,
        motivo: '',
        detalle: '',
        importe: '',
        fecha: new Date().toISOString().slice(0, 10),
    });

    const motivoOptions = noteMotivoLabels[data.tipo] ?? {};

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('billing.credit-debit-notes.store', electronicDocument.id), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Emitir Nota de Crédito/Débito</DialogTitle>
                    </DialogHeader>

                    <div className="space-y-2">
                        <Label htmlFor="note-tipo">Tipo</Label>
                        <Select
                            value={data.tipo}
                            onValueChange={(value: CreditDebitNoteTipo) => {
                                setData((prev) => ({ ...prev, tipo: value, motivo: '' }));
                            }}
                        >
                            <SelectTrigger id="note-tipo">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(noteTipoLabels).map(([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.tipo} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="note-motivo">Motivo</Label>
                        <Select value={data.motivo} onValueChange={(value) => setData('motivo', value)}>
                            <SelectTrigger id="note-motivo">
                                <SelectValue placeholder="Selecciona un motivo" />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(motivoOptions).map(([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.motivo} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="note-detalle">Detalle</Label>
                        <Textarea
                            id="note-detalle"
                            value={data.detalle}
                            onChange={(e) => setData('detalle', e.target.value)}
                            placeholder="Explica el motivo de la nota"
                            rows={3}
                        />
                        <InputError message={errors.detalle} />
                    </div>

                    <div className="grid gap-4 sm:grid-cols-2">
                        <div className="space-y-2">
                            <Label htmlFor="note-importe">Importe (S/)</Label>
                            <Input
                                id="note-importe"
                                type="number"
                                min="0.01"
                                step="0.01"
                                value={data.importe}
                                onChange={(e) => setData('importe', e.target.value)}
                            />
                            <InputError message={errors.importe} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="note-fecha">Fecha</Label>
                            <Input id="note-fecha" type="date" value={data.fecha} onChange={(e) => setData('fecha', e.target.value)} />
                            <InputError message={errors.fecha} />
                        </div>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Enviando...' : 'Emitir y enviar a SUNAT'}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function BillingCard({
    sale,
    electronicDocument,
    tipoLabels,
    estadoLabels,
    noteTipoLabels,
    noteEstadoLabels,
    noteMotivoLabels,
}: {
    sale: Sale;
    electronicDocument: ElectronicDocumentData | null;
    tipoLabels: Record<string, string>;
    estadoLabels: Record<string, string>;
    noteTipoLabels: Record<string, string>;
    noteEstadoLabels: Record<string, string>;
    noteMotivoLabels: Record<string, Record<string, string>>;
}) {
    const { auth } = usePage<SharedData>().props;
    const canIssue = auth.permissions.includes('billing.issue');
    const canRetry = auth.permissions.includes('billing.retry');
    const canCreateNote = auth.permissions.includes('billing.credit_note');
    const [noteDialogOpen, setNoteDialogOpen] = useState(false);

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

    const rawPhone = (sale.client?.telefono || '').replace(/\D/g, '');
    const clientPhone = rawPhone.length === 9 ? `51${rawPhone}` : rawPhone;
    const docTitle = tipoLabels[electronicDocument.tipo] ?? (electronicDocument.tipo === 'factura' ? 'Factura' : 'Boleta');
    const docNumber = `${electronicDocument.serie}-${electronicDocument.correlativo}`;
    const waMessage = `Estimado(a) ${sale.client?.razon_social ?? 'cliente'}, le saludamos de BRUCE FIRE S.A.C. Le informamos que su comprobante electrónico (${docTitle} ${docNumber}) por un importe de ${formatCurrency(sale.total)} ha sido emitido con éxito.`;
    const whatsappUrl = clientPhone
        ? `https://wa.me/${clientPhone}?text=${encodeURIComponent(waMessage)}`
        : `https://wa.me/?text=${encodeURIComponent(waMessage)}`;

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

                <div className="flex flex-wrap gap-2">
                    <Button variant="outline" size="sm" onClick={refresh} className="gap-1.5">
                        <RefreshCw className="size-3.5" /> Actualizar estado
                    </Button>
                    {electronicDocument.estado === 'error' && canRetry && (
                        <Button size="sm" onClick={retry} disabled={retryForm.processing}>
                            {retryForm.processing ? 'Reintentando...' : 'Reintentar envio'}
                        </Button>
                    )}
                    {electronicDocument.estado === 'aceptado' && (
                        <>
                            <Button
                                variant="outline"
                                size="sm"
                                className="gap-1.5"
                                type="button"
                                onClick={() => alert('La descarga directa de PDF/XML no está disponible actualmente en el servidor.')}
                                title="Descarga de archivo no disponible en el servidor"
                            >
                                <Download className="size-3.5" /> Descargar
                            </Button>
                            <Button
                                variant="outline"
                                size="sm"
                                className="gap-1.5"
                                type="button"
                                onClick={() => window.print()}
                            >
                                <Printer className="size-3.5" /> Imprimir
                            </Button>
                            <Button
                                asChild
                                variant="outline"
                                size="sm"
                                className="gap-1.5 text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 dark:hover:bg-emerald-950/30"
                            >
                                <a href={whatsappUrl} target="_blank" rel="noopener noreferrer">
                                    <MessageCircle className="size-3.5" /> WhatsApp
                                </a>
                            </Button>
                        </>
                    )}
                    {electronicDocument.estado === 'aceptado' && canCreateNote && (
                        <Button variant="outline" size="sm" className="gap-1.5" onClick={() => setNoteDialogOpen(true)}>
                            <FileMinus className="size-3.5" /> Nota de crédito/débito
                        </Button>
                    )}
                </div>

                {electronicDocument.credit_debit_notes && electronicDocument.credit_debit_notes.length > 0 && (
                    <div className="space-y-2 border-t pt-3">
                        <p className="text-xs font-medium text-muted-foreground">Notas emitidas</p>
                        {electronicDocument.credit_debit_notes.map((note) => (
                            <div key={note.id} className="flex items-center justify-between gap-2 text-sm">
                                <div className="min-w-0">
                                    <p className="truncate font-medium">
                                        {noteTipoLabels[note.tipo] ?? note.tipo} {note.serie}-{note.correlativo}
                                    </p>
                                    <p className="truncate text-xs text-muted-foreground">{note.detalle}</p>
                                </div>
                                <Badge variant={NOTE_BADGE_VARIANT[note.estado]} className="shrink-0">
                                    {noteEstadoLabels[note.estado] ?? note.estado}
                                </Badge>
                            </div>
                        ))}
                    </div>
                )}
            </CardContent>

            {canCreateNote && (
                <CreditDebitNoteDialog
                    electronicDocument={electronicDocument}
                    noteTipoLabels={noteTipoLabels}
                    noteMotivoLabels={noteMotivoLabels}
                    open={noteDialogOpen}
                    onOpenChange={setNoteDialogOpen}
                />
            )}
        </Card>
    );
}

export default function SaleShow({
    sale,
    electronicDocument,
    tipoLabels,
    estadoLabels,
    noteTipoLabels,
    noteEstadoLabels,
    noteMotivoLabels,
    company,
    status,
}: {
    sale: Sale;
    electronicDocument: ElectronicDocumentData | null;
    tipoLabels: Record<string, string>;
    estadoLabels: Record<string, string>;
    noteTipoLabels: Record<string, string>;
    noteEstadoLabels: Record<string, string>;
    noteMotivoLabels: Record<string, Record<string, string>>;
    company: { ruc: string; razon_social: string; nombre_comercial: string };
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

                    <BillingCard
                        sale={sale}
                        electronicDocument={electronicDocument}
                        tipoLabels={tipoLabels}
                        estadoLabels={estadoLabels}
                        noteTipoLabels={noteTipoLabels}
                        noteEstadoLabels={noteEstadoLabels}
                        noteMotivoLabels={noteMotivoLabels}
                    />
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

            {/* Vista imprimible simple para window.print() */}
            {electronicDocument && electronicDocument.estado === 'aceptado' && (
                <div id="printable-invoice" className="hidden print:block text-black bg-white p-8 max-w-3xl mx-auto font-sans">
                    <style>{`
                        @media print {
                            body * {
                                visibility: hidden !important;
                            }
                            #printable-invoice, #printable-invoice * {
                                visibility: visible !important;
                            }
                            #printable-invoice {
                                position: absolute !important;
                                left: 0 !important;
                                top: 0 !important;
                                width: 100% !important;
                                margin: 0 !important;
                                padding: 24px !important;
                                background: white !important;
                                color: black !important;
                            }
                        }
                    `}</style>
                    <div className="flex justify-between items-start border-b pb-6 mb-6">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-gray-900">{company.razon_social}</h1>
                            <p className="text-xs text-gray-600 mt-1">{company.nombre_comercial}</p>
                            <p className="text-xs text-gray-600">Venta, recarga y mantenimiento de extintores</p>
                            <p className="text-xs text-gray-600">Lima, Perú</p>
                        </div>
                        <div className="border-2 border-gray-800 rounded-lg p-4 text-center min-w-[220px]">
                            <p className="text-sm font-bold tracking-wider">R.U.C. {company.ruc}</p>
                            <p className="text-base font-extrabold uppercase my-1">
                                {tipoLabels[electronicDocument.tipo] ?? electronicDocument.tipo.toUpperCase()} ELECTRÓNICA
                            </p>
                            <p className="text-lg font-mono font-bold">{electronicDocument.serie}-{electronicDocument.correlativo}</p>
                        </div>
                    </div>

                    <div className="grid grid-cols-2 gap-4 border rounded-md p-4 text-xs mb-6">
                        <div>
                            <p><span className="font-semibold">Señor(es):</span> {sale.client?.razon_social}</p>
                            <p className="mt-1"><span className="font-semibold">{sale.client?.tipo_documento?.toUpperCase() ?? 'DOC'}:</span> {sale.client?.numero_documento}</p>
                            <p className="mt-1"><span className="font-semibold">Dirección:</span> {sale.site ? `${sale.site.nombre} (${sale.site.direccion})` : (sale.client?.direccion_fiscal || '-')}</p>
                            {sale.vehicle && (
                                <p className="mt-1"><span className="font-semibold">Vehículo / Placa:</span> {sale.vehicle.placa} ({sale.vehicle.marca} {sale.vehicle.modelo})</p>
                            )}
                        </div>
                        <div>
                            <p><span className="font-semibold">Fecha de Emisión:</span> {sale.fecha}</p>
                            <p className="mt-1"><span className="font-semibold">Condición de Pago:</span> {sale.condicion_pago.toUpperCase()}</p>
                            <p className="mt-1"><span className="font-semibold">Moneda:</span> SOLES (PEN)</p>
                            <p className="mt-1"><span className="font-semibold">Venta N°:</span> {sale.numero}</p>
                        </div>
                    </div>

                    <table className="w-full text-xs border-collapse mb-6">
                        <thead>
                            <tr className="border-b-2 border-t border-gray-400 bg-gray-50 text-gray-700">
                                <th className="py-2 text-center w-12">Cant.</th>
                                <th className="py-2 text-left w-20">Código</th>
                                <th className="py-2 text-left">Descripción</th>
                                <th className="py-2 text-right w-24">P. Unit.</th>
                                <th className="py-2 text-right w-20">Desc.</th>
                                <th className="py-2 text-right w-24">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            {(sale.items ?? []).map((item) => (
                                <tr key={item.id} className="border-b border-gray-200">
                                    <td className="py-2 text-center">{item.cantidad}</td>
                                    <td className="py-2 font-mono text-gray-600">{item.catalog_item?.codigo}</td>
                                    <td className="py-2 font-medium">{item.catalog_item?.nombre}</td>
                                    <td className="py-2 text-right">{formatCurrency(item.precio_unitario)}</td>
                                    <td className="py-2 text-right">{formatCurrency(item.descuento)}</td>
                                    <td className="py-2 text-right font-medium">{formatCurrency(item.subtotal)}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    <div className="flex justify-between items-start text-xs border-t pt-4">
                        <div className="max-w-xs text-gray-500">
                            <p className="italic">Representación impresa del Comprobante de Pago Electrónico.</p>
                            {electronicDocument.fecha_envio && (
                                <p className="mt-1">Emitido ante SUNAT el {new Date(electronicDocument.fecha_envio).toLocaleString('es-PE')}</p>
                            )}
                        </div>
                        <div className="w-64 space-y-1 text-right">
                            <div className="flex justify-between py-1 border-b border-gray-100">
                                <span className="text-gray-600">Op. Gravada / Subtotal:</span>
                                <span className="font-semibold">{formatCurrency(sale.subtotal)}</span>
                            </div>
                            <div className="flex justify-between py-1 border-b border-gray-100">
                                <span className="text-gray-600">I.G.V. (18%):</span>
                                <span className="font-semibold">{formatCurrency(sale.igv)}</span>
                            </div>
                            <div className="flex justify-between py-1 text-sm font-bold border-t-2 border-gray-900">
                                <span>Importe Total:</span>
                                <span>{formatCurrency(sale.total)}</span>
                            </div>
                        </div>
                    </div>

                    {sale.condicion_pago === 'credito' && (sale.installments ?? []).length > 0 && (
                        <div className="mt-6 border-t pt-4 text-xs">
                            <p className="font-bold uppercase tracking-wider mb-2">Información de Crédito (Cuotas)</p>
                            <div className="grid grid-cols-4 gap-2">
                                {sale.installments!.map((cuota) => (
                                    <div key={cuota.id} className="border rounded p-2">
                                        <p className="font-semibold">Cuota {cuota.numero_cuota}</p>
                                        <p className="text-gray-600">Vence: {cuota.fecha_vencimiento}</p>
                                        <p className="font-medium text-gray-900">{formatCurrency(cuota.monto)}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            )}
        </AppLayout>
    );
}
