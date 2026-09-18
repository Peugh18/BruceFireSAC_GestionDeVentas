export type CatalogType = 'producto' | 'servicio' | 'repuesto';

export const catalogTypes: Record<CatalogType, string> = {
    producto: 'Producto',
    servicio: 'Servicio',
    repuesto: 'Repuesto / componente',
};

export interface CatalogItem {
    id: number;
    codigo: string;
    tipo: CatalogType;
    categoria: string;
    nombre: string;
    descripcion: string | null;
    unidad: string;
    precio: string;
    aplica_igv: boolean;
    activo: boolean;
    controla_stock: boolean;
    control_serializado: boolean;
    genera_barcode: boolean;
    tipo_tecnico: string | null;
    requiere_orden: boolean;
    requiere_certificado: boolean;
    checklist_aplicable: string | null;
    inventory_stock?: {
        id: number;
        catalog_item_id: number;
        stock_actual: string;
        stock_minimo: string;
    } | null;
}
