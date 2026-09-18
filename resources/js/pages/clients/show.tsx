import { SiteFormDialog } from '@/components/clients/site-form-dialog';
import { VehicleFormDialog } from '@/components/clients/vehicle-form-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Client, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';

const TIPO_DOCUMENTO_LABEL: Record<string, string> = {
    dni: 'DNI',
    ruc: 'RUC',
    ce: 'CE',
    pasaporte: 'Pasaporte',
};

const TIPO_SEDE_LABEL: Record<string, string> = {
    oficina: 'Oficina',
    tienda: 'Tienda',
    planta: 'Planta',
    almacen: 'Almacen',
    local: 'Local',
    sucursal: 'Sucursal',
    otra: 'Otra',
};

function InfoRow({ label, value }: { label: string; value: string | null }) {
    return (
        <div className="grid grid-cols-3 gap-2 py-2 text-sm">
            <dt className="text-muted-foreground">{label}</dt>
            <dd className="col-span-2">{value && value.length > 0 ? value : '-'}</dd>
        </div>
    );
}

export default function ClientsShow({ client }: { client: Client }) {
    const { auth } = usePage<SharedData>().props;
    const canUpdate = auth.permissions.includes('clients.update');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Clientes', href: '/clients' },
        { title: client.razon_social, href: `/clients/${client.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={client.razon_social} />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <div className="flex items-center gap-2">
                            <h1 className="text-2xl font-semibold tracking-tight">{client.razon_social}</h1>
                            <Badge variant={client.activo ? 'default' : 'secondary'}>{client.activo ? 'Activo' : 'Inactivo'}</Badge>
                        </div>
                        <p className="text-sm text-muted-foreground">
                            {client.codigo} · {TIPO_DOCUMENTO_LABEL[client.tipo_documento]} {client.numero_documento}
                        </p>
                    </div>

                    {canUpdate && (
                        <Button asChild variant="outline">
                            <Link href={route('clients.edit', client.id)}>Editar cliente</Link>
                        </Button>
                    )}
                </div>

                <Tabs defaultValue="resumen">
                    <TabsList>
                        <TabsTrigger value="resumen">Resumen</TabsTrigger>
                        <TabsTrigger value="sedes">Sedes</TabsTrigger>
                        <TabsTrigger value="vehiculos">Vehiculos</TabsTrigger>
                        <TabsTrigger value="equipos">Equipos</TabsTrigger>
                        <TabsTrigger value="cotizaciones">Cotizaciones</TabsTrigger>
                        <TabsTrigger value="ventas">Ventas</TabsTrigger>
                        <TabsTrigger value="servicios">Servicios</TabsTrigger>
                        <TabsTrigger value="certificados">Certificados</TabsTrigger>
                        <TabsTrigger value="comprobantes">Comprobantes</TabsTrigger>
                        <TabsTrigger value="cobranzas">Cobranzas</TabsTrigger>
                        <TabsTrigger value="historial">Historial</TabsTrigger>
                    </TabsList>

                    <TabsContent value="resumen">
                        <Card>
                            <CardHeader>
                                <CardTitle>Datos del cliente</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <dl className="divide-y">
                                    <InfoRow label="Nombre comercial" value={client.nombre_comercial} />
                                    <InfoRow label="Telefono" value={client.telefono} />
                                    <InfoRow label="WhatsApp" value={client.whatsapp} />
                                    <InfoRow label="Correo" value={client.email} />
                                    <InfoRow label="Direccion fiscal" value={client.direccion_fiscal} />
                                    <InfoRow label="Departamento" value={client.departamento} />
                                    <InfoRow label="Provincia" value={client.provincia} />
                                    <InfoRow label="Distrito" value={client.distrito} />
                                    <InfoRow label="Ubigeo" value={client.ubigeo} />
                                    <InfoRow label="Observaciones" value={client.observaciones} />
                                </dl>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="sedes">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0">
                                <CardTitle>Sedes</CardTitle>
                                {canUpdate && (
                                    <SiteFormDialog client={client} trigger={<Button size="sm">Nueva sede</Button>} />
                                )}
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Nombre</TableHead>
                                            <TableHead>Tipo</TableHead>
                                            <TableHead>Direccion</TableHead>
                                            <TableHead>Contacto</TableHead>
                                            <TableHead>Estado</TableHead>
                                            {canUpdate && <TableHead className="text-right">Acciones</TableHead>}
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {(client.sites ?? []).length === 0 && (
                                            <TableRow>
                                                <TableCell colSpan={canUpdate ? 6 : 5} className="text-center text-muted-foreground">
                                                    Este cliente aun no tiene sedes registradas.
                                                </TableCell>
                                            </TableRow>
                                        )}

                                        {(client.sites ?? []).map((site) => (
                                            <TableRow key={site.id}>
                                                <TableCell className="font-medium">{site.nombre}</TableCell>
                                                <TableCell>{TIPO_SEDE_LABEL[site.tipo]}</TableCell>
                                                <TableCell>{site.direccion}</TableCell>
                                                <TableCell>{site.contacto ?? '-'}</TableCell>
                                                <TableCell>
                                                    <Badge variant={site.activo ? 'default' : 'secondary'}>
                                                        {site.activo ? 'Activa' : 'Inactiva'}
                                                    </Badge>
                                                </TableCell>
                                                {canUpdate && (
                                                    <TableCell className="text-right">
                                                        <SiteFormDialog
                                                            client={client}
                                                            site={site}
                                                            trigger={
                                                                <Button variant="ghost" size="sm">
                                                                    Editar
                                                                </Button>
                                                            }
                                                        />
                                                    </TableCell>
                                                )}
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <TabsContent value="vehiculos">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0">
                                <CardTitle>Vehiculos</CardTitle>
                                {canUpdate && (
                                    <VehicleFormDialog client={client} trigger={<Button size="sm">Nuevo vehiculo</Button>} />
                                )}
                            </CardHeader>
                            <CardContent>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Placa</TableHead>
                                            <TableHead>Marca</TableHead>
                                            <TableHead>Modelo</TableHead>
                                            <TableHead>Estado</TableHead>
                                            {canUpdate && <TableHead className="text-right">Acciones</TableHead>}
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {(client.vehicles ?? []).length === 0 && (
                                            <TableRow>
                                                <TableCell colSpan={canUpdate ? 5 : 4} className="text-center text-muted-foreground">
                                                    Este cliente aun no tiene vehiculos registrados.
                                                </TableCell>
                                            </TableRow>
                                        )}

                                        {(client.vehicles ?? []).map((vehicle) => (
                                            <TableRow key={vehicle.id}>
                                                <TableCell className="font-medium">{vehicle.placa}</TableCell>
                                                <TableCell>{vehicle.marca ?? '-'}</TableCell>
                                                <TableCell>{vehicle.modelo ?? '-'}</TableCell>
                                                <TableCell>
                                                    <Badge variant={vehicle.activo ? 'default' : 'secondary'}>
                                                        {vehicle.activo ? 'Activo' : 'Inactivo'}
                                                    </Badge>
                                                </TableCell>
                                                {canUpdate && (
                                                    <TableCell className="text-right">
                                                        <VehicleFormDialog
                                                            client={client}
                                                            vehicle={vehicle}
                                                            trigger={
                                                                <Button variant="ghost" size="sm">
                                                                    Editar
                                                                </Button>
                                                            }
                                                        />
                                                    </TableCell>
                                                )}
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    {['equipos', 'cotizaciones', 'ventas', 'servicios', 'certificados', 'comprobantes', 'cobranzas', 'historial'].map((tab) => (
                        <TabsContent key={tab} value={tab}>
                            <Card>
                                <CardContent className="flex min-h-[30vh] items-center justify-center text-sm text-muted-foreground">
                                    Proximamente.
                                </CardContent>
                            </Card>
                        </TabsContent>
                    ))}
                </Tabs>
            </div>
        </AppLayout>
    );
}
