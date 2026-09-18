import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { type PickupPhoto, type ServiceOrderPickupItem } from '@/types/pickup';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler, useState } from 'react';

export default function PickupShow({
    pickup,
    fotos,
}: {
    pickup: ServiceOrderPickupItem;
    fotos: PickupPhoto[];
}) {
    const { auth } = usePage<SharedData>().props;
    const canManageCustody = auth.permissions.includes('pickups.custody');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Recojos y Custodia', href: '/pickups' },
        { title: `Recojo #${pickup.id}`, href: `/pickups/${pickup.id}` },
    ];

    const custodyForm = useForm<{
        step: 'recibido_planta' | 'entregado' | 'recibido_cliente';
        extra_info: string;
    }>({
        step: 'recibido_planta',
        extra_info: '',
    });

    const [isClientModalOpen, setIsClientModalOpen] = useState(false);

    const advanceStep = (step: 'recibido_planta' | 'entregado' | 'recibido_cliente', extraInfo?: string) => {
        custodyForm.setData({
            step,
            extra_info: extraInfo ?? '',
        });
        custodyForm.post(route('pickups.custody.update', pickup.id), {
            onSuccess: () => {
                setIsClientModalOpen(false);
            },
        });
    };

    const handleClientConfirmSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        advanceStep('recibido_cliente', custodyForm.data.extra_info);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Recojo #${pickup.id} - Cadena de Custodia`} />

            <div className="flex flex-1 flex-col gap-6 p-4 max-w-5xl mx-auto w-full">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <div>
                        <div className="flex items-center gap-2">
                            <h1 className="text-2xl font-semibold tracking-tight">Recojo #{pickup.id}</h1>
                            {pickup.recibido_cliente_en ? (
                                <Badge variant="default">Conforme Cliente</Badge>
                            ) : pickup.entregado_en ? (
                                <Badge className="bg-blue-600 hover:bg-blue-700">Entregado</Badge>
                            ) : pickup.recibido_planta_en ? (
                                <Badge className="bg-amber-600 hover:bg-amber-700">En Planta</Badge>
                            ) : (
                                <Badge variant="secondary">Recogido</Badge>
                            )}
                        </div>
                        <p className="text-sm text-muted-foreground">
                            Orden de Servicio:{' '}
                            <Link
                                href={route('service-orders.show', pickup.service_order_id)}
                                className="font-medium text-primary hover:underline"
                            >
                                {pickup.service_order?.codigo ?? pickup.service_order?.numero_orden ?? `#${pickup.service_order_id}`}
                            </Link>
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2">
                        <Button asChild variant="outline">
                            <Link href={route('service-orders.show', pickup.service_order_id)}>
                                Ver Orden de Servicio
                            </Link>
                        </Button>
                        <Button asChild variant="default">
                            <Link href={route('service-orders.acta.show', pickup.service_order_id)}>
                                Ver Acta de Conformidad
                            </Link>
                        </Button>
                    </div>
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <Card className="md:col-span-2">
                        <CardHeader>
                            <CardTitle>Cadena de Custodia</CardTitle>
                            <CardDescription>Seguimiento de la trazabilidad y responsabilidad del equipo.</CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-6">
                            <div className="relative border-l border-muted pl-6 space-y-6">
                                {/* Paso 1: Recojo */}
                                <div className="relative">
                                    <div className="absolute -left-9 top-0 flex h-6 w-6 items-center justify-center rounded-full bg-primary text-xs font-semibold text-primary-foreground">
                                        1
                                    </div>
                                    <div>
                                        <h4 className="text-sm font-semibold">1. Recogido en Sede del Cliente</h4>
                                        <p className="text-sm text-muted-foreground">
                                            Recogido por:{' '}
                                            <span className="font-medium text-foreground">
                                                {pickup.recogido_por_user?.name ?? 'Técnico de campo'}
                                            </span>
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Fecha:{' '}
                                            {pickup.recogido_en ? new Date(pickup.recogido_en).toLocaleString('es-PE') : '-'}
                                        </p>
                                        {pickup.conforme_nombre && (
                                            <p className="text-xs text-muted-foreground mt-1">
                                                Entregado por cliente: {pickup.conforme_nombre} (DNI: {pickup.conforme_dni ?? '-'})
                                            </p>
                                        )}
                                    </div>
                                </div>

                                {/* Paso 2: Recibido en Planta */}
                                <div className="relative">
                                    <div
                                        className={`absolute -left-9 top-0 flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold ${
                                            pickup.recibido_planta_en
                                                ? 'bg-amber-600 text-white'
                                                : 'bg-muted text-muted-foreground'
                                        }`}
                                    >
                                        2
                                    </div>
                                    <div>
                                        <h4 className="text-sm font-semibold">2. Recepción en Planta / Taller</h4>
                                        {pickup.recibido_planta_en ? (
                                            <>
                                                <p className="text-sm text-muted-foreground">
                                                    Recibido en planta por:{' '}
                                                    <span className="font-medium text-foreground">
                                                        {pickup.recibido_planta_user?.name ?? 'Responsable de planta'}
                                                    </span>
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    Fecha: {new Date(pickup.recibido_planta_en).toLocaleString('es-PE')}
                                                </p>
                                            </>
                                        ) : (
                                            <div className="mt-2">
                                                <p className="text-xs text-muted-foreground mb-2">
                                                    Pendiente de ingreso a planta.
                                                </p>
                                                {canManageCustody && (
                                                    <Button
                                                        size="sm"
                                                        variant="secondary"
                                                        onClick={() => advanceStep('recibido_planta')}
                                                        disabled={custodyForm.processing}
                                                    >
                                                        Confirmar Ingreso a Planta
                                                    </Button>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Paso 3: Entregado por Planta */}
                                <div className="relative">
                                    <div
                                        className={`absolute -left-9 top-0 flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold ${
                                            pickup.entregado_en ? 'bg-blue-600 text-white' : 'bg-muted text-muted-foreground'
                                        }`}
                                    >
                                        3
                                    </div>
                                    <div>
                                        <h4 className="text-sm font-semibold">3. Despachado / Entregado</h4>
                                        {pickup.entregado_en ? (
                                            <>
                                                <p className="text-sm text-muted-foreground">
                                                    Entregado por:{' '}
                                                    <span className="font-medium text-foreground">
                                                        {pickup.entregado_por_user?.name ?? 'Personal de transporte'}
                                                    </span>
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    Fecha: {new Date(pickup.entregado_en).toLocaleString('es-PE')}
                                                </p>
                                            </>
                                        ) : (
                                            <div className="mt-2">
                                                <p className="text-xs text-muted-foreground mb-2">
                                                    {pickup.recibido_planta_en
                                                        ? 'Equipos listos para retorno.'
                                                        : 'Requiere confirmación previa en planta.'}
                                                </p>
                                                {canManageCustody && pickup.recibido_planta_en && (
                                                    <Button
                                                        size="sm"
                                                        variant="secondary"
                                                        onClick={() => advanceStep('entregado')}
                                                        disabled={custodyForm.processing}
                                                    >
                                                        Registrar Despacho / Salida
                                                    </Button>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                {/* Paso 4: Conforme Cliente */}
                                <div className="relative">
                                    <div
                                        className={`absolute -left-9 top-0 flex h-6 w-6 items-center justify-center rounded-full text-xs font-semibold ${
                                            pickup.recibido_cliente_en
                                                ? 'bg-emerald-600 text-white'
                                                : 'bg-muted text-muted-foreground'
                                        }`}
                                    >
                                        4
                                    </div>
                                    <div>
                                        <h4 className="text-sm font-semibold">4. Recepción Conforme por Cliente</h4>
                                        {pickup.recibido_cliente_en ? (
                                            <>
                                                <p className="text-sm text-muted-foreground">
                                                    Recibido conforme por:{' '}
                                                    <span className="font-medium text-foreground">
                                                        {pickup.recibido_cliente_nombre ?? pickup.conforme_nombre ?? 'Cliente'}
                                                    </span>
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    Registrado por user:{' '}
                                                    {pickup.recibido_cliente_por_user?.name ?? 'Técnico'}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    Fecha: {new Date(pickup.recibido_cliente_en).toLocaleString('es-PE')}
                                                </p>
                                            </>
                                        ) : (
                                            <div className="mt-2">
                                                <p className="text-xs text-muted-foreground mb-2">
                                                    {pickup.entregado_en
                                                        ? 'Pendiente de firma / conformidad de entrega.'
                                                        : 'Requiere registro de despacho previo.'}
                                                </p>
                                                {canManageCustody && pickup.entregado_en && (
                                                    <Dialog
                                                        open={isClientModalOpen}
                                                        onOpenChange={setIsClientModalOpen}
                                                    >
                                                        <DialogTrigger asChild>
                                                            <Button size="sm" variant="default">
                                                                Registrar Conformidad de Cliente
                                                            </Button>
                                                        </DialogTrigger>
                                                        <DialogContent>
                                                            <form onSubmit={handleClientConfirmSubmit}>
                                                                <DialogHeader>
                                                                    <DialogTitle>Conformidad de Recepción</DialogTitle>
                                                                    <DialogDescription>
                                                                        Ingrese el nombre de la persona en cliente que recibe los equipos.
                                                                    </DialogDescription>
                                                                </DialogHeader>
                                                                <div className="py-4 space-y-3">
                                                                    <Label htmlFor="extra_info">Nombre de quien recibe</Label>
                                                                    <Input
                                                                        id="extra_info"
                                                                        value={custodyForm.data.extra_info}
                                                                        onChange={(e) =>
                                                                            custodyForm.setData('extra_info', e.target.value)
                                                                        }
                                                                        placeholder={pickup.conforme_nombre || 'Nombre completo'}
                                                                    />
                                                                </div>
                                                                <DialogFooter>
                                                                    <Button
                                                                        type="button"
                                                                        variant="outline"
                                                                        onClick={() => setIsClientModalOpen(false)}
                                                                    >
                                                                        Cancelar
                                                                    </Button>
                                                                    <Button type="submit" disabled={custodyForm.processing}>
                                                                        Confirmar Entrega Final
                                                                    </Button>
                                                                </DialogFooter>
                                                            </form>
                                                        </DialogContent>
                                                    </Dialog>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Datos Generales</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                <div>
                                    <span className="text-muted-foreground block text-xs">Cliente</span>
                                    <span className="font-medium">{pickup.client?.razon_social ?? '-'}</span>
                                </div>
                                <div>
                                    <span className="text-muted-foreground block text-xs">Sede</span>
                                    <span>{pickup.site?.nombre ?? 'Sede principal / No asignada'}</span>
                                </div>
                                <div>
                                    <span className="text-muted-foreground block text-xs">Contacto en Sede</span>
                                    <span>{pickup.contacto}</span>
                                </div>
                                <div>
                                    <span className="text-muted-foreground block text-xs">Cantidad Recogida</span>
                                    <span className="font-medium">{pickup.cantidad} unidades</span>
                                </div>
                                <div>
                                    <span className="text-muted-foreground block text-xs">Fecha y Hora Programada</span>
                                    <span>
                                        {pickup.fecha_hora_recojo
                                            ? new Date(pickup.fecha_hora_recojo).toLocaleString('es-PE')
                                            : '-'}
                                    </span>
                                </div>
                                {pickup.observaciones && (
                                    <div>
                                        <span className="text-muted-foreground block text-xs">Observaciones</span>
                                        <p className="text-xs rounded border bg-muted/40 p-2 mt-1">{pickup.observaciones}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>

                {fotos.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Fotografías del Recojo ({fotos.length})</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
                                {fotos.map((foto) => (
                                    <a
                                        key={foto.id}
                                        href={foto.url}
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="group relative aspect-square rounded-lg border overflow-hidden bg-muted"
                                    >
                                        <img
                                            src={foto.url}
                                            alt={foto.file_name}
                                            className="w-full h-full object-cover transition-transform group-hover:scale-105"
                                        />
                                        <div className="absolute inset-x-0 bottom-0 bg-black/60 p-1 text-center text-[10px] text-white truncate">
                                            {foto.file_name}
                                        </div>
                                    </a>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
