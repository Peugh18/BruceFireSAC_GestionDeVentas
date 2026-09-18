import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type SharedData } from '@/types';
import { type CertificateTipo } from '@/types/certificate';
import { type ServiceOrder, type ServiceOrderLabels, type TechnicianOption } from '@/types/service-order';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { type FormEventHandler } from 'react';

interface Props extends ServiceOrderLabels {
    order: ServiceOrder;
    transitions: string[];
    canAssign: boolean;
    technicians: TechnicianOption[];
    status?: string;
}

const CERTIFICATE_TIPO_LABELS: Record<CertificateTipo, string> = {
    operatividad_garantia: 'Operatividad y Garantía',
    prueba_hidrostatica: 'Prueba Hidrostática',
    capacitacion: 'Capacitación / Participación',
    deteccion_alarma: 'Operatividad de Detección/Alarma',
    otro: 'Otro',
};

function GenerateCertificate({ order }: Pick<Props, 'order'>) {
    const { data, setData, post, errors, processing } = useForm({
        service_order_id: order.id,
        tipo: 'operatividad_garantia' as CertificateTipo,
        fecha_emision: new Date().toISOString().slice(0, 10),
        fecha_vigencia: '',
        observaciones: '',
        equipment_ids: order.equipment.map((item) => item.id),
    });

    const toggleEquipment = (equipmentId: number, checked: boolean) => {
        setData('equipment_ids', checked ? [...data.equipment_ids, equipmentId] : data.equipment_ids.filter((id) => id !== equipmentId));
    };

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        post(route('certificates.store'));
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Generar certificado</CardTitle>
            </CardHeader>
            <CardContent>
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="certificate-tipo">Tipo de certificado</Label>
                        <Select value={data.tipo} onValueChange={(value) => setData('tipo', value as CertificateTipo)}>
                            <SelectTrigger id="certificate-tipo">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {Object.entries(CERTIFICATE_TIPO_LABELS).map(([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.tipo} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="certificate-fecha-emision">Fecha de emisión</Label>
                        <input
                            id="certificate-fecha-emision"
                            type="date"
                            className="border-input bg-background h-9 rounded-md border px-3 text-sm"
                            value={data.fecha_emision}
                            onChange={(event) => setData('fecha_emision', event.target.value)}
                        />
                        <InputError message={errors.fecha_emision} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="certificate-fecha-vigencia">Vigencia hasta (opcional)</Label>
                        <input
                            id="certificate-fecha-vigencia"
                            type="date"
                            className="border-input bg-background h-9 rounded-md border px-3 text-sm"
                            value={data.fecha_vigencia}
                            onChange={(event) => setData('fecha_vigencia', event.target.value)}
                        />
                        <InputError message={errors.fecha_vigencia} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="certificate-observaciones">Observaciones (opcional)</Label>
                        <Textarea
                            id="certificate-observaciones"
                            value={data.observaciones}
                            onChange={(event) => setData('observaciones', event.target.value)}
                        />
                        <InputError message={errors.observaciones} />
                    </div>
                    <div className="grid gap-2">
                        <Label>Equipos incluidos</Label>
                        <div className="max-h-40 space-y-2 overflow-y-auto rounded-md border p-2">
                            {order.equipment.map((item) => (
                                <div key={item.id} className="flex items-center gap-2">
                                    <Checkbox
                                        id={`certificate-equipment-${item.id}`}
                                        checked={data.equipment_ids.includes(item.id)}
                                        onCheckedChange={(checked) => toggleEquipment(item.id, Boolean(checked))}
                                    />
                                    <label htmlFor={`certificate-equipment-${item.id}`} className="text-sm">
                                        {item.codigo} · {item.tipo_equipo}
                                    </label>
                                </div>
                            ))}
                        </div>
                        <InputError message={errors.equipment_ids} />
                    </div>
                    <Button type="submit" disabled={processing || data.equipment_ids.length === 0} className="w-full">
                        {processing ? 'Generando...' : 'Generar certificado'}
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}

function AdvanceStatus({ order, transitions, statuses }: Pick<Props, 'order' | 'transitions' | 'statuses'>) {
    const { data, setData, patch, errors, processing } = useForm({ estado: transitions[0] ?? '', observaciones: '' });
    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        patch(route('service-orders.status', order.id), { preserveScroll: true });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Avanzar estado</CardTitle>
            </CardHeader>
            <CardContent>
                {transitions.length === 0 ? (
                    <p className="text-muted-foreground text-sm">
                        {order.estado === 'cerrado'
                            ? 'La orden está cerrada. Su historial se conserva para consulta.'
                            : 'Tu rol no tiene acciones disponibles para el estado actual.'}
                    </p>
                ) : (
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid gap-2">
                            <Label htmlFor="next-status">Siguiente estado</Label>
                            <Select value={data.estado} onValueChange={(value) => setData('estado', value)}>
                                <SelectTrigger id="next-status">
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    {transitions.map((value) => (
                                        <SelectItem key={value} value={value}>
                                            {statuses[value]}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={errors.estado} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="status-note">Observaciones del cambio (opcional)</Label>
                            <Textarea
                                id="status-note"
                                value={data.observaciones}
                                maxLength={5000}
                                onChange={(event) => setData('observaciones', event.target.value)}
                            />
                            <InputError message={errors.observaciones} />
                        </div>
                        <Button type="submit" disabled={processing} className="w-full">
                            {processing ? 'Guardando...' : 'Avanzar estado'}
                        </Button>
                    </form>
                )}
            </CardContent>
        </Card>
    );
}

function AssignTechnician({ order, technicians }: Pick<Props, 'order' | 'technicians'>) {
    const { data, setData, patch, errors, processing } = useForm({ tecnico_user_id: order.tecnico_user_id ? String(order.tecnico_user_id) : '' });
    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        patch(route('service-orders.update', order.id), { preserveScroll: true });
    };

    return (
        <Card>
            <CardHeader>
                <CardTitle>Asignar técnico</CardTitle>
            </CardHeader>
            <CardContent>
                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-2">
                        <Label htmlFor="assigned-technician">Técnico</Label>
                        <Select
                            value={data.tecnico_user_id || 'ninguno'}
                            onValueChange={(value) => setData('tecnico_user_id', value === 'ninguno' ? '' : value)}
                        >
                            <SelectTrigger id="assigned-technician">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="ninguno">Sin asignar</SelectItem>
                                {technicians.map((technician) => (
                                    <SelectItem key={technician.id} value={String(technician.id)}>
                                        {technician.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.tecnico_user_id} />
                    </div>
                    <Button type="submit" variant="outline" className="w-full" disabled={processing}>
                        Guardar asignación
                    </Button>
                </form>
            </CardContent>
        </Card>
    );
}

export default function ServiceOrdersShow({ order, transitions, statuses, serviceTypes, priorities, canAssign, technicians, status }: Props) {
    const { auth } = usePage<SharedData>().props;
    const details = [
        ['Cliente', order.client.razon_social],
        ['Sede', order.client_site?.nombre ?? 'Sin sede específica'],
        ['Vehículo', order.vehicle?.placa ?? 'Sin vehículo asociado'],
        ['Servicio', serviceTypes[order.tipo_servicio]],
        ['Fecha', order.fecha],
        ['Técnico', order.tecnico?.name ?? 'Sin asignar'],
        ['Prioridad', priorities[order.prioridad]],
    ];

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Órdenes de Servicio', href: route('service-orders.index') },
                { title: order.codigo, href: route('service-orders.show', order.id) },
            ]}
        >
            <Head title={order.codigo} />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div className="min-w-0">
                        <h1 className="text-xl font-semibold tracking-tight break-all sm:text-2xl">{order.codigo}</h1>
                        <p className="text-muted-foreground text-sm">
                            {serviceTypes[order.tipo_servicio]} · {order.client.razon_social}
                        </p>
                    </div>
                    <div className="flex flex-wrap items-center gap-2">
                        {auth.permissions.includes('pickups.create') && (
                            <Button asChild variant="outline" size="sm">
                                <Link href={route('pickups.create', { service_order_id: order.id })}>
                                    Registrar recojo
                                </Link>
                            </Button>
                        )}
                        <Button asChild variant="secondary" size="sm">
                            <Link href={route('service-orders.acta.show', order.id)}>
                                Acta de conformidad
                            </Link>
                        </Button>
                        <Badge variant={order.estado === 'cerrado' ? 'secondary' : 'default'}>{statuses[order.estado]}</Badge>
                    </div>
                </div>
                {status && (
                    <div role="status" className="bg-muted/50 rounded-lg border px-4 py-3 text-sm">
                        {status}
                    </div>
                )}
                <div className="grid items-start gap-4 lg:grid-cols-3">
                    <div className="space-y-4 lg:col-span-2">
                        <Card>
                            <CardHeader>
                                <CardTitle>Datos de la orden</CardTitle>
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
                                {order.observaciones && <p className="mt-4 text-sm break-words whitespace-pre-wrap">{order.observaciones}</p>}
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader>
                                <CardTitle>Equipos ({order.equipment.length})</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-3 sm:grid-cols-2">
                                {order.equipment.map((item) => (
                                    <div key={item.id} className="rounded-lg border p-3 text-sm">
                                        {auth.permissions.includes('equipment.view') ? (
                                            <Link href={route('equipment.show', item.id)} className="font-medium break-all hover:underline">
                                                {item.codigo}
                                            </Link>
                                        ) : (
                                            <p className="font-medium break-all">{item.codigo}</p>
                                        )}
                                        <p className="text-muted-foreground">
                                            {item.tipo_equipo}
                                            {item.capacidad ? ` · ${item.capacidad}` : ''}
                                        </p>
                                        {auth.permissions.includes('checklists.view') && (
                                            <Button asChild variant="outline" size="sm" className="mt-2 w-full text-xs">
                                                <Link href={route('checklists.show', [order.id, item.id])}>
                                                    Checklist técnico
                                                </Link>
                                            </Button>
                                        )}
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader>
                                <CardTitle>Historial de estados</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <ol className="space-y-4">
                                    {order.status_history.map((entry) => (
                                        <li key={entry.id} className="border-muted border-l-2 pl-4">
                                            <p className="text-muted-foreground text-xs">
                                                <time dateTime={entry.created_at}>{new Date(entry.created_at).toLocaleString('es-PE')}</time> ·{' '}
                                                {entry.user?.name ?? 'Sistema'}
                                            </p>
                                            <p className="text-sm font-medium">
                                                {entry.estado_anterior ? `${statuses[entry.estado_anterior]} → ` : 'Orden creada: '}
                                                {statuses[entry.estado]}
                                            </p>
                                            {entry.observaciones && (
                                                <p className="text-muted-foreground text-sm break-words whitespace-pre-wrap">{entry.observaciones}</p>
                                            )}
                                        </li>
                                    ))}
                                </ol>
                            </CardContent>
                        </Card>
                    </div>
                    <div className="space-y-4">
                        <AdvanceStatus
                            key={`${order.id}-${order.estado}-${transitions.join(',')}`}
                            order={order}
                            transitions={transitions}
                            statuses={statuses}
                        />
                        {canAssign && <AssignTechnician key={`${order.id}-${order.tecnico_user_id}`} order={order} technicians={technicians} />}
                        {auth.permissions.includes('certificates.issue') && order.equipment.length > 0 && (
                            <GenerateCertificate key={`${order.id}-certificate`} order={order} />
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
