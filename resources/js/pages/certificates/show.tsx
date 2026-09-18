import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { type CertificateData, type CertificateEstado } from '@/types/certificate';
import { Head, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const ESTADO_BADGE_VARIANT: Record<CertificateEstado, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    vigente: 'default',
    vencido: 'destructive',
    reemplazado: 'secondary',
    anulado: 'outline',
};

const NEXT_STATUS_OPTIONS: Record<CertificateEstado, CertificateEstado[]> = {
    vigente: ['vencido', 'reemplazado', 'anulado'],
    vencido: ['reemplazado', 'anulado'],
    reemplazado: [],
    anulado: [],
};

export default function CertificateShow({
    certificate,
    tipoLabels,
    estadoLabels,
    verificationUrl,
    qrSvg,
}: {
    certificate: CertificateData;
    tipoLabels: Record<string, string>;
    estadoLabels: Record<string, string>;
    verificationUrl: string;
    qrSvg: string;
}) {
    const { auth } = usePage<SharedData>().props;
    const canManage = auth.permissions.includes('certificates.manage');
    const nextOptions = NEXT_STATUS_OPTIONS[certificate.estado];

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Certificados', href: route('certificates.index') },
        { title: certificate.numero, href: route('certificates.show', certificate.id) },
    ];

    const { data, setData, patch, processing } = useForm<{ estado: CertificateEstado | '' }>({
        estado: nextOptions[0] ?? '',
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        if (!data.estado) return;
        patch(route('certificates.status', certificate.id), { preserveScroll: true });
    };

    const details: [string, string][] = [
        ['Tipo', tipoLabels[certificate.tipo] ?? certificate.tipo],
        ['Orden de servicio', certificate.service_order?.codigo ?? '-'],
        ['Cliente', certificate.service_order?.client.razon_social ?? '-'],
        ['Fecha de emisión', certificate.fecha_emision],
        ['Vigencia', certificate.fecha_vigencia ?? 'Sin vencimiento definido'],
        ['Generado por', certificate.generado_por_user?.name ?? '-'],
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={certificate.numero} />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="min-w-0">
                        <h1 className="text-xl font-semibold tracking-tight break-all sm:text-2xl">{certificate.numero}</h1>
                        <p className="text-muted-foreground text-sm">BRUCE FIRE S.A.C. · Certificado de {tipoLabels[certificate.tipo]}</p>
                    </div>
                    <Badge variant={ESTADO_BADGE_VARIANT[certificate.estado]}>{estadoLabels[certificate.estado]}</Badge>
                </div>

                <div className="grid items-start gap-4 lg:grid-cols-3">
                    <div className="space-y-4 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Datos del certificado</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl className="divide-y">
                                    {details.map(([label, value]) => (
                                        <div key={label} className="grid grid-cols-3 gap-2 py-2 text-sm">
                                            <dt className="text-muted-foreground">{label}</dt>
                                            <dd className="col-span-2 break-words">{value}</dd>
                                        </div>
                                    ))}
                                </dl>
                                {certificate.observaciones && (
                                    <p className="mt-4 text-sm break-words whitespace-pre-wrap">{certificate.observaciones}</p>
                                )}
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Equipos ({certificate.items?.length ?? 0})</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-3 sm:grid-cols-2">
                                {(certificate.items ?? []).map((item) => (
                                    <div key={item.id} className="rounded-lg border p-3 text-sm">
                                        <p className="font-medium break-all">{item.equipment.codigo}</p>
                                        <p className="text-muted-foreground">
                                            {item.equipment.tipo_equipo}
                                            {item.equipment.capacidad ? ` · ${item.equipment.capacidad}` : ''}
                                        </p>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    </div>

                    <div className="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle>QR de autenticidad</CardTitle>
                            </CardHeader>
                            <CardContent className="flex flex-col items-center gap-3">
                                <div
                                    className="rounded-lg border bg-white p-3 [&_svg]:h-auto [&_svg]:w-full"
                                    dangerouslySetInnerHTML={{ __html: qrSvg }}
                                />
                                <p className="text-muted-foreground text-center text-xs break-all">{verificationUrl}</p>
                                <p className="text-muted-foreground text-center text-xs">
                                    Este código QR no es un QR SUNAT. Permite verificar la autenticidad del certificado en la página pública.
                                </p>
                            </CardContent>
                        </Card>

                        {canManage && nextOptions.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Cambiar estado</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <form onSubmit={submit} className="space-y-4">
                                        <Select value={data.estado} onValueChange={(value) => setData('estado', value as CertificateEstado)}>
                                            <SelectTrigger>
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {nextOptions.map((value) => (
                                                    <SelectItem key={value} value={value}>
                                                        {estadoLabels[value]}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        <Button type="submit" disabled={processing} className="w-full">
                                            {processing ? 'Guardando...' : 'Actualizar estado'}
                                        </Button>
                                    </form>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
