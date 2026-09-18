import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated, type SharedData } from '@/types';
import { type ShippingGuideData, type ShippingGuideEstado } from '@/types/shipping';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { CheckCircle2, Plus, Search, Truck } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Guías de Remisión', href: route('shipping.index') }];

const ESTADO_BADGE_VARIANT: Record<ShippingGuideEstado, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    pendiente: 'secondary',
    aceptado: 'default',
    rechazado: 'destructive',
    error: 'destructive',
};

export default function ShippingIndex({
    guides,
    motivoLabels,
    modalidadLabels,
    estadoLabels,
    filters,
    status,
}: {
    guides: Paginated<ShippingGuideData>;
    motivoLabels: Record<string, string>;
    modalidadLabels: Record<string, string>;
    estadoLabels: Record<string, string>;
    filters: { search: string; estado: string };
    status?: string;
}) {
    const { auth } = usePage<SharedData>().props;
    const canCreate = auth.permissions.includes('shipping_guides.create');

    const [search, setSearch] = useState(filters.search ?? '');
    const [estado, setEstado] = useState(filters.estado || 'todos');

    const applyFilters = (nextSearch: string, nextEstado: string) => {
        router.get(
            route('shipping.index'),
            {
                search: nextSearch || undefined,
                estado: nextEstado === 'todos' ? undefined : nextEstado,
            },
            { preserveState: true, replace: true },
        );
    };

    const submitFilter: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilters(search, estado);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Guías de Remisión" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <HeadingSmall title="Guías de Remisión" description="Traslados de bienes (GRE) enviados a SUNAT." />
                    {canCreate && (
                        <Button asChild>
                            <Link href={route('shipping.create')}>
                                <Plus className="size-4" /> Nueva guía
                            </Link>
                        </Button>
                    )}
                </div>

                {status && (
                    <div role="status" className="flex items-center gap-2 rounded-lg border bg-muted/50 px-4 py-3 text-sm">
                        <CheckCircle2 className="size-4 text-emerald-600" />
                        {status}
                    </div>
                )}

                <form onSubmit={submitFilter} className="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div className="relative flex-1 sm:max-w-xs">
                        <Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Buscar por serie-correlativo o destinatario"
                            className="pl-9"
                        />
                    </div>

                    <Select
                        value={estado}
                        onValueChange={(val) => {
                            setEstado(val);
                            applyFilters(search, val);
                        }}
                    >
                        <SelectTrigger className="sm:w-48">
                            <SelectValue placeholder="Estado" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="todos">Todos los estados</SelectItem>
                            {Object.entries(estadoLabels).map(([value, label]) => (
                                <SelectItem key={value} value={value}>
                                    {label}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Button type="submit" variant="secondary">
                        Buscar
                    </Button>
                </form>

                <div className="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Número</TableHead>
                                <TableHead>Motivo</TableHead>
                                <TableHead>Destinatario</TableHead>
                                <TableHead>Modalidad</TableHead>
                                <TableHead>Bienes</TableHead>
                                <TableHead>Fecha inicio</TableHead>
                                <TableHead>Estado</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {guides.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={7} className="py-8 text-center text-muted-foreground">
                                        <Truck className="mx-auto mb-2 size-8 opacity-50" />
                                        No se encontraron guías de remisión.
                                    </TableCell>
                                </TableRow>
                            )}

                            {guides.data.map((guide) => (
                                <TableRow key={guide.id}>
                                    <TableCell className="font-medium">
                                        {guide.serie}-{guide.correlativo}
                                        {guide.sale && <p className="text-xs text-muted-foreground">Venta {guide.sale.numero}</p>}
                                    </TableCell>
                                    <TableCell>{motivoLabels[guide.motivo_traslado] ?? guide.motivo_traslado}</TableCell>
                                    <TableCell>{guide.destinatario_client?.razon_social ?? guide.destinatario_nombre ?? '-'}</TableCell>
                                    <TableCell>{modalidadLabels[guide.modalidad] ?? guide.modalidad}</TableCell>
                                    <TableCell>{guide.items?.length ?? 0}</TableCell>
                                    <TableCell>{guide.fecha_inicio}</TableCell>
                                    <TableCell>
                                        <Badge variant={ESTADO_BADGE_VARIANT[guide.estado]}>{estadoLabels[guide.estado] ?? guide.estado}</Badge>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {guides.last_page > 1 && (
                    <div className="flex flex-wrap items-center justify-center gap-1 pt-2">
                        {guides.links.map((link, index) => (
                            <Button
                                key={index}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
