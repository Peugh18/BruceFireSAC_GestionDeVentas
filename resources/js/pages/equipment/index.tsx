import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated, type SharedData } from '@/types';
import { estadoEquipoLabels, type EstadoEquipo, type Equipment } from '@/types/equipment';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Equipos', href: '/equipment' }];

const ESTADOS = Object.entries(estadoEquipoLabels) as [EstadoEquipo, string][];

function estadoBadgeVariant(estado: EstadoEquipo): 'default' | 'secondary' | 'destructive' {
    if (estado === 'activo') {
        return 'default';
    }

    if (estado === 'baja_definitiva' || estado === 'no_localizado') {
        return 'destructive';
    }

    return 'secondary';
}

export default function EquipmentIndex({ equipment, filters }: { equipment: Paginated<Equipment>; filters: { search: string; estado: string } }) {
    const { auth } = usePage<SharedData>().props;
    const canCreate = auth.permissions.includes('equipment.create');

    const [search, setSearch] = useState(filters.search ?? '');
    const [estado, setEstado] = useState(filters.estado || 'todos');

    const applyFilters = (nextSearch: string, nextEstado: string) => {
        router.get(
            route('equipment.index'),
            {
                search: nextSearch || undefined,
                estado: nextEstado === 'todos' ? undefined : nextEstado,
            },
            { preserveState: true, replace: true },
        );
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilters(search, estado);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Equipos" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Equipos</h1>
                        <p className="text-sm text-muted-foreground">Equipos fisicos de los clientes, con su codigo BRUCE FIRE.</p>
                    </div>

                    {canCreate && (
                        <Button asChild>
                            <Link href={route('equipment.create')}>Alta tecnica rapida</Link>
                        </Button>
                    )}
                </div>

                <form onSubmit={submit} className="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Buscar por codigo, serie, marca o tipo"
                        className="sm:max-w-xs"
                    />

                    <Select
                        value={estado}
                        onValueChange={(value) => {
                            setEstado(value);
                            applyFilters(search, value);
                        }}
                    >
                        <SelectTrigger className="sm:w-52">
                            <SelectValue placeholder="Estado" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="todos">Todos los estados</SelectItem>
                            {ESTADOS.map(([value, label]) => (
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
                                <TableHead>Codigo</TableHead>
                                <TableHead>Cliente</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Marca</TableHead>
                                <TableHead>Estado</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {equipment.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-center text-muted-foreground">
                                        No se encontraron equipos.
                                    </TableCell>
                                </TableRow>
                            )}

                            {equipment.data.map((item) => (
                                <TableRow key={item.id}>
                                    <TableCell className="font-medium">
                                        <Link href={route('equipment.show', item.id)} className="hover:underline">
                                            {item.codigo}
                                        </Link>
                                    </TableCell>
                                    <TableCell>{item.client?.razon_social ?? '-'}</TableCell>
                                    <TableCell>{item.tipo_equipo}</TableCell>
                                    <TableCell>{item.marca ?? '-'}</TableCell>
                                    <TableCell>
                                        <Badge variant={estadoBadgeVariant(item.estado)}>{estadoEquipoLabels[item.estado]}</Badge>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {equipment.last_page > 1 && (
                    <div className="flex flex-wrap items-center justify-center gap-1">
                        {equipment.links.map((link, index) => (
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
