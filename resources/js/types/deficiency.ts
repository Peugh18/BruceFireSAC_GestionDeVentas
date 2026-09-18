import { CatalogItem } from './catalog';
import { Equipment } from './equipment';
import { User } from './index';
import { Quote } from './quote';
import { ServiceOrder } from './service-order';

export type DeficiencyEstado =
    | 'detectada'
    | 'esperando_autorizacion'
    | 'autorizada'
    | 'rechazada'
    | 'en_correccion'
    | 'resuelta';

export type DeficiencyAuthorizationCanal = 'whatsapp' | 'presencial';

export interface DeficiencyAuthorizationData {
    id: number;
    deficiency_id: number;
    quote_id: number | null;
    autorizado_por: string;
    canal: DeficiencyAuthorizationCanal;
    fecha: string;
    observacion: string | null;
    created_at: string;
    quote?: Quote | null;
}

export interface DeficiencyData {
    id: number;
    service_order_id: number;
    equipment_id: number;
    checklist_item_id: number | null;
    componente: string;
    condicion: string;
    foto_path: string | null;
    nota: string | null;
    accion_recomendada: string | null;
    repuesto_sugerido: string | null;
    catalog_item_id: number | null;
    requiere_autorizacion: boolean;
    estado: DeficiencyEstado;
    resolucion: string | null;
    resuelto_por_user_id: number | null;
    resuelto_en: string | null;
    created_at: string;
    updated_at: string;
    service_order?: ServiceOrder;
    equipment?: Equipment;
    catalog_item?: CatalogItem;
    resuelto_por_user?: User;
    authorizations?: DeficiencyAuthorizationData[];
}
