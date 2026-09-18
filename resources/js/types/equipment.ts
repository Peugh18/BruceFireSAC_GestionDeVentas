export type OrigenEquipo = 'vendido_bruce_fire' | 'externo' | 'desconocido';

export type EstadoEquipo = 'activo' | 'fuera_de_servicio' | 'reemplazado' | 'retirado' | 'baja_definitiva' | 'no_localizado';

export const origenEquipoLabels: Record<OrigenEquipo, string> = {
    vendido_bruce_fire: 'Vendido por BRUCE FIRE',
    externo: 'Externo',
    desconocido: 'Desconocido',
};

export const estadoEquipoLabels: Record<EstadoEquipo, string> = {
    activo: 'Activo',
    fuera_de_servicio: 'Fuera de servicio',
    reemplazado: 'Reemplazado',
    retirado: 'Retirado',
    baja_definitiva: 'Baja definitiva',
    no_localizado: 'No localizado',
};

export const NO_LEGIBLE = 'No legible / Pendiente de verificar';

export interface EquipmentClientSummary {
    id: number;
    codigo: string;
    razon_social: string;
}

export interface EquipmentClientSite {
    id: number;
    client_id: number;
    nombre: string;
}

export interface EquipmentVehicle {
    id: number;
    client_id: number;
    placa: string;
}

export interface EquipmentClientOption {
    id: number;
    codigo: string;
    razon_social: string;
    sites?: EquipmentClientSite[];
    vehicles?: EquipmentVehicle[];
}

export interface EquipmentEvent {
    id: number;
    equipment_id: number;
    tipo: string;
    descripcion: string | null;
    fecha: string;
    user_id: number | null;
    user?: { id: number; name: string } | null;
    created_at: string;
}

export interface EquipmentTransfer {
    id: number;
    equipment_id: number;
    origen_client_id: number;
    origen_client_site_id: number | null;
    destino_client_id: number;
    destino_client_site_id: number | null;
    fecha: string;
    motivo: string;
    responsable_user_id: number;
    observacion: string | null;
    origen_client?: EquipmentClientSummary;
    destino_client?: EquipmentClientSummary;
    responsable?: { id: number; name: string };
    created_at: string;
}

export interface Equipment {
    id: number;
    codigo: string;
    barcode: string;
    client_id: number;
    client_site_id: number | null;
    vehicle_id: number | null;
    origen: OrigenEquipo;
    tipo_equipo: string;
    agente: string | null;
    capacidad: string | null;
    marca: string | null;
    serie_fabricante: string | null;
    anio_fabricacion: string | null;
    ubicacion: string | null;
    estado: EstadoEquipo;
    ultima_atencion: string | null;
    proxima_atencion: string | null;
    ultima_ph: string | null;
    proxima_ph: string | null;
    observaciones: string | null;
    created_at: string;
    updated_at: string;
    client?: EquipmentClientSummary;
    client_site?: EquipmentClientSite | null;
    vehicle?: EquipmentVehicle | null;
    transfers?: EquipmentTransfer[];
    events?: EquipmentEvent[];
    [key: string]: unknown;
}
