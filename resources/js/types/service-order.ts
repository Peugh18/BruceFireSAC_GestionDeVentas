export interface TechnicianOption {
    id: number;
    name: string;
}

export interface ServiceOrderEquipment {
    id: number;
    codigo: string;
    tipo_equipo: string;
    capacidad: string | null;
    client_id: number;
    client_site_id: number | null;
    vehicle_id: number | null;
}

export interface ServiceOrderClient {
    id: number;
    codigo: string;
    razon_social: string;
    sites: { id: number; nombre: string }[];
    vehicles: { id: number; placa: string }[];
}

export interface ServiceOrder {
    id: number;
    codigo: string;
    fecha: string;
    tipo_servicio: string;
    prioridad: string;
    estado: string;
    observaciones: string | null;
    sale_id: number | null;
    tecnico_user_id: number | null;
    client: { id: number; codigo: string; razon_social: string };
    client_site: { id: number; nombre: string } | null;
    vehicle: { id: number; placa: string } | null;
    tecnico: TechnicianOption | null;
    equipment_count: number;
    equipment: ServiceOrderEquipment[];
    status_history: {
        id: number;
        estado_anterior: string | null;
        estado: string;
        observaciones: string | null;
        user: TechnicianOption | null;
        created_at: string;
    }[];
}

export interface ServiceOrderLabels {
    statuses: Record<string, string>;
    serviceTypes: Record<string, string>;
    priorities: Record<string, string>;
}
