import { EquipmentEditForm, type EquipmentEditFormData } from '@/components/equipment/equipment-edit-form';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type Equipment, type EquipmentVehicle } from '@/types/equipment';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function EquipmentEdit({ equipment, vehicles }: { equipment: Equipment; vehicles: EquipmentVehicle[] }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Equipos', href: '/equipment' },
        { title: equipment.codigo, href: `/equipment/${equipment.id}` },
        { title: 'Editar', href: `/equipment/${equipment.id}/edit` },
    ];

    const { data, setData, put, errors, processing } = useForm<EquipmentEditFormData>({
        vehicle_id: equipment.vehicle_id ? String(equipment.vehicle_id) : '',
        origen: equipment.origen,
        tipo_equipo: equipment.tipo_equipo,
        agente: equipment.agente ?? '',
        capacidad: equipment.capacidad ?? '',
        marca: equipment.marca ?? '',
        serie_fabricante: equipment.serie_fabricante ?? '',
        anio_fabricacion: equipment.anio_fabricacion ?? '',
        ubicacion: equipment.ubicacion ?? '',
        estado: equipment.estado,
        ultima_atencion: equipment.ultima_atencion ?? '',
        proxima_atencion: equipment.proxima_atencion ?? '',
        ultima_ph: equipment.ultima_ph ?? '',
        proxima_ph: equipment.proxima_ph ?? '',
        observaciones: equipment.observaciones ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('equipment.update', equipment.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${equipment.codigo}`} />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Editar equipo</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <EquipmentEditForm
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            onSubmit={submit}
                            vehicles={vehicles}
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
