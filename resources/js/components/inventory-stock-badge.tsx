import { Badge } from '@/components/ui/badge';

interface InventoryStockBadgeProps {
    stockActual: string | number;
    stockMinimo: string | number;
}

export function InventoryStockBadge({ stockActual, stockMinimo }: InventoryStockBadgeProps) {
    if (Number(stockActual) < Number(stockMinimo)) {
        return <Badge variant="destructive">Bajo el mínimo</Badge>;
    }

    return <Badge variant="secondary">{Number(stockActual) === 0 ? 'Sin existencias' : 'Stock suficiente'}</Badge>;
}
