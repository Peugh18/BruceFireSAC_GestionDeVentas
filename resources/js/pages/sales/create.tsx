import { ClientSearchCombobox } from '@/components/client-search-combobox';
import { InlineClientDialog } from '@/components/clients/inline-client-dialog';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Client, type ClientSite, type Vehicle } from '@/types';
import { type CatalogItem } from '@/types/catalog';
import { type InventoryUnit } from '@/types/inventory';
import { type FormaPago } from '@/types/sale';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, ScanBarcode, Trash2 } from 'lucide-react';
import { FormEventHandler, KeyboardEvent, useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Ventas', href: route('sales.index') },
    { title: 'Nueva venta', href: route('sales.create') },
];

// Plazo options in days for credit installments.
const PLAZOS = [
    { label: '15 días', days: 15 },
    { label: '30 días', days: 30 },
    { label: '45 días', days: 45 },
    { label: '60 días', days: 60 },
    { label: 'Personalizado', days: 0 },
] as const;

interface FormSaleItem {
    catalog_item_id: number;
    inventory_unit_id: number | null;
    cantidad: number;
    precio_unitario: number;
    descuento: number;
}

type SaleInventoryUnit = InventoryUnit & { catalog_item?: CatalogItem };

interface FormPayment {
    forma_pago: FormaPago;
    monto: number;
    referencia: string;
}

interface FormInstallment {
    numero_cuota: number;
    monto: number;
    fecha_vencimiento: string;
}

interface SaleFormState {
    [key: string]: any;
    client_id: string | number;
    client_site_id: string | number;
    vehicle_id: string | number;
    tipo_comprobante: 'boleta' | 'factura';
    fecha: string;
    condicion_pago: 'contado' | 'credito';
    observaciones: string;
    items: FormSaleItem[];
    payments: FormPayment[];
    installments: FormInstallment[];
}

export default function SaleCreate({ catalogItems }: { catalogItems: CatalogItem[] }) {
    const today = new Date().toISOString().split('T')[0];

    // selectedClient is managed locally so combobox can pass the full object
    // (with sites/vehicles) directly without an extra fetch.
    const [selectedClient, setSelectedClient] = useState<Client | null>(null);
    // plazo state: 0 = custom (user edits dates manually)
    const [plazo, setPlazo] = useState<number>(30);
    const [customPlazo, setCustomPlazo] = useState<number>(30);
    const [barcodeQuery, setBarcodeQuery] = useState('');
    const [barcodeError, setBarcodeError] = useState('');
    const [unitQueries, setUnitQueries] = useState<Record<number, string>>({});
    const [unitSearchResults, setUnitSearchResults] = useState<Record<number, SaleInventoryUnit[]>>({});
    const [unitSearchErrors, setUnitSearchErrors] = useState<Record<number, string>>({});

    const { data, setData, post, processing, errors } = useForm<SaleFormState>({
        client_id: '',
        client_site_id: '',
        vehicle_id: '',
        tipo_comprobante: 'boleta',
        fecha: today,
        condicion_pago: 'contado',
        observaciones: '',
        items: [],
        payments: [{ forma_pago: 'efectivo', monto: 0, referencia: '' }],
        installments: [],
    });

    const availableSites = selectedClient?.sites ?? [];
    const availableVehicles = selectedClient?.vehicles ?? [];
    const attentionType = data.vehicle_id ? 'VEHICULO' : data.client_site_id ? 'LOCAL' : null;

    const handleClientSelect = (client: Client) => {
        setSelectedClient(client);
        const suggestedTipo = client.tipo_documento === 'ruc' ? 'factura' : 'boleta';
        setData((prev) => ({
            ...prev,
            client_id: client.id,
            client_site_id: '',
            vehicle_id: '',
            tipo_comprobante: suggestedTipo,
        }));
    };

    const addItem = () => {
        if (catalogItems.length === 0) return;
        addCatalogItem(catalogItems[0]);
    };

    const addCatalogItem = (catalogItem: CatalogItem) => {
        setData('items', [
            ...data.items,
            {
                catalog_item_id: catalogItem.id,
                inventory_unit_id: null,
                cantidad: 1,
                precio_unitario: parseFloat(catalogItem.precio) || 0,
                descuento: 0,
            },
        ]);
    };

    const removeItem = (index: number) => {
        const next = [...data.items];
        next.splice(index, 1);
        setData('items', next);
    };

    const updateItem = (index: number, field: keyof FormSaleItem, value: number) => {
        const next = [...data.items];
        next[index] = { ...next[index], [field]: value };
        if (field === 'catalog_item_id') {
            const found = catalogItems.find((ci) => ci.id === value);
            if (found) {
                next[index].precio_unitario = parseFloat(found.precio) || 0;
                next[index].cantidad = found.control_serializado ? 1 : next[index].cantidad;
                next[index].inventory_unit_id = null;
                setUnitQueries((previous) => ({ ...previous, [index]: '' }));
                setUnitSearchResults((previous) => ({ ...previous, [index]: [] }));
                setUnitSearchErrors((previous) => ({ ...previous, [index]: '' }));
            }
        }
        setData('items', next);
    };

    const selectUnit = (index: number, unit: SaleInventoryUnit) => {
        const next = [...data.items];
        const catalogItem = unit.catalog_item ?? catalogItems.find((item) => item.id === unit.catalog_item_id);

        if (catalogItem) {
            next[index] = {
                ...next[index],
                catalog_item_id: catalogItem.id,
                inventory_unit_id: unit.id,
                cantidad: 1,
                precio_unitario: parseFloat(catalogItem.precio) || next[index].precio_unitario,
            };
        } else {
            next[index] = { ...next[index], inventory_unit_id: unit.id, cantidad: 1 };
        }

        setData('items', next);
        setUnitQueries((previous) => ({ ...previous, [index]: unit.barcode ?? unit.serie }));
        setUnitSearchResults((previous) => ({ ...previous, [index]: [] }));
        setUnitSearchErrors((previous) => ({ ...previous, [index]: '' }));
    };

    const searchUnit = async (index: number, term: string, catalogItemId?: number) => {
        const query = term.trim();
        if (!query) {
            return;
        }

        const params = new URLSearchParams({ barcode: query });
        if (catalogItemId) {
            params.set('catalog_item_id', String(catalogItemId));
        }

        const response = await fetch(`${route('inventory-units.search')}?${params.toString()}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            setUnitSearchErrors((previous) => ({ ...previous, [index]: 'No se pudo buscar la unidad escaneada.' }));
            return;
        }

        const payload = (await response.json()) as { unit: SaleInventoryUnit | null; units: SaleInventoryUnit[] };
        if (payload.unit) {
            selectUnit(index, payload.unit);
            return;
        }

        setUnitSearchResults((previous) => ({ ...previous, [index]: payload.units }));
        setUnitSearchErrors((previous) => ({
            ...previous,
            [index]: payload.units.length === 0 ? 'No se encontro una unidad disponible con ese barcode o serie.' : '',
        }));
    };

    const handleUnitSearch = async (index: number, catalogItemId: number, event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();
        await searchUnit(index, unitQueries[index] ?? '', catalogItemId);
    };

    const handleBarcodeSearch = async (event: KeyboardEvent<HTMLInputElement>) => {
        if (event.key !== 'Enter') {
            return;
        }

        event.preventDefault();

        const barcode = barcodeQuery.trim();
        if (!barcode) {
            return;
        }

        const localMatch = catalogItems.find((item) => item.codigo === barcode);
        if (localMatch) {
            addCatalogItem(localMatch);
            setBarcodeQuery('');
            setBarcodeError('');
            return;
        }

        const response = await fetch(`${route('catalog-items.search')}?barcode=${encodeURIComponent(barcode)}`, {
            headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
            setBarcodeError('No se pudo buscar el codigo escaneado.');
            return;
        }

        const payload = (await response.json()) as { item: CatalogItem | null };
        if (!payload.item) {
            const unitResponse = await fetch(`${route('inventory-units.search')}?barcode=${encodeURIComponent(barcode)}`, {
                headers: { Accept: 'application/json' },
            });

            if (!unitResponse.ok) {
                setBarcodeError('No se encontro un producto activo o unidad disponible con ese codigo.');
                return;
            }

            const unitPayload = (await unitResponse.json()) as { unit: SaleInventoryUnit | null };
            if (!unitPayload.unit?.catalog_item) {
                setBarcodeError('No se encontro un producto activo o unidad disponible con ese codigo.');
                return;
            }

            const rowIndex = data.items.length;
            setData('items', [
                ...data.items,
                {
                    catalog_item_id: unitPayload.unit.catalog_item.id,
                    inventory_unit_id: unitPayload.unit.id,
                    cantidad: 1,
                    precio_unitario: parseFloat(unitPayload.unit.catalog_item.precio) || 0,
                    descuento: 0,
                },
            ]);
            setUnitQueries((previous) => ({ ...previous, [rowIndex]: unitPayload.unit?.barcode ?? unitPayload.unit?.serie ?? '' }));
            setBarcodeQuery('');
            setBarcodeError('');
            return;
        }

        addCatalogItem(payload.item);
        setBarcodeQuery('');
        setBarcodeError('');
    };

    const totals = useMemo(() => {
        let subtotalSum = 0;
        for (const item of data.items) {
            const itemSubtotal = Math.max(0, item.cantidad * item.precio_unitario - (item.descuento || 0));
            subtotalSum += itemSubtotal;
        }
        const igv = Math.round(subtotalSum * 0.18 * 100) / 100;
        const total = Math.round((subtotalSum + igv) * 100) / 100;
        return { subtotal: subtotalSum, igv, total };
    }, [data.items]);

    /**
     * Generate installments distributed evenly across `numCuotas` payments,
     * each due `dias` days after the previous one (first due = fechaBase + dias).
     */
    const generateInstallments = (numCuotas: number, dias: number) => {
        if (numCuotas <= 0) {
            setData('installments', []);
            return;
        }
        const effectiveDias = dias > 0 ? dias : customPlazo > 0 ? customPlazo : 30;
        const montoPorCuota = Math.round((totals.total / numCuotas) * 100) / 100;
        const list: FormInstallment[] = [];
        const base = new Date(data.fecha || today);
        for (let i = 1; i <= numCuotas; i++) {
            const date = new Date(base);
            date.setDate(date.getDate() + i * effectiveDias);
            list.push({
                numero_cuota: i,
                monto: i === numCuotas ? Math.round((totals.total - montoPorCuota * (numCuotas - 1)) * 100) / 100 : montoPorCuota,
                fecha_vencimiento: date.toISOString().split('T')[0],
            });
        }
        setData('installments', list);
    };

    const handlePlazoChange = (dias: number) => {
        setPlazo(dias);
        if (dias > 0 && data.installments.length > 0) {
            generateInstallments(data.installments.length, dias);
        }
    };

    const handleCustomPlazoChange = (dias: number) => {
        setCustomPlazo(dias);
        if (data.installments.length > 0) {
            generateInstallments(data.installments.length, dias);
        }
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        if (data.condicion_pago === 'contado') {
            const payments = [...data.payments];
            if (payments[0]) {
                payments[0].monto = totals.total;
            }
            setData('payments', payments);
        }
        post(route('sales.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva venta" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center gap-4">
                    <Button asChild variant="outline" size="icon">
                        <Link href={route('sales.index')}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <HeadingSmall title="Nueva Venta Directa" description="Registra una venta comercial directa al contado o credito." />
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Datos del Cliente y Venta</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div className="space-y-2">
                                <Label htmlFor="client_id">Cliente *</Label>
                                <div className="flex gap-2">
                                    <div className="min-w-0 flex-1">
                                        <ClientSearchCombobox
                                            id="client_id"
                                            value={data.client_id}
                                            onSelect={handleClientSelect}
                                            selectedClient={selectedClient}
                                            placeholder="Buscar cliente..."
                                        />
                                    </div>
                                    <InlineClientDialog
                                        onCreated={handleClientSelect}
                                        trigger={
                                            <Button type="button" variant="outline" className="shrink-0">
                                                <Plus className="mr-1 size-4" />
                                                Nuevo cliente
                                            </Button>
                                        }
                                    />
                                </div>
                                <InputError message={errors.client_id} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="client_site_id">Sede (Opcional)</Label>
                                <Select
                                    value={data.client_site_id ? String(data.client_site_id) : ''}
                                    onValueChange={(val) => setData('client_site_id', val)}
                                    disabled={availableSites.length === 0}
                                >
                                    <SelectTrigger id="client_site_id">
                                        <SelectValue placeholder="Selecciona una sede" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {availableSites.map((s: ClientSite) => (
                                            <SelectItem key={s.id} value={String(s.id)}>
                                                {s.nombre} - {s.direccion}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.client_site_id} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="vehicle_id">Vehículo / Placa (Opcional)</Label>
                                <Select
                                    value={data.vehicle_id ? String(data.vehicle_id) : ''}
                                    onValueChange={(val) => setData('vehicle_id', val)}
                                    disabled={availableVehicles.length === 0}
                                >
                                    <SelectTrigger id="vehicle_id">
                                        <SelectValue placeholder="Selecciona un vehículo" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {availableVehicles.map((v: Vehicle) => (
                                            <SelectItem key={v.id} value={String(v.id)}>
                                                {v.placa} ({v.marca ?? ''} {v.modelo ?? ''})
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.vehicle_id} />
                            </div>

                            {attentionType && (
                                <div className="space-y-2">
                                    <Label>Tipo de atencion</Label>
                                    <div>
                                        <Badge variant="secondary">Atencion: {attentionType}</Badge>
                                    </div>
                                </div>
                            )}

                            <div className="space-y-2">
                                <Label htmlFor="tipo_comprobante">Tipo de Comprobante *</Label>
                                <Select
                                    value={data.tipo_comprobante}
                                    onValueChange={(val: 'boleta' | 'factura') => setData('tipo_comprobante', val)}
                                >
                                    <SelectTrigger id="tipo_comprobante">
                                        <SelectValue placeholder="Selecciona tipo de comprobante" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="boleta">Boleta</SelectItem>
                                        <SelectItem value="factura">Factura</SelectItem>
                                    </SelectContent>
                                </Select>
                                {data.tipo_comprobante === 'factura' && selectedClient && selectedClient.tipo_documento !== 'ruc' && (
                                    <p className="text-xs text-destructive">
                                        Una factura requiere RUC. El cliente seleccionado tiene tipo {selectedClient.tipo_documento.toUpperCase()}.
                                    </p>
                                )}
                                <InputError message={errors.tipo_comprobante} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="fecha">Fecha *</Label>
                                <Input
                                    id="fecha"
                                    type="date"
                                    value={data.fecha}
                                    onChange={(e) => setData('fecha', e.target.value)}
                                />
                                <InputError message={errors.fecha} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="condicion_pago">Condición de Pago *</Label>
                                <Select
                                    value={data.condicion_pago}
                                    onValueChange={(val: 'contado' | 'credito') => {
                                        setData('condicion_pago', val);
                                        if (val === 'credito') {
                                            generateInstallments(1, plazo);
                                        }
                                    }}
                                >
                                    <SelectTrigger id="condicion_pago">
                                        <SelectValue placeholder="Condición" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="contado">Contado</SelectItem>
                                        <SelectItem value="credito">Crédito</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.condicion_pago} />
                            </div>

                            <div className="sm:col-span-2 lg:col-span-3 space-y-2">
                                <Label htmlFor="observaciones">Observaciones</Label>
                                <Textarea
                                    id="observaciones"
                                    value={data.observaciones}
                                    onChange={(e) => setData('observaciones', e.target.value)}
                                    placeholder="Notas adicionales de la venta"
                                    rows={2}
                                />
                                <InputError message={errors.observaciones} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Ítems Vendidos</CardTitle>
                            <Button type="button" onClick={addItem} variant="outline" size="sm">
                                <Plus className="size-4 mr-1" /> Agregar Ítem
                            </Button>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <InputError message={errors.items} />

                            <div className="max-w-sm space-y-2">
                                <Label htmlFor="barcode_search">Lector de codigo de barras</Label>
                                <div className="relative">
                                    <ScanBarcode className="absolute left-3 top-2.5 size-4 text-muted-foreground" />
                                    <Input
                                        id="barcode_search"
                                        value={barcodeQuery}
                                        onChange={(event) => setBarcodeQuery(event.target.value)}
                                        onKeyDown={handleBarcodeSearch}
                                        placeholder="Escanea o escribe codigo y Enter"
                                        className="pl-9"
                                    />
                                </div>
                                {barcodeError && <p className="text-sm text-destructive">{barcodeError}</p>}
                            </div>

                            <div className="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="w-[300px]">Producto / Servicio</TableHead>
                                            <TableHead className="w-[120px]">Cantidad</TableHead>
                                            <TableHead className="w-[140px]">Precio Unit. (S/)</TableHead>
                                            <TableHead className="w-[120px]">Descuento (S/)</TableHead>
                                            <TableHead className="w-[140px] text-right">Subtotal</TableHead>
                                            <TableHead className="w-[60px]"></TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {data.items.length === 0 && (
                                            <TableRow>
                                                <TableCell colSpan={6} className="text-center text-muted-foreground py-6">
                                                    No se han agregado ítems. Haz clic en "Agregar Ítem".
                                                </TableCell>
                                            </TableRow>
                                        )}

                                        {data.items.map((item, index) => {
                                            const selectedCatalogItem = catalogItems.find((catalogItem) => catalogItem.id === item.catalog_item_id);
                                            const selectedUnit = unitSearchResults[index]?.find((unit) => unit.id === item.inventory_unit_id);
                                            const itemSubtotal = Math.max(0, item.cantidad * item.precio_unitario - (item.descuento || 0));
                                            return (
                                                <TableRow key={index}>
                                                    <TableCell>
                                                        <Select
                                                            value={String(item.catalog_item_id)}
                                                            onValueChange={(val) => updateItem(index, 'catalog_item_id', Number(val))}
                                                        >
                                                            <SelectTrigger>
                                                                <SelectValue placeholder="Selecciona ítem" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {catalogItems.map((ci) => (
                                                                    <SelectItem key={ci.id} value={String(ci.id)}>
                                                                        [{ci.codigo}] {ci.nombre}
                                                                    </SelectItem>
                                                                ))}
                                                            </SelectContent>
                                                        </Select>
                                                    </TableCell>
                                                    <TableCell>
                                                        {selectedCatalogItem?.control_serializado ? (
                                                            <div className="space-y-2">
                                                                <Input value="1" disabled />
                                                                <div className="space-y-1">
                                                                    <Input
                                                                        value={unitQueries[index] ?? ''}
                                                                        onChange={(event) => setUnitQueries((previous) => ({ ...previous, [index]: event.target.value }))}
                                                                        onKeyDown={(event) => handleUnitSearch(index, selectedCatalogItem.id, event)}
                                                                        placeholder="Serie/barcode + Enter"
                                                                    />
                                                                    {item.inventory_unit_id && (
                                                                        <p className="text-muted-foreground text-xs">
                                                                            Unidad seleccionada:{' '}
                                                                            {selectedUnit
                                                                                ? `${selectedUnit.serie}${selectedUnit.barcode ? ` · ${selectedUnit.barcode}` : ''}`
                                                                                : `#${item.inventory_unit_id}`}
                                                                        </p>
                                                                    )}
                                                                    {unitSearchResults[index]?.length > 0 && (
                                                                        <Select
                                                                            value={item.inventory_unit_id ? String(item.inventory_unit_id) : ''}
                                                                            onValueChange={(value) => {
                                                                                const unit = unitSearchResults[index]?.find((candidate) => candidate.id === Number(value));
                                                                                if (unit) {
                                                                                    selectUnit(index, unit);
                                                                                }
                                                                            }}
                                                                        >
                                                                            <SelectTrigger>
                                                                                <SelectValue placeholder="Selecciona unidad" />
                                                                            </SelectTrigger>
                                                                            <SelectContent>
                                                                                {unitSearchResults[index].map((unit) => (
                                                                                    <SelectItem key={unit.id} value={String(unit.id)}>
                                                                                        {unit.serie} {unit.barcode ? `· ${unit.barcode}` : ''}
                                                                                    </SelectItem>
                                                                                ))}
                                                                            </SelectContent>
                                                                        </Select>
                                                                    )}
                                                                    {unitSearchErrors[index] && <p className="text-xs text-destructive">{unitSearchErrors[index]}</p>}
                                                                    <InputError message={(errors as Record<string, string>)[`items.${index}.inventory_unit_id`]} />
                                                                </div>
                                                            </div>
                                                        ) : (
                                                            <Input
                                                                type="number"
                                                                min="0.01"
                                                                step="0.01"
                                                                value={item.cantidad}
                                                                onChange={(e) => updateItem(index, 'cantidad', parseFloat(e.target.value) || 0)}
                                                            />
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        <Input
                                                            type="number"
                                                            min="0"
                                                            step="0.01"
                                                            value={item.precio_unitario}
                                                            onChange={(e) => updateItem(index, 'precio_unitario', parseFloat(e.target.value) || 0)}
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <Input
                                                            type="number"
                                                            min="0"
                                                            step="0.01"
                                                            value={item.descuento}
                                                            onChange={(e) => updateItem(index, 'descuento', parseFloat(e.target.value) || 0)}
                                                        />
                                                    </TableCell>
                                                    <TableCell className="text-right font-medium">S/ {itemSubtotal.toFixed(2)}</TableCell>
                                                    <TableCell>
                                                        <Button
                                                            type="button"
                                                            variant="ghost"
                                                            size="icon"
                                                            onClick={() => removeItem(index)}
                                                            className="text-destructive hover:text-destructive"
                                                        >
                                                            <Trash2 className="size-4" />
                                                        </Button>
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                </Table>
                            </div>

                            <div className="flex flex-col items-end gap-1 text-sm pt-2">
                                <div className="flex justify-between w-64">
                                    <span className="text-muted-foreground">Subtotal:</span>
                                    <span className="font-medium">S/ {totals.subtotal.toFixed(2)}</span>
                                </div>
                                <div className="flex justify-between w-64">
                                    <span className="text-muted-foreground">IGV (18%):</span>
                                    <span className="font-medium">S/ {totals.igv.toFixed(2)}</span>
                                </div>
                                <div className="flex justify-between w-64 text-base font-semibold border-t pt-1">
                                    <span>Total:</span>
                                    <span>S/ {totals.total.toFixed(2)}</span>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {data.condicion_pago === 'contado' ? (
                        <Card>
                            <CardHeader>
                                <CardTitle>Forma de Pago (Contado)</CardTitle>
                            </CardHeader>
                            <CardContent className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label>Método de Pago</Label>
                                    <Select
                                        value={data.payments[0]?.forma_pago || 'efectivo'}
                                        onValueChange={(val: FormaPago) => {
                                            const next = [...data.payments];
                                            if (!next[0]) next[0] = { forma_pago: val, monto: totals.total, referencia: '' };
                                            else next[0].forma_pago = val;
                                            setData('payments', next);
                                        }}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="efectivo">Efectivo</SelectItem>
                                            <SelectItem value="transferencia">Transferencia bancaria</SelectItem>
                                            <SelectItem value="yape">Yape</SelectItem>
                                            <SelectItem value="plin">Plin</SelectItem>
                                            <SelectItem value="pos">POS / Tarjeta</SelectItem>
                                            <SelectItem value="deposito">Depósito</SelectItem>
                                            <SelectItem value="otro">Otro</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label>Número de Operación / Referencia</Label>
                                    <Input
                                        placeholder="Ej. Operación #123456"
                                        value={data.payments[0]?.referencia || ''}
                                        onChange={(e) => {
                                            const next = [...data.payments];
                                            if (!next[0]) next[0] = { forma_pago: 'efectivo', monto: totals.total, referencia: e.target.value };
                                            else next[0].referencia = e.target.value;
                                            setData('payments', next);
                                        }}
                                    />
                                </div>
                            </CardContent>
                        </Card>
                    ) : (
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between gap-4">
                                <CardTitle>Cronograma de Cuotas (Crédito)</CardTitle>
                                <div className="flex items-center gap-3 flex-wrap justify-end">
                                    <div className="flex items-center gap-2">
                                        <Label className="text-xs shrink-0">Plazo:</Label>
                                        <Select
                                            value={String(plazo)}
                                            onValueChange={(val) => handlePlazoChange(Number(val))}
                                        >
                                            <SelectTrigger className="w-36">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {PLAZOS.map((p) => (
                                                    <SelectItem key={p.days} value={String(p.days)}>
                                                        {p.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {plazo === 0 && (
                                            <Input
                                                type="number"
                                                min="1"
                                                max="365"
                                                className="w-20"
                                                placeholder="días"
                                                value={customPlazo}
                                                onChange={(e) => handleCustomPlazoChange(parseInt(e.target.value) || 30)}
                                            />
                                        )}
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <Label className="text-xs shrink-0">Número de Cuotas:</Label>
                                        <Input
                                            type="number"
                                            min="1"
                                            max="24"
                                            className="w-20"
                                            value={data.installments.length || 1}
                                            onChange={(e) => generateInstallments(parseInt(e.target.value) || 1, plazo)}
                                        />
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="rounded-md border">
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Cuota #</TableHead>
                                                <TableHead>Monto (S/)</TableHead>
                                                <TableHead>Fecha de Vencimiento</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {data.installments.map((inst, idx) => (
                                                <TableRow key={idx}>
                                                    <TableCell className="font-medium">Cuota {inst.numero_cuota}</TableCell>
                                                    <TableCell>
                                                        <Input
                                                            type="number"
                                                            step="0.01"
                                                            value={inst.monto}
                                                            onChange={(e) => {
                                                                const next = [...data.installments];
                                                                next[idx].monto = parseFloat(e.target.value) || 0;
                                                                setData('installments', next);
                                                            }}
                                                        />
                                                    </TableCell>
                                                    <TableCell>
                                                        <Input
                                                            type="date"
                                                            value={inst.fecha_vencimiento}
                                                            onChange={(e) => {
                                                                const next = [...data.installments];
                                                                next[idx].fecha_vencimiento = e.target.value;
                                                                setData('installments', next);
                                                            }}
                                                        />
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                        </TableBody>
                                    </Table>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    <div className="flex justify-end gap-3">
                        <Button asChild variant="outline">
                            <Link href={route('sales.index')}>Cancelar</Link>
                        </Button>
                        <Button type="submit" disabled={processing || data.items.length === 0}>
                            Completar Venta
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
