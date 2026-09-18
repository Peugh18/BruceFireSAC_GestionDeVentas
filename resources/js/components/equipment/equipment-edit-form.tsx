import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import {
    estadoEquipoLabels,
    origenEquipoLabels,
    type EquipmentVehicle,
    type EstadoEquipo,
    type OrigenEquipo,
} from '@/types/equipment';
import { FormEventHandler } from 'react';

export interface EquipmentEditFormData {
    vehicle_id: string;
    origen: OrigenEquipo;
    tipo_equipo: string;
    agente: string;
    capacidad: string;
    marca: string;
    serie_fabricante: string;
    anio_fabricacion: string;
    ubicacion: string;
    estado: EstadoEquipo;
    ultima_atencion: string;
    proxima_atencion: string;
    ultima_ph: string;
    proxima_ph: string;
    observaciones: string;
    [key: string]: string;
}

const ORIGENES: OrigenEquipo[] = ['desconocido', 'externo', 'vendido_bruce_fire'];
const ESTADOS = Object.entries(estadoEquipoLabels) as [EstadoEquipo, string][];

export function EquipmentEditForm({
    data,
    setData,
    errors,
    processing,
    onSubmit,
    vehicles,
}: {
    data: EquipmentEditFormData;
    setData: (key: keyof EquipmentEditFormData, value: string) => void;
    errors: Partial<Record<keyof EquipmentEditFormData, string>>;
    processing: boolean;
    onSubmit: FormEventHandler;
    vehicles: EquipmentVehicle[];
}) {
    return (
        <form onSubmit={onSubmit} className="space-y-8">
            <div className="grid gap-6 sm:grid-cols-2">
                <div className="grid gap-2">
                    <Label htmlFor="tipo_equipo">Tipo de equipo</Label>
                    <Input id="tipo_equipo" value={data.tipo_equipo} onChange={(e) => setData('tipo_equipo', e.target.value)} required />
                    <InputError message={errors.tipo_equipo} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="agente">Agente extintor</Label>
                    <Input id="agente" value={data.agente} onChange={(e) => setData('agente', e.target.value)} />
                    <InputError message={errors.agente} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="capacidad">Capacidad / peso</Label>
                    <Input id="capacidad" value={data.capacidad} onChange={(e) => setData('capacidad', e.target.value)} />
                    <InputError message={errors.capacidad} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="marca">Marca</Label>
                    <Input id="marca" value={data.marca} onChange={(e) => setData('marca', e.target.value)} />
                    <InputError message={errors.marca} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="serie_fabricante">Serie del fabricante</Label>
                    <Input id="serie_fabricante" value={data.serie_fabricante} onChange={(e) => setData('serie_fabricante', e.target.value)} />
                    <InputError message={errors.serie_fabricante} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="anio_fabricacion">Año de fabricacion</Label>
                    <Input id="anio_fabricacion" value={data.anio_fabricacion} onChange={(e) => setData('anio_fabricacion', e.target.value)} />
                    <InputError message={errors.anio_fabricacion} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="ubicacion">Ubicacion</Label>
                    <Input id="ubicacion" value={data.ubicacion} onChange={(e) => setData('ubicacion', e.target.value)} />
                    <InputError message={errors.ubicacion} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="vehicle_id">Vehiculo (opcional)</Label>
                    <Select
                        value={data.vehicle_id || 'ninguno'}
                        onValueChange={(value) => setData('vehicle_id', value === 'ninguno' ? '' : value)}
                    >
                        <SelectTrigger id="vehicle_id">
                            <SelectValue placeholder="Sin vehiculo asociado" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="ninguno">Sin vehiculo asociado</SelectItem>
                            {vehicles.map((vehicle) => (
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

                <div className="grid gap-2">
                    <Label htmlFor="estado">Estado</Label>
                    <Select value={data.estado} onValueChange={(value) => setData('estado', value as EstadoEquipo)}>
                        <SelectTrigger id="estado">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            {ESTADOS.map(([value, label]) => (
                                <SelectItem key={value} value={value}>
                                    {label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors.estado} />
                </div>
            </div>

            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div className="grid gap-2">
                    <Label htmlFor="ultima_atencion">Ultima atencion</Label>
                    <Input
                        id="ultima_atencion"
                        type="date"
                        value={data.ultima_atencion}
                        onChange={(e) => setData('ultima_atencion', e.target.value)}
                    />
                    <InputError message={errors.ultima_atencion} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="proxima_atencion">Proxima atencion</Label>
                    <Input
                        id="proxima_atencion"
                        type="date"
                        value={data.proxima_atencion}
                        onChange={(e) => setData('proxima_atencion', e.target.value)}
                    />
                    <InputError message={errors.proxima_atencion} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="ultima_ph">Ultimo P.H.</Label>
                    <Input id="ultima_ph" type="date" value={data.ultima_ph} onChange={(e) => setData('ultima_ph', e.target.value)} />
                    <InputError message={errors.ultima_ph} />
                </div>

                <div className="grid gap-2">
                    <Label htmlFor="proxima_ph">Proximo P.H.</Label>
                    <Input id="proxima_ph" type="date" value={data.proxima_ph} onChange={(e) => setData('proxima_ph', e.target.value)} />
                    <InputError message={errors.proxima_ph} />
                </div>
            </div>

            <div className="grid gap-2">
                <Label htmlFor="observaciones">Observaciones</Label>
                <Textarea id="observaciones" value={data.observaciones} onChange={(e) => setData('observaciones', e.target.value)} />
                <InputError message={errors.observaciones} />
            </div>

            <div className="flex items-center gap-4">
                <Button type="submit" disabled={processing}>
                    Guardar cambios
                </Button>
            </div>
        </form>
    );
}
