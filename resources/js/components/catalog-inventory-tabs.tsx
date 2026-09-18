import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';

interface CatalogInventoryTabsProps {
    active: 'catalog' | 'inventory';
}

export function CatalogInventoryTabs({ active }: CatalogInventoryTabsProps) {
    const { auth } = usePage<SharedData>().props;
    const canViewCatalog = auth.permissions.includes('catalog.view');
    const canViewInventory = auth.permissions.includes('inventory.view');

    if (!canViewCatalog && !canViewInventory) {
        return null;
    }

    const tabClass = (tab: 'catalog' | 'inventory') =>
        cn(
            'rounded-sm px-3 py-1.5 text-sm font-medium transition-colors',
            active === tab ? 'bg-background text-foreground shadow-xs' : 'text-muted-foreground hover:bg-background/50 hover:text-foreground',
        );

    return (
        <nav aria-label="Navegacion de catalogo e inventario" className="overflow-x-auto">
            <div className="inline-flex h-10 items-center justify-start gap-1 rounded-md bg-muted p-1">
                {canViewCatalog && (
                    <Button asChild variant="ghost" className={tabClass('catalog')}>
                        <Link href={route('catalog.index')}>Productos y servicios</Link>
                    </Button>
                )}
                {canViewInventory && (
                    <Button asChild variant="ghost" className={tabClass('inventory')}>
                        <Link href={route('inventory.index')}>Inventario</Link>
                    </Button>
                )}
            </div>
        </nav>
    );
}
