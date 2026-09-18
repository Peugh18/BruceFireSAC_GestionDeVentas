import { Equipment } from './equipment';
import { User } from './index';
import { ServiceOrder } from './service-order';

export type CondicionComponente = 'conforme' | 'observado' | 'no_aplica';

export interface ChecklistItemData {
    id?: number;
    checklist_id?: number;
    componente: string;
    condicion: CondicionComponente;
    foto_path?: string | null;
    nota?: string | null;
    accion_recomendada?: string | null;
    repuesto_sugerido?: string | null;
    catalog_item_id?: number | null;
    requiere_autorizacion?: boolean;
}

export interface ServiceOrderChecklistData {
    id: number;
    service_order_id: number;
    equipment_id: number;
    completado_por_user_id: number | null;
    completado_en: string | null;
    estado: 'borrador' | 'completado';
    created_at: string;
    updated_at: string;
    items?: ChecklistItemData[];
    service_order?: ServiceOrder;
    equipment?: Equipment;
    completado_por_user?: User;
}
