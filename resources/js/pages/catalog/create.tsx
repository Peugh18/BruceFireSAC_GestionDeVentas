import CatalogForm from '@/components/catalog-form';
import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';

export default function Create() {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Catálogo', href: route('catalog.index') },
                { title: 'Nuevo registro', href: route('catalog.create') },
            ]}
        >
            <Head title="Nuevo registro de catálogo" />
            <div className="p-4 md:p-6">
                <CatalogForm />
            </div>
        </AppLayout>
    );
}
