import { type CreditDebitNoteData } from './credit-debit-note';

export type ElectronicDocumentTipo = 'factura' | 'boleta';
export type ElectronicDocumentEstado = 'pendiente' | 'aceptado' | 'rechazado' | 'error';

export interface ElectronicDocumentData {
    id: number;
    sale_id: number;
    tipo: ElectronicDocumentTipo;
    serie: string;
    correlativo: string;
    xml_path: string | null;
    cdr_path: string | null;
    hash: string | null;
    estado: ElectronicDocumentEstado;
    respuesta_sunat: string | null;
    error: string | null;
    intentos: number;
    fecha_envio: string | null;
    created_at: string;
    updated_at: string;
    credit_debit_notes?: CreditDebitNoteData[];
}
