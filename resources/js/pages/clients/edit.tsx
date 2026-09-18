import { ClientForm, type ClientFormData } from '@/components/clients/client-form';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Client } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

export default function ClientsEdit({ client }: { client: Client }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Clientes', href: '/clients' },
        { title: client.razon_social, href: `/clients/${client.id}` },
        { title: 'Editar', href: `/clients/${client.id}/edit` },
    ];

    const { data, setData, put, errors, processing } = useForm<ClientFormData>({
        tipo_documento: client.tipo_documento,
        numero_documento: client.numero_documento,
        razon_social: client.razon_social,
        nombre_comercial: client.nombre_comercial ?? '',
        telefono: client.telefono ?? '',
        whatsapp: client.whatsapp ?? '',
        email: client.email ?? '',
        direccion_fiscal: client.direccion_fiscal ?? '',
        departamento: client.departamento ?? '',
        provincia: client.provincia ?? '',
        distrito: client.distrito ?? '',
        ubigeo: client.ubigeo ?? '',
        activo: client.activo,
        observaciones: client.observaciones ?? '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        put(route('clients.update', client.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar ${client.razon_social}`} />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Editar cliente</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ClientForm
                            data={data}
                            setData={setData}
                            setValues={(values) => setData((previous) => ({ ...previous, ...values }))}
                            errors={errors}
                            processing={processing}
                            onSubmit={submit}
                            submitLabel="Guardar cambios"
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
