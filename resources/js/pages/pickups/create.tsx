import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

interface ServiceOrderOption {
    id: number;
    codigo: string;
    numero_orden?: string;
    client_id: number;
    client_site_id?: number | null;
    client?: { id: number; razon_social: string };
    site?: { id: number; nombre: string } | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Recojos y Custodia', href: '/pickups' },
    { title: 'Nuevo Recojo', href: '/pickups/create' },
];

export default function PickupCreate({
    serviceOrders,
    selectedOrder,
}: {
    serviceOrders: ServiceOrderOption[];
    selectedOrder: ServiceOrderOption | null;
}) {
    const initialOrder = selectedOrder ?? serviceOrders[0] ?? null;

    const { data, setData, post, processing, errors } = useForm<{
        service_order_id: string;
        client_site_id: string;
        contacto: string;
        fecha_hora_recojo: string;
        cantidad: number;
        observaciones: string;
        conforme_nombre: string;
        conforme_dni: string;
        conforme_firma: string;
        fotos: File[];
    }>({
        service_order_id: initialOrder ? String(initialOrder.id) : '',
        client_site_id: initialOrder?.client_site_id ? String(initialOrder.client_site_id) : '',
        contacto: '',
        fecha_hora_recojo: new Date().toISOString().slice(0, 16),
        cantidad: 1,
        observaciones: '',
        conforme_nombre: '',
        conforme_dni: '',
        conforme_firma: '',
        fotos: [],
    });

    const [previewUrls, setPreviewUrls] = useState<string[]>([]);

    const activeOrder = serviceOrders.find((so) => String(so.id) === data.service_order_id) ?? selectedOrder;

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        if (e.target.files) {
            const filesArray = Array.from(e.target.files);
            setData('fotos', filesArray);

            const urls = filesArray.map((file) => URL.createObjectURL(file));
            setPreviewUrls(urls);
        }
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('pickups.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Registrar Recojo de Equipos" />

            <div className="flex flex-1 flex-col gap-4 p-4 max-w-3xl mx-auto w-full">
                <div>
                    <h1 className="text-2xl font-semibold tracking-tight">Registrar Recojo de Equipos</h1>
                    <p className="text-sm text-muted-foreground">
                        Captura de recojo en sede del cliente con fotos y constancia de recepción.
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Orden de Servicio y Sede</CardTitle>
                            <CardDescription>Seleccione la orden de servicio asociada al recojo.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="service_order_id">Orden de Servicio *</Label>
                                <Select
                                    value={data.service_order_id}
                                    onValueChange={(val) => {
                                        setData('service_order_id', val);
                                        const found = serviceOrders.find((so) => String(so.id) === val);
                                        if (found?.client_site_id) {
                                            setData('client_site_id', String(found.client_site_id));
                                        }
                                    }}
                                >
                                    <SelectTrigger id="service_order_id">
                                        <SelectValue placeholder="Seleccionar orden de servicio" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {serviceOrders.map((so) => (
                                            <SelectItem key={so.id} value={String(so.id)}>
                                                {so.codigo} {so.client?.razon_social ? `- ${so.client.razon_social}` : ''}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.service_order_id && (
                                    <p className="text-sm text-destructive">{errors.service_order_id}</p>
                                )}
                            </div>

                            {activeOrder && (
                                <div className="rounded-md bg-muted p-3 text-sm space-y-1">
                                    <p>
                                        <span className="font-semibold">Cliente:</span>{' '}
                                        {activeOrder.client?.razon_social ?? 'No especificado'}
                                    </p>
                                    {activeOrder.site && (
                                        <p>
                                            <span className="font-semibold">Sede:</span> {activeOrder.site.nombre}
                                        </p>
                                    )}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Detalles del Recojo</CardTitle>
                            <CardDescription>Datos del contacto en sede, fecha, hora y cantidad.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="contacto">Nombre de Contacto en Sede *</Label>
                                    <Input
                                        id="contacto"
                                        value={data.contacto}
                                        onChange={(e) => setData('contacto', e.target.value)}
                                        placeholder="Persona que entrega los equipos"
                                        required
                                    />
                                    {errors.contacto && <p className="text-sm text-destructive">{errors.contacto}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="fecha_hora_recojo">Fecha y Hora de Recojo *</Label>
                                    <Input
                                        id="fecha_hora_recojo"
                                        type="datetime-local"
                                        value={data.fecha_hora_recojo}
                                        onChange={(e) => setData('fecha_hora_recojo', e.target.value)}
                                        required
                                    />
                                    {errors.fecha_hora_recojo && (
                                        <p className="text-sm text-destructive">{errors.fecha_hora_recojo}</p>
                                    )}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="cantidad">Cantidad de Equipos Recogidos *</Label>
                                <Input
                                    id="cantidad"
                                    type="number"
                                    min={1}
                                    value={data.cantidad}
                                    onChange={(e) => setData('cantidad', parseInt(e.target.value, 10) || 1)}
                                    required
                                />
                                {errors.cantidad && <p className="text-sm text-destructive">{errors.cantidad}</p>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="observaciones">Observaciones o Estado de Recepción</Label>
                                <Textarea
                                    id="observaciones"
                                    value={data.observaciones}
                                    onChange={(e) => setData('observaciones', e.target.value)}
                                    placeholder="Detalles sobre el estado visible de los equipos al recojo..."
                                    rows={3}
                                />
                                {errors.observaciones && <p className="text-sm text-destructive">{errors.observaciones}</p>}
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Fotografías del Recojo</CardTitle>
                            <CardDescription>Adjunte fotografías de los equipos recogidos.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="fotos">Seleccionar o tomar fotos</Label>
                                <Input
                                    id="fotos"
                                    type="file"
                                    multiple
                                    accept="image/*"
                                    onChange={handleFileChange}
                                    className="cursor-pointer"
                                />
                                {errors.fotos && <p className="text-sm text-destructive">{errors.fotos}</p>}
                            </div>

                            {previewUrls.length > 0 && (
                                <div className="grid grid-cols-2 sm:grid-cols-4 gap-2 pt-2">
                                    {previewUrls.map((url, idx) => (
                                        <div key={idx} className="relative aspect-square rounded-md overflow-hidden border">
                                            <img
                                                src={url}
                                                alt={`Vista previa ${idx + 1}`}
                                                className="w-full h-full object-cover"
                                            />
                                        </div>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Conformidad del Cliente</CardTitle>
                            <CardDescription>Firma y datos de quien autoriza el recojo.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div className="space-y-2">
                                    <Label htmlFor="conforme_nombre">Nombre Completo de quien Entrega</Label>
                                    <Input
                                        id="conforme_nombre"
                                        value={data.conforme_nombre}
                                        onChange={(e) => setData('conforme_nombre', e.target.value)}
                                        placeholder="Nombre completo"
                                    />
                                    {errors.conforme_nombre && (
                                        <p className="text-sm text-destructive">{errors.conforme_nombre}</p>
                                    )}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="conforme_dni">DNI / Documento</Label>
                                    <Input
                                        id="conforme_dni"
                                        value={data.conforme_dni}
                                        onChange={(e) => setData('conforme_dni', e.target.value)}
                                        placeholder="Número de DNI"
                                    />
                                    {errors.conforme_dni && <p className="text-sm text-destructive">{errors.conforme_dni}</p>}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex items-center justify-end gap-3 pt-4">
                        <Button type="button" variant="outline" asChild>
                            <Link href={route('pickups.index')}>Cancelar</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Guardando...' : 'Guardar Recojo'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
