import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { type InventoryUnit } from '@/types/inventory';
import { Check, Loader2, ScanBarcode, Search, X } from 'lucide-react';
import { KeyboardEvent, useState } from 'react';

export type SaleInventoryUnit = InventoryUnit & { catalog_item?: any };

interface UnitSelectorProps {
    catalogItemId: number;
    selectedUnitId: number | null;
    selectedUnit?: SaleInventoryUnit | null;
    onSelectUnit: (unit: SaleInventoryUnit | null) => void;
    error?: string;
    disabled?: boolean;
    placeholder?: string;
}

export function UnitSelector({
    catalogItemId,
    selectedUnitId,
    selectedUnit,
    onSelectUnit,
    error,
    disabled = false,
    placeholder = 'Escanear código o serie + Enter...',
}: UnitSelectorProps) {
    const [query, setQuery] = useState('');
    const [searching, setSearching] = useState(false);
    const [searchResults, setSearchResults] = useState<SaleInventoryUnit[]>([]);
    const [searchError, setSearchError] = useState('');
    const [localSelectedUnit, setLocalSelectedUnit] = useState<SaleInventoryUnit | null>(selectedUnit ?? null);

    const activeSelectedUnit = selectedUnit ?? localSelectedUnit;

    const performSearch = async () => {
        const trimmed = query.trim();
        if (!trimmed) {
            setSearchError('Ingresa un código de barras o serie.');
            return;
        }

        setSearching(true);
        setSearchError('');
        setSearchResults([]);

        try {
            const params = new URLSearchParams({
                catalog_item_id: String(catalogItemId),
                barcode: trimmed,
            });

            const response = await fetch(`${route('inventory-units.search')}?${params.toString()}`, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                // If barcode search had no match, try 'q' partial search
                const qParams = new URLSearchParams({
                    catalog_item_id: String(catalogItemId),
                    q: trimmed,
                });
                const qResponse = await fetch(`${route('inventory-units.search')}?${qParams.toString()}`, {
                    headers: { Accept: 'application/json' },
                });

                if (!qResponse.ok) {
                    setSearchError('No se encontró una unidad disponible con ese código o serie.');
                    setSearching(false);
                    return;
                }

                const qPayload = (await qResponse.json()) as { unit: SaleInventoryUnit | null; units: SaleInventoryUnit[] };
                handleSearchResults(qPayload);
                setSearching(false);
                return;
            }

            const payload = (await response.json()) as { unit: SaleInventoryUnit | null; units: SaleInventoryUnit[] };
            handleSearchResults(payload);
        } catch {
            setSearchError('Error al conectar con el servidor para buscar unidades.');
        } finally {
            setSearching(false);
        }
    };

    const handleSearchResults = (payload: { unit: SaleInventoryUnit | null; units: SaleInventoryUnit[] }) => {
        if (payload.unit) {
            chooseUnit(payload.unit);
            return;
        }

        if (payload.units && payload.units.length === 1) {
            chooseUnit(payload.units[0]);
            return;
        }

        if (payload.units && payload.units.length > 1) {
            setSearchResults(payload.units);
            setSearchError('');
            return;
        }

        setSearchError('No se encontró una unidad física disponible con ese criterio.');
    };

    const chooseUnit = (unit: SaleInventoryUnit) => {
        setLocalSelectedUnit(unit);
        onSelectUnit(unit);
        setSearchResults([]);
        setSearchError('');
        setQuery('');
    };

    const clearSelection = () => {
        setLocalSelectedUnit(null);
        onSelectUnit(null);
        setSearchResults([]);
        setSearchError('');
        setQuery('');
    };

    const handleKeyDown = (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            performSearch();
        }
    };

    if (selectedUnitId && activeSelectedUnit) {
        return (
            <div className="space-y-1">
                <div className="flex items-center justify-between gap-2 rounded-md border border-emerald-500/40 bg-emerald-50/50 p-2 text-sm dark:bg-emerald-950/20">
                    <div className="flex items-center gap-2 overflow-hidden">
                        <Check className="size-4 shrink-0 text-emerald-600 dark:text-emerald-400" />
                        <div className="min-w-0">
                            <span className="font-semibold text-foreground">{activeSelectedUnit.serie}</span>
                            {activeSelectedUnit.barcode && (
                                <span className="ml-1 text-xs text-muted-foreground">({activeSelectedUnit.barcode})</span>
                            )}
                            {(activeSelectedUnit.marca || activeSelectedUnit.capacidad) && (
                                <span className="ml-2 text-xs text-muted-foreground">
                                    {[activeSelectedUnit.marca, activeSelectedUnit.capacidad].filter(Boolean).join(' · ')}
                                </span>
                            )}
                        </div>
                    </div>
                    {!disabled && (
                        <Button
                            type="button"
                            variant="ghost"
                            size="icon"
                            className="size-7 shrink-0 text-muted-foreground hover:text-destructive"
                            onClick={clearSelection}
                            title="Desvincular unidad"
                        >
                            <X className="size-4" />
                        </Button>
                    )}
                </div>
                {error && <p className="text-xs text-destructive">{error}</p>}
            </div>
        );
    }

    return (
        <div className="space-y-2">
            <div className="relative flex items-center">
                <ScanBarcode className="absolute left-2.5 top-2.5 size-4 text-muted-foreground" />
                <Input
                    value={query}
                    onChange={(e) => {
                        setQuery(e.target.value);
                        if (searchError) setSearchError('');
                    }}
                    onKeyDown={handleKeyDown}
                    placeholder={placeholder}
                    className="pl-8 pr-10 text-xs"
                    disabled={disabled || searching}
                />
                <Button
                    type="button"
                    variant="ghost"
                    size="icon"
                    className="absolute right-1 top-1 size-7 text-muted-foreground"
                    onClick={performSearch}
                    disabled={disabled || searching || !query.trim()}
                >
                    {searching ? <Loader2 className="size-3.5 animate-spin" /> : <Search className="size-3.5" />}
                </Button>
            </div>

            {searchResults.length > 0 && (
                <div className="space-y-1">
                    <Select onValueChange={(val) => {
                        const found = searchResults.find((u) => u.id === Number(val));
                        if (found) chooseUnit(found);
                    }}>
                        <SelectTrigger className="text-xs">
                            <SelectValue placeholder={`Selecciona entre ${searchResults.length} unidades encontradas...`} />
                        </SelectTrigger>
                        <SelectContent>
                            {searchResults.map((unit) => (
                                <SelectItem key={unit.id} value={String(unit.id)} className="text-xs">
                                    Serie: {unit.serie} {unit.barcode ? `· Barcode: ${unit.barcode}` : ''}{' '}
                                    {unit.marca ? `· ${unit.marca}` : ''} {unit.capacidad ? `(${unit.capacidad})` : ''}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            )}

            {searchError && <p className="text-xs text-destructive">{searchError}</p>}
            {error && <p className="text-xs text-destructive">{error}</p>}
        </div>
    );
}
