import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type Paginated } from '@/types';
import { type ServiceOrder, type ServiceOrderLabels, type TechnicianOption } from '@/types/service-order';
import { Head, Link, useForm } from '@inertiajs/react';
import { ClipboardList, Plus } from 'lucide-react';
import { type FormEventHandler } from 'react';

interface Props extends ServiceOrderLabels {
    orders: Paginated<ServiceOrder>;
    filters: { search: string; estado: string; tecnico_user_id: number | null };
    technicians: TechnicianOption[];
    can: { create: boolean };
}

export default function ServiceOrdersIndex({ orders, filters, technicians, can, statuses, serviceTypes, priorities }: Props) {
    const { data, setData, get, processing, errors } = useForm({
        search: filters.search,
        estado: filters.estado,
        tecnico_user_id: filters.tecnico_user_id ? String(filters.tecnico_user_id) : '',
    });
    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        get(route('service-orders.index'), { preserveState: true, preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Órdenes de Servicio', href: route('service-orders.index') }]}>
            <Head title="Órdenes de Servicio" />
            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Órdenes de Servicio</h1>
                        <p className="text-muted-foreground text-sm">Gestiona los servicios y el avance del trabajo de cada equipo.</p>
                    </div>
                    {can.create && (
                        <Button asChild>
                            <Link href={route('service-orders.create')}>
                                <Plus className="size-4" />
                                Nueva orden
                            </Link>
                        </Button>
                    )}
                </div>
                <form onSubmit={submit} className="grid gap-3 lg:grid-cols-[1fr_14rem_14rem_auto] lg:items-end">
                    <div className="grid gap-2">
                        <Label htmlFor="search">Buscar</Label>
                        <Input
                            id="search"
                            value={data.search}
                            onChange={(event) => setData('search', event.target.value)}
                            maxLength={255}
                            placeholder="Código, cliente, documento o equipo"
                        />
                        <InputError message={errors.search} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="estado">Estado</Label>
                        <Select value={data.estado || 'todos'} onValueChange={(value) => setData('estado', value === 'todos' ? '' : value)}>
                            <SelectTrigger id="estado">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todos">Todos los estados</SelectItem>
                                {Object.entries(statuses).map(([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.estado} />
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="tecnico">Técnico asignado</Label>
                        <Select
                            value={data.tecnico_user_id || 'todos'}
                            onValueChange={(value) => setData('tecnico_user_id', value === 'todos' ? '' : value)}
                        >
                            <SelectTrigger id="tecnico">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todos">Todos los técnicos</SelectItem>
                                {technicians.map((technician) => (
                                    <SelectItem key={technician.id} value={String(technician.id)}>
                                        {technician.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.tecnico_user_id} />
                    </div>
                    <div className="flex gap-2">
                        <Button type="submit" variant="secondary" disabled={processing}>
                            {processing ? 'Buscando...' : 'Filtrar'}
                        </Button>
                        <Button variant="ghost" asChild>
                            <Link href={route('service-orders.index')}>Limpiar</Link>
                        </Button>
                    </div>
                </form>
                {orders.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed px-6 py-16 text-center">
                        <ClipboardList className="text-muted-foreground size-8" aria-hidden="true" />
                        <h2 className="font-medium">No se encontraron órdenes</h2>
                        <p className="text-muted-foreground text-sm">Prueba otros filtros o registra una nueva orden de servicio.</p>
                    </div>
                ) : (
                    <>
                        <div className="hidden rounded-lg border md:block">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        {['Orden', 'Cliente', 'Servicio', 'Técnico', 'Prioridad', 'Estado'].map((label) => (
                                            <TableHead key={label}>{label}</TableHead>
                                        ))}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {orders.data.map((order) => (
                                        <TableRow key={order.id}>
                                            <TableCell>
                                                <Link href={route('service-orders.show', order.id)} className="font-medium break-all hover:underline">
                                                    {order.codigo}
                                                </Link>
                                                <p className="text-muted-foreground text-xs">
                                                    {order.fecha} · {order.equipment_count} equipos
                                                </p>
                                            </TableCell>
                                            <TableCell>{order.client.razon_social}</TableCell>
                                            <TableCell>{serviceTypes[order.tipo_servicio]}</TableCell>
                                            <TableCell>{order.tecnico?.name ?? 'Sin asignar'}</TableCell>
                                            <TableCell>
                                                <Badge variant={order.prioridad === 'alta' ? 'destructive' : 'outline'}>
                                                    {priorities[order.prioridad]}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>
                                                <Badge variant={order.estado === 'cerrado' ? 'secondary' : 'default'}>{statuses[order.estado]}</Badge>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>
                        <div className="grid gap-3 md:hidden">
                            {orders.data.map((order) => (
                                <Card key={order.id}>
                                    <CardContent className="space-y-3 pt-6">
                                        <div className="flex flex-wrap items-center justify-between gap-2">
                                            <Badge>{statuses[order.estado]}</Badge>
                                            <Badge variant={order.prioridad === 'alta' ? 'destructive' : 'outline'}>
                                                {priorities[order.prioridad]}
                                            </Badge>
                                        </div>
                                        <h2 className="font-semibold break-words">{order.client.razon_social}</h2>
                                        <p className="text-sm">
                                            {serviceTypes[order.tipo_servicio]} · {order.equipment_count} equipos
                                        </p>
                                        <p className="text-muted-foreground text-sm">
                                            {order.fecha} · {order.tecnico?.name ?? 'Sin técnico asignado'}
                                        </p>
                                        <p className="text-muted-foreground font-mono text-xs break-all">{order.codigo}</p>
                                        <Button asChild variant="outline" className="w-full">
                                            <Link href={route('service-orders.show', order.id)}>Ver orden</Link>
                                        </Button>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    </>
                )}
                <p className="text-muted-foreground text-sm">
                    {orders.total} órdenes · Página {orders.current_page} de {orders.last_page}
                </p>
                {orders.last_page > 1 && (
                    <nav aria-label="Paginación de órdenes" className="flex flex-wrap justify-center gap-1">
                        {orders.links.map((link, index) => (
                            <Button
                                key={index}
                                asChild={link.url !== null}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                            >
                                {link.url ? (
                                    <Link href={link.url} dangerouslySetInnerHTML={{ __html: link.label }} />
                                ) : (
                                    <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                )}
                            </Button>
                        ))}
                    </nav>
                )}
            </div>
        </AppLayout>
    );
}
