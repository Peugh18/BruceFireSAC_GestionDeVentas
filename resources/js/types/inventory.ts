import { type CatalogItem } from '@/types/catalog';

export interface InventoryPage<T> {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

export interface InventoryStock {
    id: number;
    catalog_item_id: number;
    stock_actual: string;
    stock_minimo: string;
    catalog_item: CatalogItem;
}

export interface InventoryUnit {
    id: number;
    serie: string;
    marca: string;
    capacidad: string;
    anio: number;
    barcode: string | null;
    estado: 'disponible' | 'vendido' | 'reservado';
    conforme: boolean;
    en_stock: boolean;
}

export interface InventoryMovement {
    id: number;
    catalog_item_id: number;
    catalog_item: CatalogItem;
    tipo: 'entrada' | 'salida' | 'ajuste';
    cantidad: string;
    stock_antes: string;
    stock_despues: string;
    motivo: string;
    referencia: string | null;
    fecha: string;
    observacion: string | null;
    usuario: { name: string } | null;
    reception: {
        proveedor: string;
        documento_referencia: string;
        cantidad: string;
        cantidad_conforme: string;
        cantidad_observada: string;
    } | null;
    withdrawn_units: { id: number; serie: string }[];
}

export const movementTypes = { entrada: 'Entrada', salida: 'Salida', ajuste: 'Ajuste' };
export const quantity = (value: string | number) => new Intl.NumberFormat('es-PE', { maximumFractionDigits: 3 }).format(Number(value));
