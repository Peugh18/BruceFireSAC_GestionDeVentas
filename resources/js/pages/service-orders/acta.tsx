import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';

interface EquipmentItem {
    id: number;
    codigo: string;
    tipo_equipo: string;
    capacidad: string | null;
    marca?: string | null;
    serie?: string | null;
    estado?: string;
}

interface ServiceOrderPickupSummary {
    id: number;
    contacto: string;
    fecha_hora_recojo: string;
    cantidad: number;
    conforme_nombre: string | null;
    conforme_dni: string | null;
    recibido_cliente_en: string | null;
    recibido_cliente_nombre: string | null;
}

interface ServiceOrderActaProps {
    serviceOrder: {
        id: number;
        codigo: string;
        numero_orden?: string;
        fecha: string;
        tipo_servicio: string;
        estado: string;
        observaciones: string | null;
        client?: {
            id: number;
            razon_social: string;
            numero_documento?: string;
            direccion?: string | null;
        };
        client_site?: {
            id: number;
            nombre: string;
            direccion?: string | null;
        } | null;
        site?: {
            id: number;
            nombre: string;
            direccion?: string | null;
        } | null;
        tecnico?: {
            id: number;
            name: string;
        } | null;
        assigned_user?: {
            id: number;
            name: string;
        } | null;
        equipment: EquipmentItem[];
        pickups: ServiceOrderPickupSummary[];
    };
}

export default function ServiceOrderActa({ serviceOrder }: ServiceOrderActaProps) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Ordenes de Servicio', href: '/service-orders' },
        { title: serviceOrder.codigo, href: `/service-orders/${serviceOrder.id}` },
        { title: 'Acta de Conformidad', href: `/service-orders/${serviceOrder.id}/acta` },
    ];

    const handlePrint = () => {
        window.print();
    };

    const siteObj = serviceOrder.client_site ?? serviceOrder.site;
    const tecnicoObj = serviceOrder.tecnico ?? serviceOrder.assigned_user;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Acta de Conformidad - ${serviceOrder.codigo}`} />

            <div className="flex flex-col gap-4 p-4 max-w-4xl mx-auto w-full">
                <div className="flex items-center justify-between print:hidden">
                    <Button asChild variant="outline">
                        <Link href={route('service-orders.show', serviceOrder.id)}>Volver a la Orden</Link>
                    </Button>
                    <Button onClick={handlePrint} variant="default">
                        Imprimir / Descargar PDF
                    </Button>
                </div>

                {/* Document Container - Styled for screen & print */}
                <div className="bg-white text-black p-8 rounded-lg border shadow-sm print:shadow-none print:border-none print:p-0">
                    {/* Header */}
                    <div className="flex justify-between items-start border-b pb-4 mb-6">
                        <div>
                            <h1 className="text-xl font-bold text-red-700 tracking-wider">BRUCE FIRE S.A.C.</h1>
                            <p className="text-xs text-gray-600">Servicios Integrales de Seguridad Contra Incendios</p>
                            <p className="text-xs text-gray-500">RUC: 20600000000</p>
                        </div>
                        <div className="text-right">
                            <h2 className="text-base font-bold uppercase">ACTA DE CONFORMIDAD DE SERVICIO</h2>
                            <p className="text-sm font-semibold text-gray-700">{serviceOrder.codigo}</p>
                            <p className="text-xs text-gray-500">Fecha: {new Date(serviceOrder.fecha).toLocaleDateString('es-PE')}</p>
                        </div>
                    </div>

                    {/* General Information */}
                    <div className="grid grid-cols-2 gap-4 text-xs mb-6 border p-3 rounded bg-gray-50/50 print:bg-transparent">
                        <div>
                            <p><span className="font-semibold">Cliente:</span> {serviceOrder.client?.razon_social ?? '-'}</p>
                            <p><span className="font-semibold">RUC/DNI:</span> {serviceOrder.client?.numero_documento ?? '-'}</p>
                            <p><span className="font-semibold">Sede:</span> {siteObj?.nombre ?? 'Sede Principal'}</p>
                            <p><span className="font-semibold">Dirección:</span> {siteObj?.direccion ?? serviceOrder.client?.direccion ?? '-'}</p>
                        </div>
                        <div>
                            <p><span className="font-semibold">Tipo de Servicio:</span> <span className="capitalize">{serviceOrder.tipo_servicio}</span></p>
                            <p><span className="font-semibold">Estado de Orden:</span> <span className="capitalize">{serviceOrder.estado}</span></p>
                            <p><span className="font-semibold">Técnico Responsable:</span> {tecnicoObj?.name ?? 'Personal BRUCE FIRE'}</p>
                            <p><span className="font-semibold">Total Equipos:</span> {serviceOrder.equipment.length} unidades</p>
                        </div>
                    </div>

                    {/* Equipment Dynamic Table (N rows) */}
                    <div className="mb-6">
                        <h3 className="text-xs font-bold uppercase tracking-wider mb-2 border-b pb-1">
                            Detalle de Equipos Atendidos ({serviceOrder.equipment.length})
                        </h3>
                        <table className="w-full text-xs border-collapse border border-gray-300">
                            <thead>
                                <tr className="bg-gray-100 print:bg-gray-200">
                                    <th className="border border-gray-300 p-2 text-center w-10">Item</th>
                                    <th className="border border-gray-300 p-2 text-left">Código / Identificación</th>
                                    <th className="border border-gray-300 p-2 text-left">Tipo de Equipo</th>
                                    <th className="border border-gray-300 p-2 text-left">Capacidad / Marca</th>
                                    <th className="border border-gray-300 p-2 text-center">Estado / Verificación</th>
                                </tr>
                            </thead>
                            <tbody>
                                {serviceOrder.equipment.length === 0 ? (
                                    <tr>
                                        <td colSpan={5} className="border border-gray-300 p-4 text-center text-gray-500 italic">
                                            No hay equipos registrados en esta orden.
                                        </td>
                                    </tr>
                                ) : (
                                    serviceOrder.equipment.map((item, index) => (
                                        <tr key={item.id} className="odd:bg-white even:bg-gray-50/50">
                                            <td className="border border-gray-300 p-2 text-center font-medium">{index + 1}</td>
                                            <td className="border border-gray-300 p-2 font-mono font-medium">{item.codigo}</td>
                                            <td className="border border-gray-300 p-2">{item.tipo_equipo}</td>
                                            <td className="border border-gray-300 p-2">
                                                {[item.capacidad, item.marca].filter(Boolean).join(' - ') || '-'}
                                            </td>
                                            <td className="border border-gray-300 p-2 text-center capitalize">
                                                {item.estado ?? 'Conforme'}
                                            </td>
                                        </tr>
                                    ))
                                )}
                            </tbody>
                        </table>
                    </div>

                    {/* Cadena de Custodia Summary */}
                    {serviceOrder.pickups.length > 0 && (
                        <div className="mb-6">
                            <h3 className="text-xs font-bold uppercase tracking-wider mb-2 border-b pb-1">
                                Resumen de Cadena de Custodia
                            </h3>
                            <div className="text-xs border p-3 rounded space-y-1">
                                {serviceOrder.pickups.map((p) => (
                                    <div key={p.id} className="flex justify-between items-center text-gray-700">
                                        <span>
                                            Recojo #{p.id} - Cantidad: {p.cantidad} eq. - Recogido el {new Date(p.fecha_hora_recojo).toLocaleDateString('es-PE')}
                                        </span>
                                        <span className="font-semibold">
                                            {p.recibido_cliente_en ? `Entrega Conforme: ${p.recibido_cliente_nombre ?? p.conforme_nombre ?? '-'}` : 'En Proceso de Custodia'}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Observaciones */}
                    {serviceOrder.observaciones && (
                        <div className="mb-6 text-xs">
                            <span className="font-semibold block mb-1">Observaciones Generales:</span>
                            <div className="border p-2 rounded bg-gray-50/50 italic text-gray-700">
                                {serviceOrder.observaciones}
                            </div>
                        </div>
                    )}

                    {/* Declaración de Conformidad */}
                    <p className="text-[11px] text-gray-600 mb-12 text-justify">
                        Por la presente, el cliente declara haber revisado y recibido a entera satisfacción los servicios y/o equipos detallados en la presente Acta de Conformidad, habiéndose verificado la operatividad y estado físico de los mismos.
                    </p>

                    {/* Signatures */}
                    <div className="grid grid-cols-2 gap-12 pt-8 text-xs text-center">
                        <div className="border-t border-gray-400 pt-2">
                            <p className="font-bold">{tecnicoObj?.name ?? 'Técnico Responsable'}</p>
                            <p className="text-gray-500">BRUCE FIRE S.A.C.</p>
                        </div>
                        <div className="border-t border-gray-400 pt-2">
                            <p className="font-bold">
                                {serviceOrder.pickups[0]?.recibido_cliente_nombre ?? serviceOrder.pickups[0]?.conforme_nombre ?? 'Firma y Sello del Cliente'}
                            </p>
                            <p className="text-gray-500">Conformidad Cliente</p>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
