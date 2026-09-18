export interface CustodyUser {
    id: number;
    name: string;
}

export interface PickupPhoto {
    id: number;
    url: string;
    file_name: string;
    size: string;
}

export interface ServiceOrderPickupItem {
    id: number;
    service_order_id: number;
    client_id: number;
    client_site_id: number | null;
    contacto: string;
    fecha_hora_recojo: string;
    cantidad: number;
    observaciones: string | null;
    conforme_nombre: string | null;
    conforme_dni: string | null;
    conforme_firma: string | null;
    recogido_por_user_id: number | null;
    recogido_en: string | null;
    recibido_planta_user_id: number | null;
    recibido_planta_en: string | null;
    entregado_por_user_id: number | null;
    entregado_en: string | null;
    recibido_cliente_por_user_id: number | null;
    recibido_cliente_en: string | null;
    recibido_cliente_nombre: string | null;
    created_at: string;
    updated_at: string;

    service_order?: {
        id: number;
        numero_orden?: string;
        codigo?: string;
        estado?: string;
    };
    client?: {
        id: number;
        razon_social: string;
        nombre_comercial?: string | null;
        numero_documento?: string;
    };
    site?: {
        id: number;
        nombre: string;
        direccion?: string | null;
    } | null;

    recogido_por_user?: CustodyUser | null;
    recibido_planta_user?: CustodyUser | null;
    entregado_por_user?: CustodyUser | null;
    recibido_cliente_por_user?: CustodyUser | null;
}
