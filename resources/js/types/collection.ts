export type CollectionStatus = 'pendiente' | 'parcial' | 'pagada' | 'vencida';

export const collectionStatusLabels: Record<CollectionStatus, string> = {
    pendiente: 'Pendiente',
    parcial: 'Parcial',
    pagada: 'Pagada',
    vencida: 'Vencida',
};

export const collectionStatusVariants = {
    pendiente: 'outline',
    parcial: 'secondary',
    pagada: 'default',
    vencida: 'destructive',
} as const;

export const formatCollectionAmount = (amount: string | number) =>
    new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(Number(amount));

export interface CollectionDocument {
    id: number;
    numero: string;
    client: { id: number; razon_social: string; numero_documento: string };
    total: string;
    saldo: string;
    vencimiento: string;
    cuotas: number;
    cuotas_pendientes: number;
    estado: CollectionStatus;
}

export interface CollectionInstallment {
    id: number;
    numero_cuota: number;
    monto: string;
    monto_pendiente: string;
    fecha_vencimiento: string;
    estado: CollectionStatus;
}

export interface CollectionPayment {
    id: number;
    sale_installment_id: number | null;
    forma_pago: string;
    monto: string;
    referencia: string | null;
    fecha: string | null;
    observaciones: string | null;
    created_at: string;
}
