import CatalogForm from '@/components/catalog-form';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { type CatalogItem } from '@/types/catalog';
import { Head, Link } from '@inertiajs/react';
import { History, Plus } from 'lucide-react';

export default function Edit({ item }: { item: CatalogItem }) {
    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Catálogo', href: route('catalog.index') },
                { title: 'Editar registro', href: route('catalog.edit', item.id) },
            ]}
        >
            <Head title={`Editar ${item.nombre}`} />
            <div className="space-y-6 p-4 md:p-6">
                {item.controla_stock && (
                    <div className="flex flex-wrap gap-2">
                        <Button variant="outline" asChild>
                            <Link href={route('inventory.receive', { catalog_item_id: item.id })}>
                                <Plus className="size-4" />
                                Recibir mercadería
                            </Link>
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={route('inventory.movements', { catalog_item_id: item.id })}>
                                <History className="size-4" />
                                Ver movimientos
                            </Link>
                        </Button>
                    </div>
                )}
                <CatalogForm item={item} />
            </div>
        </AppLayout>
    );
}
