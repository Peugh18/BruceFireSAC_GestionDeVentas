import { ClientForm, type ClientFormData } from '@/components/clients/client-form';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Clientes', href: '/clients' },
    { title: 'Nuevo cliente', href: '/clients/create' },
];

export default function ClientsCreate() {
    const { data, setData, post, errors, processing } = useForm<ClientFormData>({
        tipo_documento: 'ruc',
        numero_documento: '',
        razon_social: '',
        nombre_comercial: '',
        telefono: '',
        whatsapp: '',
        email: '',
        direccion_fiscal: '',
        departamento: '',
        provincia: '',
        distrito: '',
        ubigeo: '',
        activo: true,
        observaciones: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();

        post(route('clients.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nuevo cliente" />

            <div className="flex flex-1 flex-col gap-4 p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Nuevo cliente</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <ClientForm
                            data={data}
                            setData={setData}
                            errors={errors}
                            processing={processing}
                            onSubmit={submit}
                            submitLabel="Crear cliente"
                        />
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
