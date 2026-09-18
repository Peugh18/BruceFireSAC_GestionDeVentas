import { EquipmentTransferDialog } from '@/components/equipment/equipment-transfer-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { estadoEquipoLabels, NO_LEGIBLE, origenEquipoLabels, type Equipment, type EquipmentClientOption } from '@/types/equipment';
import { Head, Link, usePage } from '@inertiajs/react';

function InfoRow({ label, value }: { label: string; value: string | null }) {
    return (
        <div className="grid grid-cols-3 gap-2 py-2 text-sm">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="col-span-2">{value && value.length > 0 ? value : <span className="text-muted-foreground">{NO_LEGIBLE}</span>}</dd>
        </div>
    );
}

function estadoBadgeVariant(estado: Equipment['estado']): 'default' | 'secondary' | 'destructive' {
    if (estado === 'activo') {
        return 'default';
    }

    if (estado === 'baja_definitiva' || estado === 'no_localizado') {
        return 'destructive';
    }

    return 'secondary';
}

const EVENT_LABELS: Record<string, string> = {
    alta: 'Alta del equipo',
    transferencia: 'Transferencia',
    cambio_estado: 'Cambio de estado',
};

export default function EquipmentShow({ equipment, clients }: { equipment: Equipment; clients: EquipmentClientOption[] }) {
    const { auth } = usePage<SharedData>().props;
    const canUpdate = auth.permissions.includes('equipment.update');
    const canTransfer = auth.permissions.includes('equipment.transfer');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Equipos', href: '/equipment' },
        { title: equipment.codigo, href: `/equipment/${equipment.id}` },
    ];

    type Timelineitem = { id: string; fecha: string; titulo: string; descripcion: string };

    const timeline: Timelineitem[] = [
        ...(equipment.events ?? []).map((event) => ({
            id: `event-${event.id}`,
            fecha: event.fecha,
            titulo: EVENT_LABELS[event.tipo] ?? event.tipo,
            descripcion: event.descripcion ?? '',
        })),
    ].sort((a, b) => (a.fecha < b.fecha ? 1 : -1));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={equipment.codigo} />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <div className="flex items-center gap-2">
                            <h1 className="text-2xl font-semibold tracking-tight">{equipment.codigo}</h1>
                            <Badge variant={estadoBadgeVariant(equipment.estado)}>{estadoEquipoLabels[equipment.estado]}</Badge>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {equipment.tipo_equipo}
                            {equipment.client && (
                                <>
                                    {' · '}
                                    <Link href={route('clients.show', equipment.client.id)} className="hover:underline">
                                        {equipment.client.razon_social}
                                    </Link>
                                </>
                            )}
                            {equipment.client_site && <> · {equipment.client_site.nombre}</>}
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        {canTransfer && (
                            <EquipmentTransferDialog
                                equipmentId={equipment.id}
                                clients={clients}
                                trigger={<Button variant="outline">Transferir equipo</Button>}
                            />
                        )}
                        {canUpdate && (
                            <Button asChild>
                                <Link href={route('equipment.edit', equipment.id)}>Editar equipo</Link>
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Datos del equipo</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="divide-y">
                                <InfoRow label="Codigo BRUCE FIRE" value={equipment.codigo} />
                                <InfoRow label="Barcode" value={equipment.barcode} />
                                <InfoRow label="Origen" value={origenEquipoLabels[equipment.origen]} />
                                <InfoRow label="Agente" value={equipment.agente} />
                                <InfoRow label="Capacidad" value={equipment.capacidad} />
                                <InfoRow label="Marca" value={equipment.marca} />
                                <InfoRow label="Serie fabricante" value={equipment.serie_fabricante} />
                                <InfoRow label="Año de fabricacion" value={equipment.anio_fabricacion} />
                                <InfoRow label="Ubicacion" value={equipment.ubicacion} />
                                <InfoRow label="Vehiculo" value={equipment.vehicle?.placa ?? null} />
                                <InfoRow label="Ultima atencion" value={equipment.ultima_atencion} />
                                <InfoRow label="Proxima atencion" value={equipment.proxima_atencion} />
                                <InfoRow label="Ultimo P.H." value={equipment.ultima_ph} />
                                <InfoRow label="Proximo P.H." value={equipment.proxima_ph} />
                                <InfoRow label="Observaciones" value={equipment.observaciones} />
                            </dl>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Historial de vida</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {timeline.length === 0 && <p className="text-sm text-muted-foreground">Sin eventos registrados.</p>}

                            <ol className="space-y-4">
                                {timeline.map((item) => (
                                    <li key={item.id} className="border-l-2 border-muted pl-4">
                                        <p className="text-xs text-muted-foreground">{item.fecha}</p>
                                        <p className="text-sm font-medium">{item.titulo}</p>
                                        {item.descripcion && <p className="text-sm text-muted-foreground">{item.descripcion}</p>}
                                    </li>
                                ))}
                            </ol>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Historial de transferencias</CardTitle>
                    </CardHeader>
                    <CardContent>
                        {(equipment.transfers ?? []).length === 0 && (
                            <p className="text-sm text-muted-foreground">Este equipo no ha sido transferido.</p>
                        )}

                        <ol className="space-y-4">
                            {(equipment.transfers ?? []).map((transfer) => (
                                <li key={transfer.id} className="border-l-2 border-muted pl-4 text-sm">
                                    <p className="text-xs text-muted-foreground">{transfer.fecha}</p>
                                    <p className="font-medium">
                                        {transfer.origen_client?.razon_social ?? 'Sin cliente'} → {transfer.destino_client?.razon_social ?? 'Sin cliente'}
                                    </p>
                                    <p className="text-muted-foreground">Motivo: {transfer.motivo}</p>
                                    {transfer.responsable && (
                                        <p className="text-muted-foreground">Responsable: {transfer.responsable.name}</p>
                                    )}
                                    {transfer.observacion && <p className="text-muted-foreground">{transfer.observacion}</p>}
                                </li>
                            ))}
                        </ol>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
