import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Client, type Paginated, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Clientes', href: '/clients' }];

const TIPO_DOCUMENTO_LABEL: Record<string, string> = {
    dni: 'DNI',
    ruc: 'RUC',
    ce: 'CE',
    pasaporte: 'Pasaporte',
};

export default function ClientsIndex({ clients, filters }: { clients: Paginated<Client>; filters: { search: string; estado: string } }) {
    const { auth } = usePage<SharedData>().props;
    const canCreate = auth.permissions.includes('clients.create');
    const canUpdate = auth.permissions.includes('clients.update');

    const [search, setSearch] = useState(filters.search ?? '');
    const [estado, setEstado] = useState(filters.estado || 'todos');

    const applyFilters = (nextSearch: string, nextEstado: string) => {
        router.get(
            route('clients.index'),
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
            <Head title="Clientes" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-tight">Clientes</h1>
                        <p className="text-sm text-muted-foreground">Administra la cartera de clientes, sedes y vehiculos.</p>
                    </div>

                    {canCreate && (
                        <Button asChild>
                            <Link href={route('clients.create')}>Nuevo cliente</Link>
                        </Button>
                    )}
                </div>

                <form onSubmit={submit} className="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Buscar por codigo, razon social o documento"
                        className="sm:max-w-xs"
                    />

                    <Select
                        value={estado}
                        onValueChange={(value) => {
                            setEstado(value);
                            applyFilters(search, value);
                        }}
                    >
                        <SelectTrigger className="sm:w-40">
                            <SelectValue placeholder="Estado" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="todos">Todos</SelectItem>
                            <SelectItem value="activo">Activos</SelectItem>
                            <SelectItem value="inactivo">Inactivos</SelectItem>
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
                                <TableHead>Documento</TableHead>
                                <TableHead>Razon social</TableHead>
                                <TableHead>Telefono</TableHead>
                                <TableHead>Estado</TableHead>
                                {canUpdate && <TableHead className="text-right">Acciones</TableHead>}
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {clients.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={canUpdate ? 6 : 5} className="text-center text-muted-foreground">
                                        No se encontraron clientes.
                                    </TableCell>
                                </TableRow>
                            )}

                            {clients.data.map((client) => (
                                <TableRow key={client.id}>
                                    <TableCell className="font-medium">{client.codigo}</TableCell>
                                    <TableCell>
                                        {TIPO_DOCUMENTO_LABEL[client.tipo_documento]} {client.numero_documento}
                                    </TableCell>
                                    <TableCell>
                                        <Link href={route('clients.show', client.id)} className="hover:underline">
                                            {client.razon_social}
                                        </Link>
                                        {client.nombre_comercial && (
                                            <p className="text-xs text-muted-foreground">{client.nombre_comercial}</p>
                                        )}
                                    </TableCell>
                                    <TableCell>{client.telefono ?? '-'}</TableCell>
                                    <TableCell>
                                        <Badge variant={client.activo ? 'default' : 'secondary'}>
                                            {client.activo ? 'Activo' : 'Inactivo'}
                                        </Badge>
                                    </TableCell>
                                    {canUpdate && (
                                        <TableCell className="text-right">
                                            <Button asChild variant="ghost" size="sm">
                                                <Link href={route('clients.edit', client.id)}>Editar</Link>
                                            </Button>
                                        </TableCell>
                                    )}
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {clients.last_page > 1 && (
                    <div className="flex flex-wrap items-center justify-center gap-1">
                        {clients.links.map((link, index) => (
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
