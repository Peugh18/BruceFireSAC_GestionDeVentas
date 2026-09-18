import { CatalogItem } from './catalog';
import { Client, ClientSite, User, Vehicle } from './index';

export type QuoteEstado = 'borrador' | 'emitida' | 'enviada' | 'aceptada' | 'rechazada' | 'vencida' | 'convertida' | 'anulada';
export type CondicionPropuesta = 'contado' | 'credito';

export interface QuoteItem {
    id?: number;
    quote_id?: number;
    catalog_item_id: number;
    cantidad: number;
    precio_unitario: number;
    descuento: number;
    subtotal: number;
    catalog_item?: CatalogItem;
}

export interface Quote {
    id: number;
    numero: string;
    client_id: number;
    client_site_id: number | null;
    vehicle_id: number | null;
    vendedor_user_id: number;
    fecha: string;
    vigencia: string;
    subtotal: string | number;
    igv: string | number;
    total: string | number;
    condicion_propuesta: CondicionPropuesta;
    observaciones: string | null;
    estado: QuoteEstado;
    created_at: string;
    updated_at: string;
    client?: Client;
    site?: ClientSite;
    vehicle?: Vehicle;
    vendedor?: User;
    items?: QuoteItem[];
    sale?: { id: number; numero: string };
}
