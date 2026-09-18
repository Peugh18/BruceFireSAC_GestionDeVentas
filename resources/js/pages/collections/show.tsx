import CollectionPaymentDialog from '@/components/collection-payment-dialog';
import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type Paginated } from '@/types';
import {
    collectionStatusLabels,
    collectionStatusVariants,
    formatCollectionAmount,
    type CollectionInstallment,
    type CollectionPayment,
} from '@/types/collection';
import { Head, Link } from '@inertiajs/react';
import { useState } from 'react';

interface Props {
    sale: {
        id: number;
        numero: string;
        fecha: string;
        total: string;
        condicion_pago: string;
        estado: string;
        client: { razon_social: string; numero_documento: string };
    };
    installments: CollectionInstallment[];
    payments: Paginated<CollectionPayment>;
    balance: string;
    canRegisterPayment: boolean;
    paymentMethods: Record<string, string>;
    paymentKey: string;
    today: string;
    openPayment: boolean;
    status?: string;
}

export default function CollectionsShow({
    sale,
    installments,
    payments,
    balance,
    canRegisterPayment,
    paymentMethods,
    paymentKey,
    today,
    openPayment,
    status,
}: Props) {
    const [selectedInstallment, setSelectedInstallment] = useState<CollectionInstallment | null>(
        openPayment && canRegisterPayment ? (installments.find((installment) => Number(installment.monto_pendiente) > 0) ?? null) : null,
    );

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Cobranzas', href: route('collections.index') },
                { title: sale.numero, href: route('collections.show', sale.id) },
            ]}
        >
            <Head title={`Cobranza ${sale.numero}`} />
            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <HeadingSmall title={`Cobranza ${sale.numero}`} description={`${sale.client.razon_social} · ${sale.client.numero_documento}`} />
                    <Button variant="outline" asChild>
                        <Link href={route('collections.index')}>Volver a cobranzas</Link>
                    </Button>
                </div>
                {status && (
                    <div role="status" className="bg-muted/50 rounded-lg border px-4 py-3 text-sm">
                        {status}
                    </div>
                )}
                {sale.estado === 'anulada' && (
                    <p role="status" className="border-destructive text-destructive rounded-lg border p-4 text-sm">
                        Esta venta está anulada y no admite nuevos pagos.
                    </p>
                )}
                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-muted-foreground text-sm">Total de la venta</CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold tabular-nums">{formatCollectionAmount(sale.total)}</CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-muted-foreground text-sm">Saldo de cuotas</CardTitle>
                        </CardHeader>
                        <CardContent className="text-2xl font-semibold tabular-nums">{formatCollectionAmount(balance)}</CardContent>
                    </Card>
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-muted-foreground text-sm">Condición de pago</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p className="text-xl font-semibold">{sale.condicion_pago === 'credito' ? 'Crédito' : 'Contado'}</p>
                            <p className="text-muted-foreground mt-1 text-xs">Venta del {sale.fecha.slice(0, 10)}</p>
                        </CardContent>
                    </Card>
                </div>
                <Card>
                    <CardHeader>
                        <CardTitle>Cuotas</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-3">
                        {installments.length === 0 && <p className="text-muted-foreground text-sm">Esta venta no tiene cuotas registradas.</p>}
                        {installments.map((installment) => (
                            <article
                                key={installment.id}
                                className="flex flex-col justify-between gap-4 rounded-lg border p-4 sm:flex-row sm:items-center"
                            >
                                <div className="space-y-2">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <h2 className="font-medium">Cuota {installment.numero_cuota}</h2>
                                        <Badge variant={collectionStatusVariants[installment.estado]}>
                                            {collectionStatusLabels[installment.estado]}
                                        </Badge>
                                    </div>
                                    <p className="text-muted-foreground text-sm">
                                        Vencimiento: {installment.fecha_vencimiento} · Monto: {formatCollectionAmount(installment.monto)}
                                    </p>
                                </div>
                                <div className="flex flex-wrap items-center gap-4">
                                    <p className="text-sm font-semibold tabular-nums">Saldo: {formatCollectionAmount(installment.monto_pendiente)}</p>
                                    {canRegisterPayment && Number(installment.monto_pendiente) > 0 && (
                                        <Button size="sm" onClick={() => setSelectedInstallment(installment)}>
                                            Registrar pago
                                        </Button>
                                    )}
                                </div>
                            </article>
                        ))}
                    </CardContent>
                </Card>
                <Card>
                    <CardHeader>
                        <CardTitle>Pagos registrados</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        {payments.data.length === 0 && <p className="text-muted-foreground text-sm">Aún no hay pagos registrados para esta venta.</p>}
                        <ol className="space-y-3">
                            {payments.data.map((payment) => {
                                const installment = installments.find((item) => item.id === payment.sale_installment_id);
                                return (
                                    <li key={payment.id} className="space-y-2 rounded-lg border p-4">
                                        <div className="flex flex-wrap justify-between gap-2">
                                            <p className="font-medium">
                                                {paymentMethods[payment.forma_pago] ?? payment.forma_pago}
                                                {installment ? ` · Cuota ${installment.numero_cuota}` : ''}
                                            </p>
                                            <p className="font-semibold tabular-nums">{formatCollectionAmount(payment.monto)}</p>
                                        </div>
                                        <p className="text-muted-foreground text-sm">
                                            {payment.fecha ?? payment.created_at.slice(0, 10)}
                                            {payment.referencia ? ` · Operación: ${payment.referencia}` : ''}
                                        </p>
                                        {payment.observaciones && <p className="text-sm break-words whitespace-pre-wrap">{payment.observaciones}</p>}
                                    </li>
                                );
                            })}
                        </ol>
                        {payments.last_page > 1 && (
                            <nav aria-label="Paginación de pagos" className="flex flex-wrap gap-1">
                                {payments.links.map((link, index) => (
                                    <Button
                                        key={index}
                                        size="sm"
                                        asChild={!!link.url}
                                        disabled={!link.url}
                                        variant={link.active ? 'default' : 'outline'}
                                    >
                                        {link.url ? (
                                            <Link href={link.url} preserveScroll dangerouslySetInnerHTML={{ __html: link.label }} />
                                        ) : (
                                            <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                        )}
                                    </Button>
                                ))}
                            </nav>
                        )}
                    </CardContent>
                </Card>
                {selectedInstallment && (
                    <CollectionPaymentDialog
                        key={`${selectedInstallment.id}-${paymentKey}`}
                        saleId={sale.id}
                        saleDate={sale.fecha}
                        installment={selectedInstallment}
                        paymentMethods={paymentMethods}
                        paymentKey={paymentKey}
                        today={today}
                        onClose={() => setSelectedInstallment(null)}
                    />
                )}
            </div>
        </AppLayout>
    );
}
