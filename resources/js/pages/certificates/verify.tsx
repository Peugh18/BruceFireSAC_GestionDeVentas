import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head } from '@inertiajs/react';
import { ShieldCheck, ShieldX } from 'lucide-react';

interface PublicCertificateEquipment {
    codigo: string;
    tipo_equipo: string;
    capacidad: string | null;
}

interface PublicCertificate {
    numero: string;
    tipo: string;
    estado: string;
    estado_key: string;
    es_vigente: boolean;
    fecha_emision: string | null;
    fecha_vigencia: string | null;
    cliente: string;
    orden_codigo: string;
    equipos: PublicCertificateEquipment[];
}

export default function CertificateVerify({ certificate }: { certificate: PublicCertificate }) {
    return (
        <>
            <Head title={`Verificación ${certificate.numero}`} />
            <div className="bg-background flex min-h-screen flex-col items-center gap-6 p-4 py-10 sm:p-8">
                <div className="w-full max-w-2xl space-y-6">
                    <div className="text-center">
                        <p className="text-primary text-sm font-semibold tracking-wide uppercase">BRUCE FIRE S.A.C.</p>
                        <h1 className="text-2xl font-bold tracking-tight sm:text-3xl">Verificación de certificado</h1>
                    </div>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between gap-3">
                            <CardTitle className="text-lg">{certificate.numero}</CardTitle>
                            <Badge variant={certificate.es_vigente ? 'default' : 'destructive'} className="gap-1.5">
                                {certificate.es_vigente ? <ShieldCheck className="size-3.5" /> : <ShieldX className="size-3.5" />}
                                {certificate.estado}
                            </Badge>
                        </CardHeader>
                        <CardContent>
                            <dl className="divide-y text-sm">
                                <div className="grid grid-cols-3 gap-2 py-2">
                                    <dt className="text-muted-foreground">Tipo</dt>
                                    <dd className="col-span-2 break-words">{certificate.tipo}</dd>
                                </div>
                                <div className="grid grid-cols-3 gap-2 py-2">
                                    <dt className="text-muted-foreground">Cliente</dt>
                                    <dd className="col-span-2 break-words">{certificate.cliente}</dd>
                                </div>
                                <div className="grid grid-cols-3 gap-2 py-2">
                                    <dt className="text-muted-foreground">Orden de servicio</dt>
                                    <dd className="col-span-2 break-words">{certificate.orden_codigo}</dd>
                                </div>
                                <div className="grid grid-cols-3 gap-2 py-2">
                                    <dt className="text-muted-foreground">Emisión</dt>
                                    <dd className="col-span-2 break-words">{certificate.fecha_emision ?? '-'}</dd>
                                </div>
                                <div className="grid grid-cols-3 gap-2 py-2">
                                    <dt className="text-muted-foreground">Vigencia</dt>
                                    <dd className="col-span-2 break-words">{certificate.fecha_vigencia ?? 'Sin vencimiento definido'}</dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Equipos certificados ({certificate.equipos.length})</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-3 sm:grid-cols-2">
                            {certificate.equipos.map((equipo) => (
                                <div key={equipo.codigo} className="rounded-lg border p-3 text-sm">
                                    <p className="font-medium break-all">{equipo.codigo}</p>
                                    <p className="text-muted-foreground">
                                        {equipo.tipo_equipo}
                                        {equipo.capacidad ? ` · ${equipo.capacidad}` : ''}
                                    </p>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <p className="text-muted-foreground text-center text-xs">
                        Esta página verifica la autenticidad de certificados emitidos por BRUCE FIRE S.A.C. No corresponde a un comprobante SUNAT.
                    </p>
                </div>
            </div>
        </>
    );
}
