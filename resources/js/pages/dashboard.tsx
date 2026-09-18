import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { AlertTriangle, ClipboardList, DollarSign, FileText, Wrench } from 'lucide-react';
import { Bar, BarChart, CartesianGrid, Line, LineChart, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Panel', href: '/dashboard' }];

interface DashboardKpis {
    ventas_mes: number;
    facturacion_mes: number;
    por_cobrar: number;
    servicios_pendientes: number;
    equipos_por_vencer: number;
}

interface VentaMensual {
    mes: string;
    label: string;
    total: number;
}

interface ServicioPorTipo {
    tipo: string;
    label: string;
    total: number;
}

interface EquipoProximo {
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
}

interface OrdenReciente {
    id: number;
    codigo: string;
    cliente: string;
    estado: string;
    estado_label: string;
    fecha: string;
}

interface StockCritico {
    codigo: string;
    nombre: string;
    categoria: string;
    unidad: string;
    stock_actual: number;
    stock_minimo: number;
    faltante: number;
}

interface DashboardProps {
    kpis: DashboardKpis;
    ventas_mensuales: VentaMensual[];
    servicios_por_tipo: ServicioPorTipo[];
    proximos_vencimientos: EquipoProximo[];
    ordenes_recientes: OrdenReciente[];
    stock_critico: StockCritico[];
}

const formatCurrency = (amount: number) => new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' }).format(amount || 0);

const today = new Date().toLocaleDateString('es-PE', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

function KpiCard({ title, value, icon: Icon, hint }: { title: string; value: string; icon: typeof DollarSign; hint?: string }) {
    return (
        <Card>
            <CardContent className="flex items-start justify-between gap-3 p-4">
                <div className="min-w-0">
                    <p className="text-xs font-medium text-muted-foreground">{title}</p>
                    <p className="mt-1 truncate text-xl font-semibold">{value}</p>
                    {hint && <p className="mt-0.5 text-xs text-muted-foreground">{hint}</p>}
                </div>
                <div className="rounded-lg bg-primary/10 p-2 text-primary">
                    <Icon className="size-4" />
                </div>
            </CardContent>
        </Card>
    );
}

export default function Dashboard({ kpis, ventas_mensuales, servicios_por_tipo, proximos_vencimientos, ordenes_recientes, stock_critico }: DashboardProps) {
    const { auth } = usePage<SharedData>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Panel" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Hola, {auth.user.name}</h1>
                    <p className="text-sm text-muted-foreground capitalize">{today}</p>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <KpiCard title="Ventas del mes" value={formatCurrency(kpis.ventas_mes)} icon={DollarSign} />
                    <KpiCard title="Facturación del mes" value={formatCurrency(kpis.facturacion_mes)} icon={FileText} hint="Comprobantes aceptados" />
                    <KpiCard title="Por cobrar" value={formatCurrency(kpis.por_cobrar)} icon={DollarSign} hint="Cuotas pendientes" />
                    <KpiCard title="Servicios pendientes" value={String(kpis.servicios_pendientes)} icon={ClipboardList} hint="Órdenes no cerradas" />
                    <KpiCard title="Equipos por vencer" value={String(kpis.equipos_por_vencer)} icon={Wrench} hint="Próximos 30 días" />
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <Card>
                        <CardHeader>
                            <CardTitle>Ventas mensuales</CardTitle>
                        </CardHeader>
                        <CardContent className="h-72">
                            <ResponsiveContainer width="100%" height="100%">
                                <LineChart data={ventas_mensuales}>
                                    <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
                                    <XAxis dataKey="label" tick={{ fontSize: 12 }} />
                                    <YAxis tick={{ fontSize: 12 }} width={70} tickFormatter={(v: number) => formatCurrency(v)} />
                                    <Tooltip formatter={(value) => formatCurrency(Number(value))} />
                                    <Line type="monotone" dataKey="total" name="Ventas" stroke="#D20404" strokeWidth={2} dot />
                                </LineChart>
                            </ResponsiveContainer>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Servicios por tipo</CardTitle>
                        </CardHeader>
                        <CardContent className="h-72">
                            {servicios_por_tipo.length === 0 ? (
                                <p className="flex h-full items-center justify-center text-sm text-muted-foreground">Sin órdenes registradas.</p>
                            ) : (
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={servicios_por_tipo} layout="vertical" margin={{ left: 24 }}>
                                        <CartesianGrid strokeDasharray="3 3" className="stroke-border" />
                                        <XAxis type="number" tick={{ fontSize: 12 }} allowDecimals={false} />
                                        <YAxis type="category" dataKey="label" tick={{ fontSize: 12 }} width={120} />
                                        <Tooltip />
                                        <Bar dataKey="total" name="Órdenes" fill="#D20404" radius={[0, 4, 4, 0]} />
                                    </BarChart>
                                </ResponsiveContainer>
                            )}
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <Wrench className="size-4" /> Próximos vencimientos
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {proximos_vencimientos.length === 0 && <p className="text-sm text-muted-foreground">Sin equipos próximos a vencer.</p>}
                            {proximos_vencimientos.map((equipo) => (
                                <div key={equipo.id} className="flex items-center justify-between gap-2 text-sm">
                                    <div className="min-w-0">
                                        <p className="truncate font-medium">{equipo.codigo}</p>
                                        <p className="truncate text-xs text-muted-foreground">{equipo.cliente}</p>
                                    </div>
                                    <Badge variant={equipo.atencion_vencida || equipo.ph_vencida ? 'destructive' : 'secondary'} className="shrink-0">
                                        {equipo.proxima_atencion ?? equipo.proxima_ph}
                                    </Badge>
                                </div>
                            ))}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <ClipboardList className="size-4" /> Órdenes recientes
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-3">
                            {ordenes_recientes.length === 0 && <p className="text-sm text-muted-foreground">Sin órdenes registradas.</p>}
                            {ordenes_recientes.map((orden) => (
                                <Link
                                    key={orden.id}
                                    href={route('service-orders.show', orden.id)}
                                    className="flex items-center justify-between gap-2 text-sm hover:underline"
                                >
                                    <div className="min-w-0">
                                        <p className="truncate font-medium">{orden.codigo}</p>
                                        <p className="truncate text-xs text-muted-foreground">{orden.cliente}</p>
                                    </div>
                                    <Badge variant="outline" className="shrink-0">
                                        {orden.estado_label}
                                    </Badge>
                                </Link>
                            ))}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2 text-base">
                                <AlertTriangle className="size-4" /> Stock crítico
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            {stock_critico.length === 0 ? (
                                <p className="text-sm text-muted-foreground">Sin artículos bajo el mínimo.</p>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Artículo</TableHead>
                                            <TableHead className="text-right">Stock</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {stock_critico.map((item) => (
                                            <TableRow key={item.codigo}>
                                                <TableCell className="text-xs">{item.nombre}</TableCell>
                                                <TableCell className="text-right text-xs">
                                                    {item.stock_actual} / {item.stock_minimo} {item.unidad}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
