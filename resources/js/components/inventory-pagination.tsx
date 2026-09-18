import { Button } from '@/components/ui/button';
import { type InventoryPage } from '@/types/inventory';
import { Link } from '@inertiajs/react';

export default function InventoryPagination({ page, preserveState = false }: { page: Omit<InventoryPage<never>, 'data'>; preserveState?: boolean }) {
    return (
        <div className="flex flex-wrap items-center justify-between gap-3">
            <p className="text-muted-foreground text-sm">
                {page.total} registros · Página {page.current_page} de {page.last_page}
            </p>
            <nav aria-label="Paginación de inventario" className="flex gap-2">
                {(
                    [
                        ['Anterior', page.prev_page_url],
                        ['Siguiente', page.next_page_url],
                    ] as const
                ).map(([label, url]) =>
                    url ? (
                        <Button key={label} variant="outline" size="sm" asChild>
                            <Link href={url} preserveState={preserveState} preserveScroll>
                                {label}
                            </Link>
                        </Button>
                    ) : (
                        <Button key={label} variant="outline" size="sm" disabled>
                            {label}
                        </Button>
                    ),
                )}
            </nav>
        </div>
    );
}
