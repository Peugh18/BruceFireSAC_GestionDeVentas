import CatalogForm from '@/components/catalog-form';
import AppLayout from '@/layouts/app-layout';
import { type CatalogItem } from '@/types/catalog';
import { Head } from '@inertiajs/react';

export default function Edit({ item }: { item: CatalogItem }) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Catálogo', href: route('catalog.index') },
                { title: 'Editar registro', href: route('catalog.edit', item.id) },
            ]}
        >
            <Head title={`Editar ${item.nombre}`} />
            <div className="p-4 md:p-6">
                <CatalogForm item={item} />
            </div>
        </AppLayout>
    );
}
