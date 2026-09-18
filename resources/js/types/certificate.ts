import { Equipment } from './equipment';
import { ServiceOrder } from './service-order';
import { User } from './index';

export type CertificateTipo = 'operatividad_garantia' | 'prueba_hidrostatica' | 'capacitacion' | 'deteccion_alarma' | 'otro';

export type CertificateEstado = 'vigente' | 'vencido' | 'reemplazado' | 'anulado';

export interface CertificateItemData {
    id: number;
    certificate_id: number;
    equipment_id: number;
    equipment: Equipment;
}

export interface CertificateData {
    id: number;
    service_order_id: number;
    tipo: CertificateTipo;
    numero: string;
    token: string;
    estado: CertificateEstado;
    fecha_emision: string;
    fecha_vigencia: string | null;
    observaciones: string | null;
    generado_por_user_id: number | null;
    created_at: string;
    updated_at: string;
    service_order?: ServiceOrder;
    items?: CertificateItemData[];
    generado_por_user?: User | null;
}
