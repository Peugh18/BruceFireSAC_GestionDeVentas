export type MotivoTraslado = 'venta' | 'compra' | 'traslado_entre_establecimientos' | 'importacion' | 'exportacion' | 'otros';
export type ModalidadTraslado = 'transporte_publico' | 'transporte_privado';
export type ShippingGuideEstado = 'pendiente' | 'aceptado' | 'rechazado' | 'error';

export interface ShippingGuideItemData {
    id: number;
    shipping_guide_id: number;
    sale_item_id: number | null;
    descripcion: string;
    cantidad: number;
    unidad: string;
    peso: number | string | null;
}

export interface ShippingGuideClient {
    id: number;
    codigo: string;
    razon_social: string;
}

export interface ShippingGuideSale {
    id: number;
    numero: string;
}

export interface ShippingGuideData {
    id: number;
    sale_id: number | null;
    motivo_traslado: MotivoTraslado;
    fecha_inicio: string;
    origen: string;
    destino: string;
    destinatario_client_id: number | null;
    destinatario_nombre: string | null;
    destinatario_documento: string | null;
    peso_total: number | string;
    modalidad: ModalidadTraslado;
    transportista_razon_social: string | null;
    transportista_ruc: string | null;
    vehiculo_placa: string | null;
    conductor_nombre: string | null;
    conductor_licencia: string | null;
    observaciones: string | null;
    serie: string;
    correlativo: string;
    estado: ShippingGuideEstado;
    respuesta_sunat: string | null;
    error: string | null;
    intentos: number;
    fecha_envio: string | null;
    created_at: string;
    updated_at: string;
    sale?: ShippingGuideSale | null;
    destinatario_client?: ShippingGuideClient | null;
    items?: ShippingGuideItemData[];
}
