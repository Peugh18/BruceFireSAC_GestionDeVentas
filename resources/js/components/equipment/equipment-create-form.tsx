import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { origenEquipoLabels, type EquipmentClientOption, type OrigenEquipo } from '@/types/equipment';
import { FormEventHandler, useMemo } from 'react';

export interface EquipmentCreateFormData {
    client_id: string;
    client_site_id: string;
    vehicle_id: string;
    origen: OrigenEquipo;
    tipo_equipo: string;
    agente: string;
    capacidad: string;
    marca: string;
    serie_fabricante: string;
    anio_fabricacion: string;
    ubicacion: string;
    observaciones: string;
    [key: string]: string;
}

const ORIGENES: OrigenEquipo[] = ['desconocido', 'externo', 'vendido_bruce_fire'];

export function EquipmentCreateForm({
    data,
    setData,
    errors,
    processing,
    onSubmit,
    clients,
    lockClient,
}: {
    data: EquipmentCreateFormData;
    setData: (key: keyof EquipmentCreateFormData, value: string) => void;
    errors: Partial<Record<keyof EquipmentCreateFormData, string>>;
    processing: boolean;
    onSubmit: FormEventHandler;
    clients: EquipmentClientOption[];
    lockClient: boolean;
}) {
    const selectedClient = useMemo(() => clients.find((client) => String(client.id) === data.client_id), [clients, data.client_id]);

    return (
        <form onSubmit={onSubmit} className="space-y-8">
            <div className="grid gap-6 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="client_id">Cliente</Label>
                    <Select
                        value={data.client_id}
                        onValueChange={(value) => {
                            setData('client_id', value);
                            setData('client_site_id', '');
                            setData('vehicle_id', '');
                        }}
                        disabled={lockClient}
                    >
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
                        onValueChange={(value) => setData('client_site_id', value === 'ninguna' ? '' : value)}
                        disabled={!selectedClient}
                    >
                        <SelectTrigger id="client_site_id">
                            <SelectValue placeholder="Sin sede especifica" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="ninguna">Sin sede especifica</SelectItem>
                            {(selectedClient?.sites ?? []).map((site) => (
                                <SelectItem key={site.id} value={String(site.id)}>
                                    {site.nombre}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.client_site_id} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="vehicle_id">Vehiculo (opcional)</Label>
                    <Select
                        value={data.vehicle_id || 'ninguno'}
                        onValueChange={(value) => setData('vehicle_id', value === 'ninguno' ? '' : value)}
                        disabled={!selectedClient}
                    >
                        <SelectTrigger id="vehicle_id">
                            <SelectValue placeholder="Sin vehiculo asociado" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="ninguno">Sin vehiculo asociado</SelectItem>
                            {(selectedClient?.vehicles ?? []).map((vehicle) => (
                                <SelectItem key={vehicle.id} value={String(vehicle.id)}>
                                    {vehicle.placa}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.vehicle_id} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="origen">Origen del equipo</Label>
                    <Select value={data.origen} onValueChange={(value) => setData('origen', value as OrigenEquipo)}>
                        <SelectTrigger id="origen">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {ORIGENES.map((origen) => (
                                <SelectItem key={origen} value={origen}>
                                    {origenEquipoLabels[origen]}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.origen} />
                </div>
            </div>

            <div className="grid gap-6 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="tipo_equipo">Tipo de equipo</Label>
                    <Input
                        id="tipo_equipo"
                        value={data.tipo_equipo}
                        onChange={(e) => setData('tipo_equipo', e.target.value)}
                        placeholder="Ej. Extintor PQS"
                        required
                    />
                    <InputError message={errors.tipo_equipo} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="agente">Agente extintor</Label>
                    <Input
                        id="agente"
                        value={data.agente}
                        onChange={(e) => setData('agente', e.target.value)}
                        placeholder="Ej. PQS ABC, CO2"
                    />
                    <InputError message={errors.agente} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="capacidad">Capacidad / peso</Label>
                    <Input id="capacidad" value={data.capacidad} onChange={(e) => setData('capacidad', e.target.value)} placeholder="Ej. 6 kg" />
                    <InputError message={errors.capacidad} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="marca">Marca</Label>
                    <Input
                        id="marca"
                        value={data.marca}
                        onChange={(e) => setData('marca', e.target.value)}
                        placeholder="Dejar vacio si no es legible"
                    />
                    <p className="text-xs text-muted-foreground">Si no se puede leer, deja el campo vacio. Nunca inventes el dato.</p>
                    <InputError message={errors.marca} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="serie_fabricante">Serie del fabricante</Label>
                    <Input
                        id="serie_fabricante"
                        value={data.serie_fabricante}
                        onChange={(e) => setData('serie_fabricante', e.target.value)}
                        placeholder="Dejar vacio si no es legible"
                    />
                    <InputError message={errors.serie_fabricante} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="anio_fabricacion">Año de fabricacion</Label>
                    <Input
                        id="anio_fabricacion"
                        value={data.anio_fabricacion}
                        onChange={(e) => setData('anio_fabricacion', e.target.value)}
                        placeholder="Dejar vacio si no es legible"
                    />
                    <InputError message={errors.anio_fabricacion} />
                </div>

                <div className="grid gap-2 sm:col-span-2">
                    <Label htmlFor="ubicacion">Ubicacion del equipo</Label>
                    <Input
                        id="ubicacion"
                        value={data.ubicacion}
                        onChange={(e) => setData('ubicacion', e.target.value)}
                        placeholder="Ej. Recepcion, pasillo principal"
                    />
                    <InputError message={errors.ubicacion} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="observaciones">Observaciones</Label>
                <Textarea id="observaciones" value={data.observaciones} onChange={(e) => setData('observaciones', e.target.value)} />
                <InputError message={errors.observaciones} />
            </div>

            <div className="flex items-center gap-4">
                <Button type="submit" disabled={processing}>
                    Registrar equipo
                </Button>
            </div>
        </form>
    );
}
