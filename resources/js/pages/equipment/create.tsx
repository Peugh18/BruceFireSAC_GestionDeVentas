import { EquipmentCreateForm, type EquipmentCreateFormData } from '@/components/equipment/equipment-create-form';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type EquipmentClientOption } from '@/types/equipment';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Equipos', href: '/equipment' },
    { title: 'Alta tecnica rapida', href: '/equipment/create' },
];

export default function EquipmentCreate({
    clients,
    defaultClientId,
}: {
    clients: EquipmentClientOption[];
    defaultClientId: number | null;
}) {
    const { data, setData, post, errors, processing } = useForm<EquipmentCreateFormData>({
        client_id: defaultClientId ? String(defaultClientId) : '',
        client_site_id: '',
        vehicle_id: '',
        origen: 'desconocido',
        tipo_equipo: '',
        agente: '',
        capacidad: '',
        marca: '',
        serie_fabricante: '',
        anio_fabricacion: '',
        ubicacion: '',
        observaciones: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('equipment.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Alta tecnica rapida" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Alta tecnica rapida</CardTitle>
                        <p className="text-sm text-muted-foreground">
                            Registra un equipo fisico nuevo con los datos minimos disponibles. El codigo BRUCE FIRE se genera
                            automaticamente al guardar.
                        </p>
                    </CardHeader>
                    <CardContent>
                        <EquipmentCreateForm
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            onSubmit={submit}
                            clients={clients}
                            lockClient={defaultClientId !== null}
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
