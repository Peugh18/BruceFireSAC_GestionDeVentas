import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import {
    type CertificadosReport,
    type CobranzasReport,
    type ComercialesReport,
    type EquiposReport,
    type FacturacionReport,
    type InventarioReport,
    type ReportCategory,
    type ServiciosReport,
} from '@/types/report';
import { Head, router } from '@inertiajs/react';
import { Download, Filter } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Reportes y Analítica', href: '/reports' }];

interface Props {
    categoria: ReportCategory;
    filters: {
        fecha_desde: string;
        fecha_hasta: string;
    };
    reportData:
        | ComercialesReport
        | InventarioReport
        | ServiciosReport
        | EquiposReport
        | CertificadosReport
        | FacturacionReport
        | CobranzasReport;
}

function formatMoney(amount: number | string | null | undefined): string {
    const num = Number(amount) || 0;
    return new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(num);
}

export default function ReportsIndex({ categoria, filters, reportData }: Props) {
    const [from, setFrom] = useState(filters.fecha_desde);
    const [to, setTo] = useState(filters.fecha_hasta);

    const handleCategoryChange = (newCat: string) => {
        router.get(
            route('reports.index'),
            {
                categoria: newCat,
                fecha_desde: from,
                fecha_hasta: to,
            },
            { preserveState: true, replace: true },
        );
    };

    const handleFilterSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        router.get(
            route('reports.index'),
            {
                categoria,
                fecha_desde: from,
                fecha_hasta: to,
            },
            { preserveState: true, replace: true },
        );
    };

    const setPresetDates = (type: 'this_month' | 'last_30_days' | 'this_year') => {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');

        let start = '';
        const end = `${yyyy}-${mm}-${dd}`;

        if (type === 'this_month') {
            start = `${yyyy}-${mm}-01`;
        } else if (type === 'last_30_days') {
            const past30 = new Date();
            past30.setDate(past30.getDate() - 30);
            start = past30.toISOString().slice(0, 10);
        } else if (type === 'this_year') {
            start = `${yyyy}-01-01`;
        }

        setFrom(start);
        setTo(end);

        router.get(
            route('reports.index'),
            {
                categoria,
                fecha_desde: start,
                fecha_hasta: end,
            },
            { preserveState: true, replace: true },
        );
    };

    const triggerExport = (subreporte: string) => {
        const url = route('reports.export', {
            categoria,
            subreporte,
            fecha_desde: from,
            fecha_hasta: to,
        });
        window.open(url, '_blank');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reportes y Analítica" />

            <div className="flex flex-1 flex-col gap-6 p-4 max-w-7xl mx-auto w-full">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Reportes y Analítica</h1>
                        <p className="text-sm text-muted-foreground">
                            Consultas agregadas e indicadores operativos de BRUCE FIRE S.A.C.
                        </p>
                    </div>
                </div>

                {/* Filtros de Fecha */}
                <Card className="border">
                    <CardHeader className="py-3 px-4">
                        <CardTitle className="text-sm font-medium flex items-center gap-2">
                            <Filter className="h-4 w-4 text-muted-foreground" />
                            Filtros de Periodo
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="py-2 px-4">
                        <form onSubmit={handleFilterSubmit} className="flex flex-wrap items-end gap-3">
                            <div className="space-y-1">
                                <Label htmlFor="fecha_desde" className="text-xs">
                                    Fecha Desde
                                </Label>
                                <Input
                                    id="fecha_desde"
                                    type="date"
                                    className="h-8 text-xs w-36"
                                    value={from}
                                    onChange={(e) => setFrom(e.target.value)}
                                />
                            </div>
                            <div className="space-y-1">
                                <Label htmlFor="fecha_hasta" className="text-xs">
                                    Fecha Hasta
                                </Label>
                                <Input
                                    id="fecha_hasta"
                                    type="date"
                                    className="h-8 text-xs w-36"
                                    value={to}
                                    onChange={(e) => setTo(e.target.value)}
                                />
                            </div>
                            <Button type="submit" size="sm" variant="secondary" className="h-8 text-xs">
                                Aplicar
                            </Button>
                            <div className="flex items-center gap-1 ml-auto">
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    className="h-8 text-xs"
                                    onClick={() => setPresetDates('this_month')}
                                >
                                    Este Mes
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    className="h-8 text-xs"
                                    onClick={() => setPresetDates('last_30_days')}
                                >
                                    Últimos 30 días
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    className="h-8 text-xs"
                                    onClick={() => setPresetDates('this_year')}
                                >
                                    Este Año
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                {/* Tabs de Categorías */}
                <Tabs defaultValue={categoria} value={categoria} onValueChange={handleCategoryChange} className="w-full">
                    <TabsList className="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-7 w-full h-auto p-1 bg-muted/60">
                        <TabsTrigger value="comerciales" className="text-xs py-2">
                            Comerciales
                        </TabsTrigger>
                        <TabsTrigger value="inventario" className="text-xs py-2">
                            Inventario
                        </TabsTrigger>
                        <TabsTrigger value="servicios" className="text-xs py-2">
                            Servicios
                        </TabsTrigger>
                        <TabsTrigger value="equipos" className="text-xs py-2">
                            Equipos
                        </TabsTrigger>
                        <TabsTrigger value="certificados" className="text-xs py-2">
                            Certificados
                        </TabsTrigger>
                        <TabsTrigger value="facturacion" className="text-xs py-2">
                            Facturación
                        </TabsTrigger>
                        <TabsTrigger value="cobranzas" className="text-xs py-2">
                            Cobranzas
                        </TabsTrigger>
                    </TabsList>

                    {/* 1. COMERCIALES */}
                    <TabsContent value="comerciales" className="space-y-6 pt-4">
                        {(() => {
                            const d = reportData as ComercialesReport;
                            return (
                                <>
                                    {/* Resumen KPIs */}
                                    <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                                        <Card>
                                            <CardHeader className="pb-2">
                                                <CardDescription className="text-xs">Monto Total Vendido</CardDescription>
                                                <CardTitle className="text-2xl font-bold text-primary">
                                                    {formatMoney(d.resumen.total_vendido)}
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent className="text-xs text-muted-foreground">
                                                {d.resumen.total_transacciones} ventas en el periodo
                                            </CardContent>
                                        </Card>
                                        <Card>
                                            <CardHeader className="pb-2">
                                                <CardDescription className="text-xs">Cotizaciones Emitidas</CardDescription>
                                                <CardTitle className="text-2xl font-bold">{d.resumen.total_cotizaciones}</CardTitle>
                                            </CardHeader>
                                            <CardContent className="text-xs text-muted-foreground">
                                                {d.resumen.cotizaciones_pendientes} en curso / pendientes
                                            </CardContent>
                                        </Card>
                                        <Card>
                                            <CardHeader className="pb-2">
                                                <CardDescription className="text-xs">Cotizaciones Aceptadas</CardDescription>
                                                <CardTitle className="text-2xl font-bold text-emerald-600">
                                                    {d.resumen.cotizaciones_aceptadas}
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent className="text-xs text-muted-foreground">
                                                {d.resumen.cotizaciones_rechazadas} rechazadas
                                            </CardContent>
                                        </Card>
                                        <Card>
                                            <CardHeader className="pb-2">
                                                <CardDescription className="text-xs">Tasa de Conversión</CardDescription>
                                                <CardTitle className="text-2xl font-bold text-blue-600">
                                                    {d.resumen.tasa_conversion}%
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent className="text-xs text-muted-foreground">
                                                Aceptadas sobre total cotizaciones
                                            </CardContent>
                                        </Card>
                                    </div>

                                    {/* Ventas por Periodo */}
                                    <Card>
                                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                                            <div>
                                                <CardTitle className="text-base">Ventas por Día</CardTitle>
                                                <CardDescription className="text-xs">Desglose de transacciones e importes</CardDescription>
                                            </div>
                                            <Button size="sm" variant="outline" onClick={() => triggerExport('periodo')}>
                                                <Download className="h-3.5 w-3.5 mr-1" /> Exportar CSV
                                            </Button>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="rounded-md border">
                                                <Table>
                                                    <TableHeader>
                                                        <TableRow>
                                                            <TableHead>Fecha</TableHead>
                                                            <TableHead className="text-right">Transacciones</TableHead>
                                                            <TableHead className="text-right">Subtotal</TableHead>
                                                            <TableHead className="text-right">IGV</TableHead>
                                                            <TableHead className="text-right">Total</TableHead>
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        {d.ventas_periodo.length === 0 ? (
                                                            <TableRow>
                                                                <TableCell colSpan={5} className="text-center text-muted-foreground py-4">
                                                                    No hay ventas registradas en este periodo.
                                                                </TableCell>
                                                            </TableRow>
                                                        ) : (
                                                            d.ventas_periodo.map((v, i) => (
                                                                <TableRow key={i}>
                                                                    <TableCell className="font-medium">{v.fecha}</TableCell>
                                                                    <TableCell className="text-right">{v.total_ventas}</TableCell>
                                                                    <TableCell className="text-right">{formatMoney(v.subtotal)}</TableCell>
                                                                    <TableCell className="text-right">{formatMoney(v.igv)}</TableCell>
                                                                    <TableCell className="text-right font-semibold">
                                                                        {formatMoney(v.monto_total)}
                                                                    </TableCell>
                                                                </TableRow>
                                                            ))
                                                        )}
                                                    </TableBody>
                                                </Table>
                                            </div>
                                        </CardContent>
                                    </Card>

                                    {/* Ventas por Vendedor y Cliente */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <Card>
                                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                                <div>
                                                    <CardTitle className="text-base">Ventas por Vendedor</CardTitle>
                                                    <CardDescription className="text-xs">Desempeño comercial por asesor</CardDescription>
                                                </div>
                                                <Button size="sm" variant="outline" onClick={() => triggerExport('vendedor')}>
                                                    <Download className="h-3.5 w-3.5 mr-1" /> Exportar
                                                </Button>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="rounded-md border">
                                                    <Table>
                                                        <TableHeader>
                                                            <TableRow>
                                                                <TableHead>Vendedor</TableHead>
                                                                <TableHead className="text-right">Ventas</TableHead>
                                                                <TableHead className="text-right">Total</TableHead>
                                                            </TableRow>
                                                        </TableHeader>
                                                        <TableBody>
                                                            {d.ventas_vendedor.length === 0 ? (
                                                                <TableRow>
                                                                    <TableCell colSpan={3} className="text-center text-muted-foreground py-4">
                                                                        Sin datos.
                                                                    </TableCell>
                                                                </TableRow>
                                                            ) : (
                                                                d.ventas_vendedor.map((row) => (
                                                                    <TableRow key={row.id}>
                                                                        <TableCell className="font-medium">{row.vendedor}</TableCell>
                                                                        <TableCell className="text-right">{row.total_ventas}</TableCell>
                                                                        <TableCell className="text-right font-semibold">
                                                                            {formatMoney(row.monto_total)}
                                                                        </TableCell>
                                                                    </TableRow>
                                                                ))
                                                            )}
                                                        </TableBody>
                                                    </Table>
                                                </div>
                                            </CardContent>
                                        </Card>

                                        <Card>
                                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                                <div>
                                                    <CardTitle className="text-base">Ventas por Cliente</CardTitle>
                                                    <CardDescription className="text-xs">Top clientes por facturación</CardDescription>
                                                </div>
                                                <Button size="sm" variant="outline" onClick={() => triggerExport('cliente')}>
                                                    <Download className="h-3.5 w-3.5 mr-1" /> Exportar
                                                </Button>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="rounded-md border">
                                                    <Table>
                                                        <TableHeader>
                                                            <TableRow>
                                                                <TableHead>Cliente</TableHead>
                                                                <TableHead className="text-right">Ventas</TableHead>
                                                                <TableHead className="text-right">Total</TableHead>
                                                            </TableRow>
                                                        </TableHeader>
                                                        <TableBody>
                                                            {d.ventas_cliente.length === 0 ? (
                                                                <TableRow>
                                                                    <TableCell colSpan={3} className="text-center text-muted-foreground py-4">
                                                                        Sin datos.
                                                                    </TableCell>
                                                                </TableRow>
                                                            ) : (
                                                                d.ventas_cliente.map((row) => (
                                                                    <TableRow key={row.id}>
                                                                        <TableCell className="font-medium">
                                                                            <p className="truncate max-w-[200px]">{row.cliente}</p>
                                                                            <span className="text-[10px] text-muted-foreground">
                                                                                {row.numero_documento}
                                                                            </span>
                                                                        </TableCell>
                                                                        <TableCell className="text-right">{row.total_ventas}</TableCell>
                                                                        <TableCell className="text-right font-semibold">
                                                                            {formatMoney(row.monto_total)}
                                                                        </TableCell>
                                                                    </TableRow>
                                                                ))
                                                            )}
                                                        </TableBody>
                                                    </Table>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    </div>

                                    {/* Ventas por Producto / Servicio */}
                                    <Card>
                                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                                            <div>
                                                <CardTitle className="text-base">Ventas por Producto o Servicio</CardTitle>
                                                <CardDescription className="text-xs">Ítems del catálogo con mayor demanda</CardDescription>
                                            </div>
                                            <Button size="sm" variant="outline" onClick={() => triggerExport('producto')}>
                                                <Download className="h-3.5 w-3.5 mr-1" /> Exportar CSV
                                            </Button>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="rounded-md border">
                                                <Table>
                                                    <TableHeader>
                                                        <TableRow>
                                                            <TableHead>Código</TableHead>
                                                            <TableHead>Nombre</TableHead>
                                                            <TableHead>Tipo</TableHead>
                                                            <TableHead className="text-right">Cant. Vendida</TableHead>
                                                            <TableHead className="text-right">Monto Total</TableHead>
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        {d.ventas_producto.length === 0 ? (
                                                            <TableRow>
                                                                <TableCell colSpan={5} className="text-center text-muted-foreground py-4">
                                                                    Sin datos de productos vendidos.
                                                                </TableCell>
                                                            </TableRow>
                                                        ) : (
                                                            d.ventas_producto.map((p) => (
                                                                <TableRow key={p.id}>
                                                                    <TableCell className="font-mono text-xs">{p.codigo}</TableCell>
                                                                    <TableCell className="font-medium">{p.nombre}</TableCell>
                                                                    <TableCell className="capitalize">{p.tipo}</TableCell>
                                                                    <TableCell className="text-right">{p.cantidad_vendida}</TableCell>
                                                                    <TableCell className="text-right font-semibold">
                                                                        {formatMoney(p.monto_total)}
                                                                    </TableCell>
                                                                </TableRow>
                                                            ))
                                                        )}
                                                    </TableBody>
                                                </Table>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </>
                            );
                        })()}
                    </TabsContent>

                    {/* 2. INVENTARIO */}
                    <TabsContent value="inventario" className="space-y-6 pt-4">
                        {(() => {
                            const d = reportData as InventarioReport;
                            return (
                                <>
                                    {/* Resumen KPIs */}
                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <Card>
                                            <CardHeader className="pb-2">
                                                <CardDescription className="text-xs">Ítems en Inventario</CardDescription>
                                                <CardTitle className="text-2xl font-bold">{d.resumen.total_items}</CardTitle>
                                            </CardHeader>
                                            <CardContent className="text-xs text-muted-foreground">
                                                Artículos con control de existencias
                                            </CardContent>
                                        </Card>
                                        <Card>
                                            <CardHeader className="pb-2">
                                                <CardDescription className="text-xs">Bajo Stock Mínimo</CardDescription>
                                                <CardTitle className="text-2xl font-bold text-destructive">
                                                    {d.resumen.total_bajo_minimo}
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent className="text-xs text-muted-foreground">
                                                Requieren reposición urgente
                                            </CardContent>
                                        </Card>
                                        <Card>
                                            <CardHeader className="pb-2">
                                                <CardDescription className="text-xs">Movimientos Registrados</CardDescription>
                                                <CardTitle className="text-2xl font-bold">{d.resumen.total_movimientos}</CardTitle>
                                            </CardHeader>
                                            <CardContent className="text-xs text-muted-foreground">
                                                En el periodo seleccionado
                                            </CardContent>
                                        </Card>
                                    </div>

                                    {/* Items bajo stock mínimo */}
                                    {d.bajo_minimo.length > 0 && (
                                        <Card className="border-destructive/40">
                                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                                <div>
                                                    <CardTitle className="text-base text-destructive">
                                                        Alerta: Artículos Bajo Stock Mínimo
                                                    </CardTitle>
                                                    <CardDescription className="text-xs">
                                                        Ítems cuyo stock físico es menor o igual al umbral mínimo
                                                    </CardDescription>
                                                </div>
                                                <Button size="sm" variant="outline" onClick={() => triggerExport('bajo_minimo')}>
                                                    <Download className="h-3.5 w-3.5 mr-1" /> Exportar
                                                </Button>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="rounded-md border">
                                                    <Table>
                                                        <TableHeader>
                                                            <TableRow>
                                                                <TableHead>Código</TableHead>
                                                                <TableHead>Artículo</TableHead>
                                                                <TableHead>Categoría</TableHead>
                                                                <TableHead className="text-right">Stock Actual</TableHead>
                                                                <TableHead className="text-right">Stock Mínimo</TableHead>
                                                                <TableHead className="text-right">Faltante</TableHead>
                                                            </TableRow>
                                                        </TableHeader>
                                                        <TableBody>
                                                            {d.bajo_minimo.map((item, idx) => (
                                                                <TableRow key={idx}>
                                                                    <TableCell className="font-mono text-xs">{item.codigo}</TableCell>
                                                                    <TableCell className="font-medium">{item.nombre}</TableCell>
                                                                    <TableCell>{item.categoria}</TableCell>
                                                                    <TableCell className="text-right font-bold text-destructive">
                                                                        {item.stock_actual} {item.unidad}
                                                                    </TableCell>
                                                                    <TableCell className="text-right">
                                                                        {item.stock_minimo} {item.unidad}
                                                                    </TableCell>
                                                                    <TableCell className="text-right text-destructive font-semibold">
                                                                        {item.faltante} {item.unidad}
                                                                    </TableCell>
                                                                </TableRow>
                                                            ))}
                                                        </TableBody>
                                                    </Table>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    )}

                                    {/* Repuestos / Artículos de Mayor Rotación */}
                                    <Card>
                                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                                            <div>
                                                <CardTitle className="text-base">Repuestos y Artículos con Más Rotación</CardTitle>
                                                <CardDescription className="text-xs">
                                                    Mayor volumen de salidas de almacén en el periodo
                                                </CardDescription>
                                            </div>
                                            <Button size="sm" variant="outline" onClick={() => triggerExport('rotacion')}>
                                                <Download className="h-3.5 w-3.5 mr-1" /> Exportar
                                            </Button>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="rounded-md border">
                                                <Table>
                                                    <TableHeader>
                                                        <TableRow>
                                                            <TableHead>Código</TableHead>
                                                            <TableHead>Artículo</TableHead>
                                                            <TableHead>Tipo</TableHead>
                                                            <TableHead className="text-right">Total Salidas</TableHead>
                                                            <TableHead className="text-right">Nro. Movimientos</TableHead>
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        {d.rotacion.length === 0 ? (
                                                            <TableRow>
                                                                <TableCell colSpan={5} className="text-center text-muted-foreground py-4">
                                                                    Sin registros de salidas en el periodo.
                                                                </TableCell>
                                                            </TableRow>
                                                        ) : (
                                                            d.rotacion.map((r) => (
                                                                <TableRow key={r.id}>
                                                                    <TableCell className="font-mono text-xs">{r.codigo}</TableCell>
                                                                    <TableCell className="font-medium">{r.nombre}</TableCell>
                                                                    <TableCell className="capitalize">{r.tipo}</TableCell>
                                                                    <TableCell className="text-right font-semibold">
                                                                        {r.total_salidas} {r.unidad}
                                                                    </TableCell>
                                                                    <TableCell className="text-right">{r.num_movimientos}</TableCell>
                                                                </TableRow>
                                                            ))
                                                        )}
                                                    </TableBody>
                                                </Table>
                                            </div>
                                        </CardContent>
                                    </Card>

                                    {/* Stock Actual General */}
                                    <Card>
                                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                                            <div>
                                                <CardTitle className="text-base">Stock Físico Actual</CardTitle>
                                                <CardDescription className="text-xs">Inventario consolidado</CardDescription>
                                            </div>
                                            <Button size="sm" variant="outline" onClick={() => triggerExport('stock_actual')}>
                                                <Download className="h-3.5 w-3.5 mr-1" /> Exportar
                                            </Button>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="rounded-md border max-h-96 overflow-y-auto">
                                                <Table>
                                                    <TableHeader>
                                                        <TableRow>
                                                            <TableHead>Código</TableHead>
                                                            <TableHead>Artículo</TableHead>
                                                            <TableHead>Categoría</TableHead>
                                                            <TableHead className="text-right">Stock Actual</TableHead>
                                                            <TableHead className="text-right">Stock Mínimo</TableHead>
                                                            <TableHead className="text-center">Estado</TableHead>
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        {d.stock_actual.map((s) => (
                                                            <TableRow key={s.id}>
                                                                <TableCell className="font-mono text-xs">{s.codigo}</TableCell>
                                                                <TableCell className="font-medium">{s.nombre}</TableCell>
                                                                <TableCell>{s.categoria}</TableCell>
                                                                <TableCell className="text-right font-medium">
                                                                    {s.stock_actual} {s.unidad}
                                                                </TableCell>
                                                                <TableCell className="text-right">
                                                                    {s.stock_minimo} {s.unidad}
                                                                </TableCell>
                                                                <TableCell className="text-center">
                                                                    {s.bajo_minimo ? (
                                                                        <Badge variant="destructive">Bajo Mínimo</Badge>
                                                                    ) : (
                                                                        <Badge variant="secondary">Normal</Badge>
                                                                    )}
                                                                </TableCell>
                                                            </TableRow>
                                                        ))}
                                                    </TableBody>
                                                </Table>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </>
                            );
                        })()}
                    </TabsContent>

                    {/* 3. SERVICIOS */}
                    <TabsContent value="servicios" className="space-y-6 pt-4">
                        {(() => {
                            const d = reportData as ServiciosReport;
                            return (
                                <>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <Card>
                                            <CardHeader className="pb-2">
                                                <CardDescription className="text-xs">Órdenes Atendidas en Periodo</CardDescription>
                                                <CardTitle className="text-2xl font-bold">{d.resumen.total_ordenes}</CardTitle>
                                            </CardHeader>
                                            <CardContent className="text-xs text-muted-foreground">
                                                Órdenes de servicio registradas
                                            </CardContent>
                                        </Card>
                                        <Card>
                                            <CardHeader className="pb-2">
                                                <CardDescription className="text-xs">Deficiencias Detectadas</CardDescription>
                                                <CardTitle className="text-2xl font-bold">{d.resumen.total_deficiencias}</CardTitle>
                                            </CardHeader>
                                            <CardContent className="text-xs text-muted-foreground">
                                                Hallazgos durante inspecciones/mantenimientos
                                            </CardContent>
                                        </Card>
                                    </div>

                                    {/* Órdenes por Estado y Tiempos */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <Card>
                                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                                <div>
                                                    <CardTitle className="text-base">Órdenes por Estado</CardTitle>
                                                    <CardDescription className="text-xs">Distribución del flujo operativo</CardDescription>
                                                </div>
                                                <Button size="sm" variant="outline" onClick={() => triggerExport('ordenes_estado')}>
                                                    <Download className="h-3.5 w-3.5 mr-1" /> Exportar
                                                </Button>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="rounded-md border">
                                                    <Table>
                                                        <TableHeader>
                                                            <TableRow>
                                                                <TableHead>Estado</TableHead>
                                                                <TableHead className="text-right">Total Órdenes</TableHead>
                                                            </TableRow>
                                                        </TableHeader>
                                                        <TableBody>
                                                            {d.ordenes_por_estado.length === 0 ? (
                                                                <TableRow>
                                                                    <TableCell colSpan={2} className="text-center text-muted-foreground py-4">
                                                                        Sin órdenes en el periodo.
                                                                    </TableCell>
                                                                </TableRow>
                                                            ) : (
                                                                d.ordenes_por_estado.map((o, idx) => (
                                                                    <TableRow key={idx}>
                                                                        <TableCell className="font-medium">{o.label}</TableCell>
                                                                        <TableCell className="text-right font-bold">{o.total}</TableCell>
                                                                    </TableRow>
                                                                ))
                                                            )}
                                                        </TableBody>
                                                    </Table>
                                                </div>
                                            </CardContent>
                                        </Card>

                                        <Card>
                                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                                <div>
                                                    <CardTitle className="text-base">Tiempo Promedio por Estado</CardTitle>
                                                    <CardDescription className="text-xs">
                                                        Duración media en cada etapa del servicio
                                                    </CardDescription>
                                                </div>
                                                <Button size="sm" variant="outline" onClick={() => triggerExport('tiempos')}>
                                                    <Download className="h-3.5 w-3.5 mr-1" /> Exportar
                                                </Button>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="rounded-md border">
                                                    <Table>
                                                        <TableHeader>
                                                            <TableRow>
                                                                <TableHead>Estado</TableHead>
                                                                <TableHead className="text-right">Promedio (Horas)</TableHead>
                                                                <TableHead className="text-right">Muestras</TableHead>
                                                            </TableRow>
                                                        </TableHeader>
                                                        <TableBody>
                                                            {d.tiempo_promedio_estado.length === 0 ? (
                                                                <TableRow>
                                                                    <TableCell colSpan={3} className="text-center text-muted-foreground py-4">
                                                                        Sin transiciones suficientes para calcular promedio.
                                                                    </TableCell>
                                                                </TableRow>
                                                            ) : (
                                                                d.tiempo_promedio_estado.map((t, idx) => (
                                                                    <TableRow key={idx}>
                                                                        <TableCell className="font-medium">{t.estado_nombre}</TableCell>
                                                                        <TableCell className="text-right font-semibold">
                                                                            {t.promedio_horas} hrs
                                                                        </TableCell>
                                                                        <TableCell className="text-right text-muted-foreground">
                                                                            {t.transiciones_analizadas}
                                                                        </TableCell>
                                                                    </TableRow>
                                                                ))
                                                            )}
                                                        </TableBody>
                                                    </Table>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    </div>

                                    {/* Deficiencias por Tipo */}
                                    <Card>
                                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                                            <div>
                                                <CardTitle className="text-base">Deficiencias por Componente / Tipo</CardTitle>
                                                <CardDescription className="text-xs">
                                                    Análisis de fallas y estado de resolución
                                                </CardDescription>
                                            </div>
                                            <Button size="sm" variant="outline" onClick={() => triggerExport('deficiencias')}>
                                                <Download className="h-3.5 w-3.5 mr-1" /> Exportar
                                            </Button>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="rounded-md border">
                                                <Table>
                                                    <TableHeader>
                                                        <TableRow>
                                                            <TableHead>Componente</TableHead>
                                                            <TableHead className="text-right">Total</TableHead>
                                                            <TableHead className="text-right">Detectadas</TableHead>
                                                            <TableHead className="text-right">Autorizadas</TableHead>
                                                            <TableHead className="text-right">Resueltas</TableHead>
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        {d.deficiencias_por_tipo.length === 0 ? (
                                                            <TableRow>
                                                                <TableCell colSpan={5} className="text-center text-muted-foreground py-4">
                                                                    Sin deficiencias registradas en el periodo.
                                                                </TableCell>
                                                            </TableRow>
                                                        ) : (
                                                            d.deficiencias_por_tipo.map((def, idx) => (
                                                                <TableRow key={idx}>
                                                                    <TableCell className="font-medium">{def.componente}</TableCell>
                                                                    <TableCell className="text-right font-bold">{def.total}</TableCell>
                                                                    <TableCell className="text-right">{def.detectadas}</TableCell>
                                                                    <TableCell className="text-right">{def.autorizadas}</TableCell>
                                                                    <TableCell className="text-right text-emerald-600 font-semibold">
                                                                        {def.resueltas}
                                                                    </TableCell>
                                                                </TableRow>
                                                            ))
                                                        )}
                                                    </TableBody>
                                                </Table>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </>
                            );
                        })()}
                    </TabsContent>

                    {/* 4. EQUIPOS */}
                    <TabsContent value="equipos" className="space-y-6 pt-4">
                        {(() => {
                            const d = reportData as EquiposReport;
                            return (
                                <>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <Card>
                                            <CardHeader className="pb-2">
                                                <CardDescription className="text-xs">Parque Total de Equipos</CardDescription>
                                                <CardTitle className="text-2xl font-bold">{d.resumen.total_equipos}</CardTitle>
                                            </CardHeader>
                                            <CardContent className="text-xs text-muted-foreground">
                                                Equipos registrados en clientes
                                            </CardContent>
                                        </Card>
                                        <Card>
                                            <CardHeader className="pb-2">
                                                <CardDescription className="text-xs">Próximos a Atención / Vencidos</CardDescription>
                                                <CardTitle className="text-2xl font-bold text-amber-600">
                                                    {d.resumen.total_proximos_o_vencidos}
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent className="text-xs text-muted-foreground">
                                                Próxima atención o PH vencida / próximos 30 días
                                            </CardContent>
                                        </Card>
                                    </div>

                                    {/* Próximos a Atención / PH */}
                                    <Card>
                                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                                            <div>
                                                <CardTitle className="text-base">Equipos Próximos a Atención o Prueba Hidrostática</CardTitle>
                                                <CardDescription className="text-xs">
                                                    Equipos vencidos o que vencen dentro de los próximos 30 días
                                                </CardDescription>
                                            </div>
                                            <Button size="sm" variant="outline" onClick={() => triggerExport('proximos')}>
                                                <Download className="h-3.5 w-3.5 mr-1" /> Exportar
                                            </Button>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="rounded-md border max-h-96 overflow-y-auto">
                                                <Table>
                                                    <TableHeader>
                                                        <TableRow>
                                                            <TableHead>Código</TableHead>
                                                            <TableHead>Cliente</TableHead>
                                                            <TableHead>Tipo</TableHead>
                                                            <TableHead>Ubicación</TableHead>
                                                            <TableHead>Próx. Atención</TableHead>
                                                            <TableHead>Próx. P.H.</TableHead>
                                                            <TableHead className="text-center">Estado Alerta</TableHead>
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        {d.proximos_atencion.length === 0 ? (
                                                            <TableRow>
                                                                <TableCell colSpan={7} className="text-center text-muted-foreground py-4">
                                                                    No hay equipos con atención pendiente en los próximos 30 días.
                                                                </TableCell>
                                                            </TableRow>
                                                        ) : (
                                                            d.proximos_atencion.map((eq) => (
                                                                <TableRow key={eq.id}>
                                                                    <TableCell className="font-mono text-xs font-semibold">
                                                                        {eq.codigo}
                                                                    </TableCell>
                                                                    <TableCell className="font-medium">{eq.cliente}</TableCell>
                                                                    <TableCell>{eq.tipo_equipo}</TableCell>
                                                                    <TableCell className="text-xs text-muted-foreground">
                                                                        {eq.ubicacion}
                                                                    </TableCell>
                                                                    <TableCell className={eq.atencion_vencida ? 'text-destructive font-bold' : ''}>
                                                                        {eq.proxima_atencion ?? '-'}
                                                                    </TableCell>
                                                                    <TableCell className={eq.ph_vencida ? 'text-destructive font-bold' : ''}>
                                                                        {eq.proxima_ph ?? '-'}
                                                                    </TableCell>
                                                                    <TableCell className="text-center">
                                                                        {eq.atencion_vencida || eq.ph_vencida ? (
                                                                            <Badge variant="destructive">Vencido</Badge>
                                                                        ) : (
                                                                            <Badge className="bg-amber-600 hover:bg-amber-700">Próximo</Badge>
                                                                        )}
                                                                    </TableCell>
                                                                </TableRow>
                                                            ))
                                                        )}
                                                    </TableBody>
                                                </Table>
                                            </div>
                                        </CardContent>
                                    </Card>

                                    {/* Equipos por Cliente y Estado */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <Card>
                                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                                <div>
                                                    <CardTitle className="text-base">Equipos por Cliente</CardTitle>
                                                    <CardDescription className="text-xs">Distribución de clientes principales</CardDescription>
                                                </div>
                                                <Button size="sm" variant="outline" onClick={() => triggerExport('cliente')}>
                                                    <Download className="h-3.5 w-3.5 mr-1" /> Exportar
                                                </Button>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="rounded-md border">
                                                    <Table>
                                                        <TableHeader>
                                                            <TableRow>
                                                                <TableHead>Cliente</TableHead>
                                                                <TableHead className="text-right">Equipos</TableHead>
                                                            </TableRow>
                                                        </TableHeader>
                                                        <TableBody>
                                                            {d.equipos_por_cliente.map((row) => (
                                                                <TableRow key={row.id}>
                                                                    <TableCell className="font-medium">{row.cliente}</TableCell>
                                                                    <TableCell className="text-right font-bold">{row.total_equipos}</TableCell>
                                                                </TableRow>
                                                            ))}
                                                        </TableBody>
                                                    </Table>
                                                </div>
                                            </CardContent>
                                        </Card>

                                        <Card>
                                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                                <div>
                                                    <CardTitle className="text-base">Equipos por Estado</CardTitle>
                                                    <CardDescription className="text-xs">Estado operativo actual</CardDescription>
                                                </div>
                                                <Button size="sm" variant="outline" onClick={() => triggerExport('estado')}>
                                                    <Download className="h-3.5 w-3.5 mr-1" /> Exportar
                                                </Button>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="rounded-md border">
                                                    <Table>
                                                        <TableHeader>
                                                            <TableRow>
                                                                <TableHead>Estado</TableHead>
                                                                <TableHead className="text-right">Total</TableHead>
                                                            </TableRow>
                                                        </TableHeader>
                                                        <TableBody>
                                                            {d.equipos_por_estado.map((row, idx) => (
                                                                <TableRow key={idx}>
                                                                    <TableCell className="font-medium capitalize">{row.estado}</TableCell>
                                                                    <TableCell className="text-right font-bold">{row.total}</TableCell>
                                                                </TableRow>
                                                            ))}
                                                        </TableBody>
                                                    </Table>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    </div>
                                </>
                            );
                        })()}
                    </TabsContent>

                    {/* 5. CERTIFICADOS */}
                    <TabsContent value="certificados" className="space-y-6 pt-4">
                        {(() => {
                            const d = reportData as CertificadosReport;
                            return (
                                <>
                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <Card>
                                            <CardHeader className="pb-2">
                                                <CardDescription className="text-xs">Total Certificados</CardDescription>
                                                <CardTitle className="text-2xl font-bold">{d.resumen.total_certificados}</CardTitle>
                                            </CardHeader>
                                            <CardContent className="text-xs text-muted-foreground">
                                                Emitidos en el periodo
                                            </CardContent>
                                        </Card>
                                    </div>

                                    {/* Por Estado y Por Tipo */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <Card>
                                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                                <div>
                                                    <CardTitle className="text-base">Certificados por Estado</CardTitle>
                                                    <CardDescription className="text-xs">Vigentes, vencidos, anulados</CardDescription>
                                                </div>
                                                <Button size="sm" variant="outline" onClick={() => triggerExport('estado')}>
                                                    <Download className="h-3.5 w-3.5 mr-1" /> Exportar
                                                </Button>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="rounded-md border">
                                                    <Table>
                                                        <TableHeader>
                                                            <TableRow>
                                                                <TableHead>Estado</TableHead>
                                                                <TableHead className="text-right">Total</TableHead>
                                                            </TableRow>
                                                        </TableHeader>
                                                        <TableBody>
                                                            {d.por_estado.length === 0 ? (
                                                                <TableRow>
                                                                    <TableCell colSpan={2} className="text-center text-muted-foreground py-4">
                                                                        Sin datos.
                                                                    </TableCell>
                                                                </TableRow>
                                                            ) : (
                                                                d.por_estado.map((row, idx) => (
                                                                    <TableRow key={idx}>
                                                                        <TableCell className="font-medium">{row.label}</TableCell>
                                                                        <TableCell className="text-right font-bold">{row.total}</TableCell>
                                                                    </TableRow>
                                                                ))
                                                            )}
                                                        </TableBody>
                                                    </Table>
                                                </div>
                                            </CardContent>
                                        </Card>

                                        <Card>
                                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                                <div>
                                                    <CardTitle className="text-base">Certificados por Tipo</CardTitle>
                                                    <CardDescription className="text-xs">Operatividad, P.H., capacitación</CardDescription>
                                                </div>
                                                <Button size="sm" variant="outline" onClick={() => triggerExport('tipo')}>
                                                    <Download className="h-3.5 w-3.5 mr-1" /> Exportar
                                                </Button>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="rounded-md border">
                                                    <Table>
                                                        <TableHeader>
                                                            <TableRow>
                                                                <TableHead>Tipo</TableHead>
                                                                <TableHead className="text-right">Total</TableHead>
                                                            </TableRow>
                                                        </TableHeader>
                                                        <TableBody>
                                                            {d.por_tipo.length === 0 ? (
                                                                <TableRow>
                                                                    <TableCell colSpan={2} className="text-center text-muted-foreground py-4">
                                                                        Sin datos.
                                                                    </TableCell>
                                                                </TableRow>
                                                            ) : (
                                                                d.por_tipo.map((row, idx) => (
                                                                    <TableRow key={idx}>
                                                                        <TableCell className="font-medium">{row.label}</TableCell>
                                                                        <TableCell className="text-right font-bold">{row.total}</TableCell>
                                                                    </TableRow>
                                                                ))
                                                            )}
                                                        </TableBody>
                                                    </Table>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    </div>

                                    {/* Listado de Certificados */}
                                    <Card>
                                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                                            <div>
                                                <CardTitle className="text-base">Listado de Certificados Emitidos</CardTitle>
                                                <CardDescription className="text-xs">Detalle de emisiones en el periodo</CardDescription>
                                            </div>
                                            <Button size="sm" variant="outline" onClick={() => triggerExport('listado')}>
                                                <Download className="h-3.5 w-3.5 mr-1" /> Exportar CSV
                                            </Button>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="rounded-md border max-h-96 overflow-y-auto">
                                                <Table>
                                                    <TableHeader>
                                                        <TableRow>
                                                            <TableHead>Número</TableHead>
                                                            <TableHead>Tipo</TableHead>
                                                            <TableHead>Cliente</TableHead>
                                                            <TableHead>Orden</TableHead>
                                                            <TableHead>Emisión</TableHead>
                                                            <TableHead>Vigencia</TableHead>
                                                            <TableHead className="text-center">Estado</TableHead>
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        {d.certificados.length === 0 ? (
                                                            <TableRow>
                                                                <TableCell colSpan={7} className="text-center text-muted-foreground py-4">
                                                                    Sin certificados emitidos en el periodo.
                                                                </TableCell>
                                                            </TableRow>
                                                        ) : (
                                                            d.certificados.map((c) => (
                                                                <TableRow key={c.id}>
                                                                    <TableCell className="font-mono text-xs font-semibold">
                                                                        {c.numero}
                                                                    </TableCell>
                                                                    <TableCell>{c.tipo}</TableCell>
                                                                    <TableCell className="font-medium">{c.cliente}</TableCell>
                                                                    <TableCell className="font-mono text-xs">{c.orden_codigo}</TableCell>
                                                                    <TableCell>{c.fecha_emision}</TableCell>
                                                                    <TableCell>{c.fecha_vigencia}</TableCell>
                                                                    <TableCell className="text-center">
                                                                        <Badge
                                                                            variant={
                                                                                c.estado === 'Vigente'
                                                                                    ? 'default'
                                                                                    : c.estado === 'Vencido'
                                                                                      ? 'destructive'
                                                                                      : 'secondary'
                                                                            }
                                                                        >
                                                                            {c.estado}
                                                                        </Badge>
                                                                    </TableCell>
                                                                </TableRow>
                                                            ))
                                                        )}
                                                    </TableBody>
                                                </Table>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </>
                            );
                        })()}
                    </TabsContent>

                    {/* 6. FACTURACIÓN */}
                    <TabsContent value="facturacion" className="space-y-6 pt-4">
                        {(() => {
                            const d = reportData as FacturacionReport;
                            return (
                                <>
                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <Card>
                                            <CardHeader className="pb-2">
                                                <CardDescription className="text-xs">Documentos Electrónicos</CardDescription>
                                                <CardTitle className="text-2xl font-bold">{d.resumen.total_documentos}</CardTitle>
                                            </CardHeader>
                                            <CardContent className="text-xs text-muted-foreground">
                                                Facturas y boletas generadas
                                            </CardContent>
                                        </Card>
                                        <Card>
                                            <CardHeader className="pb-2">
                                                <CardDescription className="text-xs">Errores / Rechazos SUNAT</CardDescription>
                                                <CardTitle className="text-2xl font-bold text-destructive">
                                                    {d.resumen.total_errores}
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent className="text-xs text-muted-foreground">
                                                Documentos que requieren subsanación
                                            </CardContent>
                                        </Card>
                                        <Card>
                                            <CardHeader className="pb-2">
                                                <CardDescription className="text-xs">Facturación Total</CardDescription>
                                                <CardTitle className="text-2xl font-bold text-primary">
                                                    {formatMoney(d.resumen.total_ventas_monto)}
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent className="text-xs text-muted-foreground">
                                                Monto global en el periodo
                                            </CardContent>
                                        </Card>
                                    </div>

                                    {/* Documentos por Estado y Contado vs Crédito */}
                                    <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        <Card>
                                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                                <div>
                                                    <CardTitle className="text-base">Documentos por Estado SUNAT</CardTitle>
                                                    <CardDescription className="text-xs">Aceptados, pendientes o con error</CardDescription>
                                                </div>
                                                <Button size="sm" variant="outline" onClick={() => triggerExport('estado_sunat')}>
                                                    <Download className="h-3.5 w-3.5 mr-1" /> Exportar
                                                </Button>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="rounded-md border">
                                                    <Table>
                                                        <TableHeader>
                                                            <TableRow>
                                                                <TableHead>Tipo</TableHead>
                                                                <TableHead>Estado SUNAT</TableHead>
                                                                <TableHead className="text-right">Total</TableHead>
                                                            </TableRow>
                                                        </TableHeader>
                                                        <TableBody>
                                                            {d.documentos_por_estado.length === 0 ? (
                                                                <TableRow>
                                                                    <TableCell colSpan={3} className="text-center text-muted-foreground py-4">
                                                                        Sin comprobantes en el periodo.
                                                                    </TableCell>
                                                                </TableRow>
                                                            ) : (
                                                                d.documentos_por_estado.map((doc, idx) => (
                                                                    <TableRow key={idx}>
                                                                        <TableCell className="font-medium">{doc.tipo}</TableCell>
                                                                        <TableCell>
                                                                            <Badge
                                                                                variant={
                                                                                    doc.estado === 'Aceptado'
                                                                                        ? 'default'
                                                                                        : doc.estado === 'Rechazado' || doc.estado === 'Error'
                                                                                          ? 'destructive'
                                                                                          : 'secondary'
                                                                                }
                                                                            >
                                                                                {doc.estado}
                                                                            </Badge>
                                                                        </TableCell>
                                                                        <TableCell className="text-right font-bold">{doc.total}</TableCell>
                                                                    </TableRow>
                                                                ))
                                                            )}
                                                        </TableBody>
                                                    </Table>
                                                </div>
                                            </CardContent>
                                        </Card>

                                        <Card>
                                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                                <div>
                                                    <CardTitle className="text-base">Ventas Contado vs Crédito</CardTitle>
                                                    <CardDescription className="text-xs">Distribución por condición de pago</CardDescription>
                                                </div>
                                                <Button size="sm" variant="outline" onClick={() => triggerExport('contado_credito')}>
                                                    <Download className="h-3.5 w-3.5 mr-1" /> Exportar
                                                </Button>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="rounded-md border">
                                                    <Table>
                                                        <TableHeader>
                                                            <TableRow>
                                                                <TableHead>Condición</TableHead>
                                                                <TableHead className="text-right">Operaciones</TableHead>
                                                                <TableHead className="text-right">Monto Total</TableHead>
                                                            </TableRow>
                                                        </TableHeader>
                                                        <TableBody>
                                                            {d.ventas_contado_credito.length === 0 ? (
                                                                <TableRow>
                                                                    <TableCell colSpan={3} className="text-center text-muted-foreground py-4">
                                                                        Sin ventas en el periodo.
                                                                    </TableCell>
                                                                </TableRow>
                                                            ) : (
                                                                d.ventas_contado_credito.map((v, idx) => (
                                                                    <TableRow key={idx}>
                                                                        <TableCell className="font-medium capitalize">{v.condicion_pago}</TableCell>
                                                                        <TableCell className="text-right">{v.total_ventas}</TableCell>
                                                                        <TableCell className="text-right font-bold">
                                                                            {formatMoney(v.monto_total)}
                                                                        </TableCell>
                                                                    </TableRow>
                                                                ))
                                                            )}
                                                        </TableBody>
                                                    </Table>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    </div>

                                    {/* Errores SUNAT */}
                                    {d.errores_sunat.length > 0 && (
                                        <Card className="border-destructive/40">
                                            <CardHeader className="flex flex-row items-center justify-between pb-2">
                                                <div>
                                                    <CardTitle className="text-base text-destructive">Detalle de Errores SUNAT</CardTitle>
                                                    <CardDescription className="text-xs">
                                                        Comprobantes con observaciones o fallas en el envío electrónico
                                                    </CardDescription>
                                                </div>
                                                <Button size="sm" variant="outline" onClick={() => triggerExport('errores')}>
                                                    <Download className="h-3.5 w-3.5 mr-1" /> Exportar
                                                </Button>
                                            </CardHeader>
                                            <CardContent>
                                                <div className="rounded-md border max-h-80 overflow-y-auto">
                                                    <Table>
                                                        <TableHeader>
                                                            <TableRow>
                                                                <TableHead>Documento</TableHead>
                                                                <TableHead>Tipo</TableHead>
                                                                <TableHead>Estado</TableHead>
                                                                <TableHead>Error / Mensaje</TableHead>
                                                                <TableHead className="text-center">Intentos</TableHead>
                                                                <TableHead>Fecha Envío</TableHead>
                                                            </TableRow>
                                                        </TableHeader>
                                                        <TableBody>
                                                            {d.errores_sunat.map((err) => (
                                                                <TableRow key={err.id}>
                                                                    <TableCell className="font-mono text-xs font-semibold">
                                                                        {err.documento}
                                                                    </TableCell>
                                                                    <TableCell>{err.tipo}</TableCell>
                                                                    <TableCell>
                                                                        <Badge variant="destructive">{err.estado}</Badge>
                                                                    </TableCell>
                                                                    <TableCell className="text-xs text-destructive max-w-xs truncate">
                                                                        {err.error}
                                                                    </TableCell>
                                                                    <TableCell className="text-center font-mono">{err.intentos}</TableCell>
                                                                    <TableCell className="text-xs">{err.fecha_envio}</TableCell>
                                                                </TableRow>
                                                            ))}
                                                        </TableBody>
                                                    </Table>
                                                </div>
                                            </CardContent>
                                        </Card>
                                    )}
                                </>
                            );
                        })()}
                    </TabsContent>

                    {/* 7. COBRANZAS */}
                    <TabsContent value="cobranzas" className="space-y-6 pt-4">
                        {(() => {
                            const d = reportData as CobranzasReport;
                            return (
                                <>
                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-4">
                                        <Card>
                                            <CardHeader className="pb-2">
                                                <CardDescription className="text-xs">Saldo Total por Cobrar</CardDescription>
                                                <CardTitle className="text-2xl font-bold text-blue-600">
                                                    {formatMoney(d.resumen.total_por_cobrar)}
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent className="text-xs text-muted-foreground">
                                                Monto pendiente en cuotas de crédito
                                            </CardContent>
                                        </Card>
                                        <Card>
                                            <CardHeader className="pb-2">
                                                <CardDescription className="text-xs">Saldo Vencido</CardDescription>
                                                <CardTitle className="text-2xl font-bold text-destructive">
                                                    {formatMoney(d.resumen.total_vencido)}
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent className="text-xs text-muted-foreground">
                                                Cuotas vencidas a la fecha
                                            </CardContent>
                                        </Card>
                                        <Card>
                                            <CardHeader className="pb-2">
                                                <CardDescription className="text-xs">Total Cobrado en Periodo</CardDescription>
                                                <CardTitle className="text-2xl font-bold text-emerald-600">
                                                    {formatMoney(d.resumen.total_cobrado_periodo)}
                                                </CardTitle>
                                            </CardHeader>
                                            <CardContent className="text-xs text-muted-foreground">
                                                Pagos registrados en el rango de fechas
                                            </CardContent>
                                        </Card>
                                    </div>

                                    {/* Cobrado por Medio de Pago */}
                                    <Card>
                                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                                            <div>
                                                <CardTitle className="text-base">Cobros por Medio de Pago</CardTitle>
                                                <CardDescription className="text-xs">Recaudación por canal financiero</CardDescription>
                                            </div>
                                            <Button size="sm" variant="outline" onClick={() => triggerExport('cobrado')}>
                                                <Download className="h-3.5 w-3.5 mr-1" /> Exportar
                                            </Button>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="rounded-md border">
                                                <Table>
                                                    <TableHeader>
                                                        <TableRow>
                                                            <TableHead>Medio de Pago</TableHead>
                                                            <TableHead className="text-right">Operaciones</TableHead>
                                                            <TableHead className="text-right">Monto Cobrado</TableHead>
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        {d.cobrado_por_medio.length === 0 ? (
                                                            <TableRow>
                                                                <TableCell colSpan={3} className="text-center text-muted-foreground py-4">
                                                                    Sin cobros registrados en el periodo.
                                                                </TableCell>
                                                            </TableRow>
                                                        ) : (
                                                            d.cobrado_por_medio.map((c, idx) => (
                                                                <TableRow key={idx}>
                                                                    <TableCell className="font-medium capitalize">{c.forma_pago}</TableCell>
                                                                    <TableCell className="text-right">{c.total_operaciones}</TableCell>
                                                                    <TableCell className="text-right font-bold text-emerald-600">
                                                                        {formatMoney(c.total_monto)}
                                                                    </TableCell>
                                                                </TableRow>
                                                            ))
                                                        )}
                                                    </TableBody>
                                                </Table>
                                            </div>
                                        </CardContent>
                                    </Card>

                                    {/* Cuotas Pendientes / Vencidas */}
                                    <Card>
                                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                                            <div>
                                                <CardTitle className="text-base">Cuotas Pendientes y Vencidas</CardTitle>
                                                <CardDescription className="text-xs">
                                                    Control de cartera por cobrar de ventas a crédito
                                                </CardDescription>
                                            </div>
                                            <Button size="sm" variant="outline" onClick={() => triggerExport('cuotas')}>
                                                <Download className="h-3.5 w-3.5 mr-1" /> Exportar
                                            </Button>
                                        </CardHeader>
                                        <CardContent>
                                            <div className="rounded-md border max-h-96 overflow-y-auto">
                                                <Table>
                                                    <TableHeader>
                                                        <TableRow>
                                                            <TableHead>Venta</TableHead>
                                                            <TableHead>Cliente</TableHead>
                                                            <TableHead className="text-center">Cuota</TableHead>
                                                            <TableHead className="text-right">Monto Cuota</TableHead>
                                                            <TableHead className="text-right">Saldo Pendiente</TableHead>
                                                            <TableHead>Vencimiento</TableHead>
                                                            <TableHead className="text-center">Estado</TableHead>
                                                        </TableRow>
                                                    </TableHeader>
                                                    <TableBody>
                                                        {d.cuotas_pendientes.length === 0 ? (
                                                            <TableRow>
                                                                <TableCell colSpan={7} className="text-center text-muted-foreground py-4">
                                                                    No hay cuotas pendientes por cobrar.
                                                                </TableCell>
                                                            </TableRow>
                                                        ) : (
                                                            d.cuotas_pendientes.map((cuota) => (
                                                                <TableRow key={cuota.id}>
                                                                    <TableCell className="font-mono text-xs font-semibold">
                                                                        {cuota.venta_numero}
                                                                    </TableCell>
                                                                    <TableCell className="font-medium">{cuota.cliente}</TableCell>
                                                                    <TableCell className="text-center">#{cuota.numero_cuota}</TableCell>
                                                                    <TableCell className="text-right">{formatMoney(cuota.monto)}</TableCell>
                                                                    <TableCell className="text-right font-bold text-destructive">
                                                                        {formatMoney(cuota.monto_pendiente)}
                                                                    </TableCell>
                                                                    <TableCell className={cuota.vencida ? 'text-destructive font-bold' : ''}>
                                                                        {cuota.fecha_vencimiento}
                                                                    </TableCell>
                                                                    <TableCell className="text-center">
                                                                        {cuota.vencida ? (
                                                                            <Badge variant="destructive">Vencida</Badge>
                                                                        ) : (
                                                                            <Badge variant="secondary">Por Vencer</Badge>
                                                                        )}
                                                                    </TableCell>
                                                                </TableRow>
                                                            ))
                                                        )}
                                                    </TableBody>
                                                </Table>
                                            </div>
                                        </CardContent>
                                    </Card>
                                </>
                            );
                        })()}
                    </TabsContent>
                </Tabs>
            </div>
        </AppLayout>
    );
}
