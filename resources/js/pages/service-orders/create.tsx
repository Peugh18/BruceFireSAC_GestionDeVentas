import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type ServiceOrderClient, type ServiceOrderEquipment, type TechnicianOption } from '@/types/service-order';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { type FormEventHandler, useState } from 'react';

interface Props {
    clients: ServiceOrderClient[];
    equipment: ServiceOrderEquipment[];
    defaultClientId: number | null;
    technicians: TechnicianOption[];
    canAssign: boolean;
    serviceTypes: Record<string, string>;
    priorities: Record<string, string>;
    today: string;
}

export default function ServiceOrdersCreate({ clients, equipment, defaultClientId, technicians, canAssign, serviceTypes, priorities, today }: Props) {
    const form = useForm({
        client_id: defaultClientId ? String(defaultClientId) : '',
        client_site_id: '',
        vehicle_id: '',
        tipo_servicio: 'recarga',
        fecha: today,
        tecnico_user_id: '',
        prioridad: 'media',
        observaciones: '',
        equipment_ids: [] as number[],
    });
    const { data, setData, errors, processing } = form;
    const [loadingEquipment, setLoadingEquipment] = useState(false);
    const [equipmentSearch, setEquipmentSearch] = useState('');
    const selectedClient = clients.find((client) => String(client.id) === data.client_id);
    const availableEquipment = equipment.filter(
        (item) =>
            String(item.client_id) === data.client_id &&
            (!data.client_site_id || String(item.client_site_id) === data.client_site_id) &&
            (!data.vehicle_id || String(item.vehicle_id) === data.vehicle_id) &&
            `${item.codigo} ${item.tipo_equipo} ${item.capacidad ?? ''}`.toLocaleLowerCase().includes(equipmentSearch.toLocaleLowerCase()),
    );
    const equipmentErrors = Object.entries(errors).filter(([key]) => key.startsWith('equipment_ids.'));
    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        form.post(route('service-orders.store'));
    };

    function selectClient(value: string) {
        setData((current) => ({ ...current, client_id: value, client_site_id: '', vehicle_id: '', equipment_ids: [] }));
        form.clearErrors();
        setEquipmentSearch('');
        router.get(
            route('service-orders.create'),
            { client_id: value },
            {
                only: ['equipment'],
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onStart: () => setLoadingEquipment(true),
                onFinish: () => setLoadingEquipment(false),
            },
        );
    }

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Órdenes de Servicio', href: route('service-orders.index') },
                { title: 'Nueva orden', href: route('service-orders.create') },
            ]}
        >
            <Head title="Nueva orden de servicio" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Nueva orden de servicio</CardTitle>
                        <p className="text-muted-foreground text-sm">El código se genera al guardar. La orden comienza pendiente de recepción.</p>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-8">
                            <div className="grid gap-6 sm:grid-cols-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="client_id">Cliente</Label>
                                    <Select value={data.client_id} onValueChange={selectClient} disabled={loadingEquipment}>
                                        <SelectTrigger id="client_id">
                                            <SelectValue placeholder="Selecciona un cliente" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {clients.map((client) => (
                                                <SelectItem key={client.id} value={String(client.id)}>
                                                    {client.razon_social} ({client.codigo})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.client_id} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="client_site_id">Sede (opcional)</Label>
                                    <Select
                                        value={data.client_site_id || 'ninguna'}
                                        disabled={!selectedClient}
                                        onValueChange={(value) =>
                                            setData((current) => ({
                                                ...current,
                                                client_site_id: value === 'ninguna' ? '' : value,
                                                equipment_ids: [],
                                            }))
                                        }
                                    >
                                        <SelectTrigger id="client_site_id">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="ninguna">Sin sede específica</SelectItem>
                                            {selectedClient?.sites.map((site) => (
                                                <SelectItem key={site.id} value={String(site.id)}>
                                                    {site.nombre}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.client_site_id} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="vehicle_id">Vehículo (opcional)</Label>
                                    <Select
                                        value={data.vehicle_id || 'ninguno'}
                                        disabled={!selectedClient}
                                        onValueChange={(value) =>
                                            setData((current) => ({ ...current, vehicle_id: value === 'ninguno' ? '' : value, equipment_ids: [] }))
                                        }
                                    >
                                        <SelectTrigger id="vehicle_id">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="ninguno">Sin vehículo asociado</SelectItem>
                                            {selectedClient?.vehicles.map((vehicle) => (
                                                <SelectItem key={vehicle.id} value={String(vehicle.id)}>
                                                    {vehicle.placa}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.vehicle_id} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="tipo_servicio">Tipo de servicio</Label>
                                    <Select value={data.tipo_servicio} onValueChange={(value) => setData('tipo_servicio', value)}>
                                        <SelectTrigger id="tipo_servicio">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(serviceTypes).map(([value, label]) => (
                                                <SelectItem key={value} value={value}>
                                                    {label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.tipo_servicio} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="fecha">Fecha</Label>
                                    <Input
                                        id="fecha"
                                        type="date"
                                        required
                                        value={data.fecha}
                                        onChange={(event) => setData('fecha', event.target.value)}
                                    />
                                    <InputError message={errors.fecha} />
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="prioridad">Prioridad</Label>
                                    <Select value={data.prioridad} onValueChange={(value) => setData('prioridad', value)}>
                                        <SelectTrigger id="prioridad">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {Object.entries(priorities).map(([value, label]) => (
                                                <SelectItem key={value} value={value}>
                                                    {label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.prioridad} />
                                </div>
                                {canAssign && (
                                    <div className="grid gap-2">
                                        <Label htmlFor="tecnico_user_id">Técnico asignado (opcional)</Label>
                                        <Select
                                            value={data.tecnico_user_id || 'ninguno'}
                                            onValueChange={(value) => setData('tecnico_user_id', value === 'ninguno' ? '' : value)}
                                        >
                                            <SelectTrigger id="tecnico_user_id">
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
                                )}
                            </div>
                            <fieldset className="space-y-4" disabled={loadingEquipment}>
                                <legend className="text-base font-semibold">Equipos de la orden</legend>
                                <p className="text-muted-foreground text-sm">
                                    {data.equipment_ids.length} seleccionados. Selecciona al menos un equipo del cliente.
                                </p>
                                <Label className="sr-only" htmlFor="equipment-search">
                                    Buscar equipos
                                </Label>
                                <Input
                                    id="equipment-search"
                                    placeholder="Buscar por código, tipo o capacidad"
                                    value={equipmentSearch}
                                    onChange={(event) => setEquipmentSearch(event.target.value)}
                                    disabled={!selectedClient}
                                />
                                {loadingEquipment ? (
                                    <p role="status" className="text-muted-foreground animate-pulse text-sm">
                                        Cargando equipos...
                                    </p>
                                ) : (
                                    <div className="grid max-h-80 gap-3 overflow-y-auto sm:grid-cols-2">
                                        {availableEquipment.map((item) => (
                                            <label key={item.id} className="flex cursor-pointer items-start gap-3 rounded-lg border p-4">
                                                <Checkbox
                                                    checked={data.equipment_ids.includes(item.id)}
                                                    onCheckedChange={(checked) =>
                                                        setData(
                                                            'equipment_ids',
                                                            checked === true
                                                                ? [...data.equipment_ids, item.id]
                                                                : data.equipment_ids.filter((id) => id !== item.id),
                                                        )
                                                    }
                                                    aria-label={`Seleccionar ${item.codigo}`}
                                                />
                                                <span className="min-w-0 text-sm">
                                                    <span className="block font-medium break-all">{item.codigo}</span>
                                                    <span className="text-muted-foreground">
                                                        {item.tipo_equipo}
                                                        {item.capacidad ? ` · ${item.capacidad}` : ''}
                                                    </span>
                                                </span>
                                            </label>
                                        ))}
                                        {availableEquipment.length === 0 && (
                                            <p className="text-muted-foreground text-sm">
                                                {selectedClient
                                                    ? 'No hay equipos que coincidan con la selección.'
                                                    : 'Selecciona un cliente para cargar sus equipos.'}
                                            </p>
                                        )}
                                    </div>
                                )}
                                <InputError message={errors.equipment_ids} />
                                {equipmentErrors.map(([key, message]) => (
                                    <InputError key={key} message={message} />
                                ))}
                            </fieldset>
                            <div className="grid gap-2">
                                <Label htmlFor="observaciones">Observaciones</Label>
                                <Textarea
                                    id="observaciones"
                                    maxLength={5000}
                                    rows={4}
                                    value={data.observaciones}
                                    onChange={(event) => setData('observaciones', event.target.value)}
                                />
                                <InputError message={errors.observaciones} />
                            </div>
                            <div className="flex flex-col gap-3 sm:flex-row">
                                <Button type="submit" disabled={processing || loadingEquipment}>
                                    {processing ? 'Guardando...' : 'Crear orden'}
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={route('service-orders.index')}>Cancelar</Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
