import { CatalogItem } from './catalog';
import { Client, ClientSite, User, Vehicle } from './index';
import { InventoryUnit } from './inventory';
import { Quote } from './quote';

export type CondicionPago = 'contado' | 'credito';
export type SaleEstado = 'pendiente' | 'completada' | 'anulada';
export type FormaPago = 'efectivo' | 'transferencia' | 'yape' | 'plin' | 'pos' | 'deposito' | 'otro';
export type InstallmentEstado = 'pendiente' | 'pagado_parcial' | 'pagado' | 'vencido';

export interface SaleItem {
    id?: number;
    sale_id?: number;
    catalog_item_id: number;
    inventory_unit_id?: number | null;
    cantidad: number;
    precio_unitario: number;
    descuento: number;
    subtotal: number;
    catalog_item?: CatalogItem;
    inventory_unit?: InventoryUnit | null;
}

export interface SalePayment {
    id?: number;
    sale_id?: number;
    forma_pago: FormaPago;
    monto: number;
    referencia: string | null;
}

export interface SaleInstallment {
    id?: number;
    sale_id?: number;
    numero_cuota: number;
    monto: number;
    monto_pendiente: number;
    fecha_vencimiento: string;
    estado: InstallmentEstado;
}

export interface Sale {
    id: number;
    numero: string;
    quote_id: number | null;
    client_id: number;
    client_site_id: number | null;
    vehicle_id: number | null;
    vendedor_user_id: number;
    fecha: string;
    condicion_pago: CondicionPago;
    subtotal: string | number;
    igv: string | number;
    total: string | number;
    estado: SaleEstado;
    observaciones: string | null;
    service_order_id: number | null;
    created_at: string;
    updated_at: string;
    client?: Client;
    site?: ClientSite;
    vehicle?: Vehicle;
    vendedor?: User;
    quote?: Quote;
    items?: SaleItem[];
    payments?: SalePayment[];
    installments?: SaleInstallment[];
}
