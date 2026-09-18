import { ClientSearchCombobox } from '@/components/client-search-combobox';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
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
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import { FormEventHandler, useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Cotizaciones', href: route('quotes.index') },
    { title: 'Nueva cotización', href: route('quotes.create') },
];

interface FormQuoteItem {
    catalog_item_id: number;
    cantidad: number;
    precio_unitario: number;
    descuento: number;
}

interface QuoteFormState {
    [key: string]: any;
    client_id: string | number;
    client_site_id: string | number;
    vehicle_id: string | number;
    fecha: string;
    vigencia: string;
    condicion_propuesta: 'contado' | 'credito';
    observaciones: string;
    items: FormQuoteItem[];
}

export default function QuoteCreate({
    clients = [],
    catalogItems,
}: {
    clients?: Client[];
    catalogItems: CatalogItem[];
}) {
    const today = new Date().toISOString().split('T')[0];
    const validityDefault = new Date(Date.now() + 15 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];

    const [selectedClient, setSelectedClient] = useState<Client | null>(null);

    const { data, setData, post, processing, errors } = useForm<QuoteFormState>({
        client_id: '',
        client_site_id: '',
        vehicle_id: '',
        fecha: today,
        vigencia: validityDefault,
        condicion_propuesta: 'contado',
        observaciones: '',
        items: [],
    });

    const availableSites = selectedClient?.sites ?? [];
    const availableVehicles = selectedClient?.vehicles ?? [];

    const handleClientSelect = (client: Client) => {
        setSelectedClient(client);
        setData((prev) => ({
            ...prev,
            client_id: client.id,
            client_site_id: '',
            vehicle_id: '',
        }));
    };

    const addItem = () => {
        if (catalogItems.length === 0) return;
        const defaultItem = catalogItems[0];
        setData('items', [
            ...data.items,
            {
                catalog_item_id: defaultItem.id,
                cantidad: 1,
                precio_unitario: parseFloat(defaultItem.precio) || 0,
                descuento: 0,
            },
        ]);
    };

    const removeItem = (index: number) => {
        const next = [...data.items];
        next.splice(index, 1);
        setData('items', next);
    };

    const updateItem = (index: number, field: keyof FormQuoteItem, value: number) => {
        const next = [...data.items];
        next[index] = { ...next[index], [field]: value };
        if (field === 'catalog_item_id') {
            const found = catalogItems.find((ci) => ci.id === value);
            if (found) {
                next[index].precio_unitario = parseFloat(found.precio) || 0;
            }
        }
        setData('items', next);
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

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('quotes.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva cotización" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center gap-4">
                    <Button asChild variant="outline" size="icon">
                        <Link href={route('quotes.index')}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <HeadingSmall title="Nueva Cotización" description="Completa el formulario para registrar una cotizacion oficial." />
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Información General</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div className="space-y-2">
                                <Label htmlFor="client_id">Cliente *</Label>
                                <ClientSearchCombobox
                                    id="client_id"
                                    value={data.client_id}
                                    onSelect={handleClientSelect}
                                    placeholder="Buscar cliente..."
                                />
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

                            <div className="space-y-2">
                                <Label htmlFor="fecha">Fecha de Emisión *</Label>
                                <Input
                                    id="fecha"
                                    type="date"
                                    value={data.fecha}
                                    onChange={(e) => setData('fecha', e.target.value)}
                                />
                                <InputError message={errors.fecha} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="vigencia">Vigencia hasta *</Label>
                                <Input
                                    id="vigencia"
                                    type="date"
                                    value={data.vigencia}
                                    onChange={(e) => setData('vigencia', e.target.value)}
                                />
                                <InputError message={errors.vigencia} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="condicion_propuesta">Condición de Pago Propuesta *</Label>
                                <Select
                                    value={data.condicion_propuesta}
                                    onValueChange={(val: 'contado' | 'credito') => setData('condicion_propuesta', val)}
                                >
                                    <SelectTrigger id="condicion_propuesta">
                                        <SelectValue placeholder="Condición" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="contado">Contado</SelectItem>
                                        <SelectItem value="credito">Crédito</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.condicion_propuesta} />
                            </div>

                            <div className="sm:col-span-2 lg:col-span-3 space-y-2">
                                <Label htmlFor="observaciones">Observaciones</Label>
                                <Textarea
                                    id="observaciones"
                                    value={data.observaciones}
                                    onChange={(e) => setData('observaciones', e.target.value)}
                                    placeholder="Notas adicionales o términos específicos"
                                    rows={2}
                                />
                                <InputError message={errors.observaciones} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Ítems de la Cotización</CardTitle>
                            <Button type="button" onClick={addItem} variant="outline" size="sm">
                                <Plus className="size-4 mr-1" /> Agregar Ítem
                            </Button>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <InputError message={errors.items} />

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
                                                        <Input
                                                            type="number"
                                                            min="0.01"
                                                            step="0.01"
                                                            value={item.cantidad}
                                                            onChange={(e) => updateItem(index, 'cantidad', parseFloat(e.target.value) || 0)}
                                                        />
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
                                                    <TableCell className="text-right font-medium">
                                                        S/ {itemSubtotal.toFixed(2)}
                                                    </TableCell>
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

                    <div className="flex justify-end gap-3">
                        <Button asChild variant="outline">
                            <Link href={route('quotes.index')}>Cancelar</Link>
                        </Button>
                        <Button type="submit" disabled={processing || data.items.length === 0}>
                            Guardar Cotización
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
