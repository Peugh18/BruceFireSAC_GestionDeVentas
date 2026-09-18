import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated, type SharedData } from '@/types';
import { type ServiceOrderPickupItem } from '@/types/pickup';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Recojos y Custodia', href: '/pickups' }];

function getCustodyBadge(item: ServiceOrderPickupItem) {
    if (item.recibido_cliente_en) {
        return <Badge variant="default">Conforme Cliente</Badge>;
    }
    if (item.entregado_en) {
        return <Badge className="bg-blue-600 hover:bg-blue-700">Entregado</Badge>;
    }
    if (item.recibido_planta_en) {
        return <Badge className="bg-amber-600 hover:bg-amber-700">En Planta</Badge>;
    }
    return <Badge variant="secondary">Recogido</Badge>;
}

export default function PickupsIndex({
    pickups,
    filters,
}: {
    pickups: Paginated<ServiceOrderPickupItem>;
    filters: { search: string };
}) {
    const { auth } = usePage<SharedData>().props;
    const canCreate = auth.permissions.includes('pickups.create');

    const [search, setSearch] = useState(filters.search ?? '');

    const applyFilters = (nextSearch: string) => {
        router.get(
            route('pickups.index'),
            {
                search: nextSearch || undefined,
            },
            { preserveState: true, replace: true },
        );
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilters(search);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Recojos y Cadena de Custodia" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Recojos y Cadena de Custodia</h1>
                        <p className="text-sm text-muted-foreground">
                            Registro de recojos de equipos, seguimiento de custodia en planta y entrega con conformidad.
                        </p>
                    </div>

                    {canCreate && (
                        <Button asChild>
                            <Link href={route('pickups.create')}>Registrar recojo</Link>
                        </Button>
                    )}
                </div>

                <form onSubmit={submit} className="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Buscar por orden, cliente o contacto"
                        className="sm:max-w-xs"
                    />
                    <Button type="submit" variant="secondary">
                        Buscar
                    </Button>
                </form>

                <div className="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Orden</TableHead>
                                <TableHead>Cliente</TableHead>
                                <TableHead>Sede</TableHead>
                                <TableHead>Contacto</TableHead>
                                <TableHead>Fecha Recojo</TableHead>
                                <TableHead>Cant.</TableHead>
                                <TableHead>Custodia</TableHead>
                                <TableHead className="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {pickups.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={8} className="text-center text-muted-foreground">
                                        No se encontraron registros de recojo.
                                    </TableCell>
                                </TableRow>
                            )}

                            {pickups.data.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell className="font-medium">
                                        <Link href={route('pickups.show', item.id)} className="hover:underline">
                                            {item.service_order?.codigo ?? item.service_order?.numero_orden ?? `SO #${item.service_order_id}`}
                                        </Link>
                                    </TableCell>
                                    <TableCell>{item.client?.razon_social ?? '-'}</TableCell>
                                    <TableCell>{item.site?.nombre ?? '-'}</TableCell>
                                    <TableCell>{item.contacto}</TableCell>
                                    <TableCell>{item.fecha_hora_recojo ? new Date(item.fecha_hora_recojo).toLocaleString('es-PE') : '-'}</TableCell>
                                    <TableCell>{item.cantidad}</TableCell>
                                    <TableCell>{getCustodyBadge(item)}</TableCell>
                                    <TableCell className="text-right">
                                        <Button asChild variant="outline" size="sm">
                                            <Link href={route('pickups.show', item.id)}>Ver detalle</Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {pickups.last_page > 1 && (
                    <div className="flex flex-wrap items-center justify-center gap-1">
                        {pickups.links.map((link, index) => (
                            <Button
                                key={index}
                                asChild={link.url !== null}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={link.url === null}
                            >
                                {link.url !== null ? (
                                    <Link href={link.url} preserveState dangerouslySetInnerHTML={{ __html: link.label }} />
                                ) : (
                                    <span dangerouslySetInnerHTML={{ __html: link.label }} />
                                )}
                            </Button>
                        ))}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
