export type CreditDebitNoteTipo = 'nota_credito' | 'nota_debito';
export type CreditDebitNoteEstado = 'pendiente' | 'aceptado' | 'rechazado' | 'error';

export interface CreditDebitNoteData {
    id: number;
    cpe_afectado_id: number;
    tipo: CreditDebitNoteTipo;
    motivo: string;
    detalle: string;
    importe: number | string;
    fecha: string;
    serie: string;
    correlativo: string;
    estado: CreditDebitNoteEstado;
    respuesta_sunat: string | null;
    error: string | null;
    intentos: number;
    fecha_envio: string | null;
    created_at: string;
}
