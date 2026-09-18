export type ReportCategory =
    | 'comerciales'
    | 'inventario'
    | 'servicios'
    | 'equipos'
    | 'certificados'
    | 'facturacion'
    | 'cobranzas';

export interface ComercialesReport {
    resumen: {
        total_vendido: number;
        total_transacciones: number;
        total_cotizaciones: number;
        cotizaciones_aceptadas: number;
        cotizaciones_rechazadas: number;
        cotizaciones_pendientes: number;
        tasa_conversion: number;
    };
    ventas_periodo: {
        fecha: string;
        total_ventas: number;
        subtotal: number;
        igv: number;
        monto_total: number;
    }[];
    ventas_vendedor: {
        id: number;
        vendedor: string;
        total_ventas: number;
        monto_total: number;
    }[];
    ventas_cliente: {
        id: number;
        cliente: string;
        numero_documento: string;
        total_ventas: number;
        monto_total: number;
    }[];
    ventas_producto: {
        id: number;
        codigo: string;
        nombre: string;
        tipo: string;
        cantidad_vendida: number;
        monto_total: number;
    }[];
}

export interface InventarioReport {
    resumen: {
        total_items: number;
        total_bajo_minimo: number;
        total_movimientos: number;
    };
    stock_actual: {
        id: number;
        codigo: string;
        nombre: string;
        tipo: string;
        categoria: string;
        unidad: string;
        stock_actual: number;
        stock_minimo: number;
        bajo_minimo: number;
    }[];
    movimientos: {
        id: number;
        fecha: string;
        tipo: string;
        cantidad: number;
        stock_antes: number;
        stock_despues: number;
        motivo: string;
        catalog_item?: {
            id: number;
            codigo: string;
            nombre: string;
            unidad: string;
        } | null;
        usuario?: {
            id: number;
            name: string;
        } | null;
    }[];
    rotacion: {
        id: number;
        codigo: string;
        nombre: string;
        tipo: string;
        unidad: string;
        total_salidas: number;
        num_movimientos: number;
    }[];
    bajo_minimo: {
        codigo: string;
        nombre: string;
        categoria: string;
        unidad: string;
        stock_actual: number;
        stock_minimo: number;
        faltante: number;
    }[];
}

export interface ServiciosReport {
    resumen: {
        total_ordenes: number;
        total_deficiencias: number;
    };
    ordenes_por_estado: {
        estado: string;
        label: string;
        total: number;
    }[];
    tiempo_promedio_estado: {
        estado: string;
        estado_nombre: string;
        promedio_horas: number;
        transiciones_analizadas: number;
    }[];
    deficiencias_por_tipo: {
        componente: string;
        total: number;
        resueltas: number;
        autorizadas: number;
        detectadas: number;
    }[];
}

export interface EquiposReport {
    resumen: {
        total_equipos: number;
        total_proximos_o_vencidos: number;
    };
    proximos_atencion: {
        id: number;
        codigo: string;
        cliente: string;
        tipo_equipo: string;
        ubicacion: string;
        proxima_atencion: string | null;
        proxima_ph: string | null;
        atencion_vencida: boolean;
        ph_vencida: boolean;
        estado: string;
    }[];
    equipos_por_estado: {
        estado: string;
        total: number;
    }[];
    equipos_por_cliente: {
        id: number;
        cliente: string;
        total_equipos: number;
    }[];
}

export interface CertificadosReport {
    resumen: {
        total_certificados: number;
    };
    por_estado: {
        estado: string;
        label: string;
        total: number;
    }[];
    por_tipo: {
        tipo: string;
        label: string;
        total: number;
    }[];
    certificados: {
        id: number;
        numero: string;
        tipo: string;
        cliente: string;
        orden_codigo: string;
        fecha_emision: string;
        fecha_vigencia: string;
        estado: string;
    }[];
}

export interface FacturacionReport {
    resumen: {
        total_documentos: number;
        total_errores: number;
        total_ventas_monto: number;
    };
    documentos_por_estado: {
        tipo: string;
        estado: string;
        total: number;
    }[];
    errores_sunat: {
        id: number;
        documento: string;
        tipo: string;
        estado: string;
        error: string;
        respuesta_sunat: string | null;
        intentos: number;
        fecha_envio: string;
    }[];
    ventas_contado_credito: {
        condicion_pago: string;
        total_ventas: number;
        monto_total: number;
    }[];
}

export interface CobranzasReport {
    resumen: {
        total_por_cobrar: number;
        total_vencido: number;
        total_cobrado_periodo: number;
    };
    cuotas_pendientes: {
        id: number;
        venta_numero: string;
        cliente: string;
        numero_cuota: number;
        monto: number;
        monto_pendiente: number;
        fecha_vencimiento: string;
        vencida: boolean;
        estado: string;
    }[];
    cobrado_por_medio: {
        forma_pago: string;
        total_operaciones: number;
        total_monto: number;
    }[];
}
