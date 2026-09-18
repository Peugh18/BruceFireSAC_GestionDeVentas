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
import { type BreadcrumbItem } from '@/types';
import { type ModalidadTraslado, type MotivoTraslado } from '@/types/shipping';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Plus, Trash2 } from 'lucide-react';
import { FormEventHandler, useMemo, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Guías de Remisión', href: route('shipping.index') },
    { title: 'Nueva guía', href: route('shipping.create') },
];

interface ClientOption {
    id: number;
    codigo: string;
    razon_social: string;
    tipo_documento: string;
    numero_documento: string;
}

interface SaleItemOption {
    id: number;
    catalog_item: { nombre: string; unidad: string } | null;
}

interface SaleOption {
    id: number;
    numero: string;
    client_id: number;
    items: SaleItemOption[];
}

interface FormItem {
    sale_item_id: number | null;
    descripcion: string;
    cantidad: number;
    unidad: string;
    peso: string;
}

interface ShippingFormState {
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    [key: string]: any;
    sale_id: number | '';
    motivo_traslado: MotivoTraslado;
    fecha_inicio: string;
    origen: string;
    destino: string;
    destinatario_client_id: number | '';
    destinatario_nombre: string;
    destinatario_documento: string;
    peso_total: string;
    modalidad: ModalidadTraslado;
    transportista_razon_social: string;
    transportista_ruc: string;
    vehiculo_placa: string;
    conductor_nombre: string;
    conductor_licencia: string;
    observaciones: string;
    items: FormItem[];
}

export default function ShippingCreate({
    clients,
    sales,
    motivoLabels,
    modalidadLabels,
}: {
    clients: ClientOption[];
    sales: SaleOption[];
    motivoLabels: Record<string, string>;
    modalidadLabels: Record<string, string>;
}) {
    const tomorrow = new Date(Date.now() + 24 * 60 * 60 * 1000).toISOString().slice(0, 10);
    const [useClientDestinatario, setUseClientDestinatario] = useState(true);

    const { data, setData, post, processing, errors } = useForm<ShippingFormState>({
        sale_id: '',
        motivo_traslado: 'traslado_entre_establecimientos',
        fecha_inicio: tomorrow,
        origen: '',
        destino: '',
        destinatario_client_id: '',
        destinatario_nombre: '',
        destinatario_documento: '',
        peso_total: '',
        modalidad: 'transporte_privado',
        transportista_razon_social: '',
        transportista_ruc: '',
        vehiculo_placa: '',
        conductor_nombre: '',
        conductor_licencia: '',
        observaciones: '',
        items: [{ sale_item_id: null, descripcion: '', cantidad: 1, unidad: 'NIU', peso: '' }],
    });

    const selectedSale = useMemo(() => sales.find((s) => s.id === data.sale_id), [sales, data.sale_id]);

    const applySaleItems = (sale: SaleOption | undefined) => {
        if (!sale || sale.items.length === 0) return;

        setData(
            'items',
            sale.items.map((item) => ({
                sale_item_id: item.id,
                descripcion: item.catalog_item?.nombre ?? `Ítem ${item.id}`,
                cantidad: 1,
                unidad: item.catalog_item?.unidad ?? 'NIU',
                peso: '',
            })),
        );
    };

    const addItem = () => {
        setData('items', [...data.items, { sale_item_id: null, descripcion: '', cantidad: 1, unidad: 'NIU', peso: '' }]);
    };

    const removeItem = (index: number) => {
        const next = [...data.items];
        next.splice(index, 1);
        setData('items', next);
    };

    const updateItem = (index: number, field: keyof FormItem, value: string | number | null) => {
        const next = [...data.items];
        next[index] = { ...next[index], [field]: value } as FormItem;
        setData('items', next);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('shipping.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nueva Guía de Remisión" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center gap-4">
                    <Button asChild variant="outline" size="icon">
                        <Link href={route('shipping.index')}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <HeadingSmall title="Nueva Guía de Remisión" description="Registra el traslado de bienes (GRE) y envíalo a SUNAT." />
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <Card>
                        <CardHeader>
                            <CardTitle>Datos del traslado</CardTitle>
                        </CardHeader>
                        <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div className="space-y-2">
                                <Label htmlFor="sale_id">Venta relacionada (opcional)</Label>
                                <Select
                                    value={data.sale_id ? String(data.sale_id) : 'ninguna'}
                                    onValueChange={(val) => {
                                        const saleId = val === 'ninguna' ? '' : Number(val);
                                        setData('sale_id', saleId);
                                        applySaleItems(sales.find((s) => s.id === saleId));
                                    }}
                                >
                                    <SelectTrigger id="sale_id">
                                        <SelectValue placeholder="Sin venta relacionada" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="ninguna">Sin venta relacionada</SelectItem>
                                        {sales.map((sale) => (
                                            <SelectItem key={sale.id} value={String(sale.id)}>
                                                {sale.numero}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.sale_id} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="motivo_traslado">Motivo del traslado *</Label>
                                <Select value={data.motivo_traslado} onValueChange={(val: MotivoTraslado) => setData('motivo_traslado', val)}>
                                    <SelectTrigger id="motivo_traslado">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {Object.entries(motivoLabels).map(([value, label]) => (
                                            <SelectItem key={value} value={value}>
                                                {label}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.motivo_traslado} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="fecha_inicio">Fecha de inicio *</Label>
                                <Input
                                    id="fecha_inicio"
                                    type="date"
                                    value={data.fecha_inicio}
                                    onChange={(e) => setData('fecha_inicio', e.target.value)}
                                />
                                <InputError message={errors.fecha_inicio} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="origen">Origen *</Label>
                                <Input id="origen" value={data.origen} onChange={(e) => setData('origen', e.target.value)} placeholder="Dirección de origen" />
                                <InputError message={errors.origen} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="destino">Destino *</Label>
                                <Input id="destino" value={data.destino} onChange={(e) => setData('destino', e.target.value)} placeholder="Dirección de destino" />
                                <InputError message={errors.destino} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="peso_total">Peso total (kg) *</Label>
                                <Input
                                    id="peso_total"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    value={data.peso_total}
                                    onChange={(e) => setData('peso_total', e.target.value)}
                                />
                                <InputError message={errors.peso_total} />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Destinatario</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    variant={useClientDestinatario ? 'default' : 'outline'}
                                    onClick={() => {
                                        setUseClientDestinatario(true);
                                        setData((prev) => ({ ...prev, destinatario_nombre: '', destinatario_documento: '' }));
                                    }}
                                >
                                    Cliente registrado
                                </Button>
                                <Button
                                    type="button"
                                    size="sm"
                                    variant={!useClientDestinatario ? 'default' : 'outline'}
                                    onClick={() => {
                                        setUseClientDestinatario(false);
                                        setData('destinatario_client_id', '');
                                    }}
                                >
                                    Texto libre
                                </Button>
                            </div>

                            {useClientDestinatario ? (
                                <div className="space-y-2">
                                    <Label htmlFor="destinatario_client_id">Cliente *</Label>
                                    <Select
                                        value={data.destinatario_client_id ? String(data.destinatario_client_id) : ''}
                                        onValueChange={(val) => setData('destinatario_client_id', Number(val))}
                                    >
                                        <SelectTrigger id="destinatario_client_id">
                                            <SelectValue placeholder="Selecciona un cliente" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {clients.map((client) => (
                                                <SelectItem key={client.id} value={String(client.id)}>
                                                    {client.razon_social} ({client.numero_documento})
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.destinatario_client_id} />
                                </div>
                            ) : (
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="destinatario_nombre">Nombre / Razón social *</Label>
                                        <Input
                                            id="destinatario_nombre"
                                            value={data.destinatario_nombre}
                                            onChange={(e) => setData('destinatario_nombre', e.target.value)}
                                        />
                                        <InputError message={errors.destinatario_nombre} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="destinatario_documento">Documento (opcional)</Label>
                                        <Input
                                            id="destinatario_documento"
                                            value={data.destinatario_documento}
                                            onChange={(e) => setData('destinatario_documento', e.target.value)}
                                        />
                                        <InputError message={errors.destinatario_documento} />
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Modalidad de transporte</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="flex gap-2">
                                {(Object.entries(modalidadLabels) as [ModalidadTraslado, string][]).map(([value, label]) => (
                                    <Button
                                        key={value}
                                        type="button"
                                        size="sm"
                                        variant={data.modalidad === value ? 'default' : 'outline'}
                                        onClick={() => setData('modalidad', value)}
                                    >
                                        {label}
                                    </Button>
                                ))}
                            </div>
                            <InputError message={errors.modalidad} />

                            {data.modalidad === 'transporte_publico' ? (
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="transportista_razon_social">Razón social del transportista *</Label>
                                        <Input
                                            id="transportista_razon_social"
                                            value={data.transportista_razon_social}
                                            onChange={(e) => setData('transportista_razon_social', e.target.value)}
                                        />
                                        <InputError message={errors.transportista_razon_social} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="transportista_ruc">RUC del transportista *</Label>
                                        <Input
                                            id="transportista_ruc"
                                            value={data.transportista_ruc}
                                            onChange={(e) => setData('transportista_ruc', e.target.value)}
                                            maxLength={11}
                                        />
                                        <InputError message={errors.transportista_ruc} />
                                    </div>
                                </div>
                            ) : (
                                <div className="grid gap-4 sm:grid-cols-3">
                                    <div className="space-y-2">
                                        <Label htmlFor="vehiculo_placa">Placa del vehículo *</Label>
                                        <Input
                                            id="vehiculo_placa"
                                            value={data.vehiculo_placa}
                                            onChange={(e) => setData('vehiculo_placa', e.target.value)}
                                        />
                                        <InputError message={errors.vehiculo_placa} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="conductor_nombre">Nombre del conductor *</Label>
                                        <Input
                                            id="conductor_nombre"
                                            value={data.conductor_nombre}
                                            onChange={(e) => setData('conductor_nombre', e.target.value)}
                                        />
                                        <InputError message={errors.conductor_nombre} />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="conductor_licencia">Licencia de conducir *</Label>
                                        <Input
                                            id="conductor_licencia"
                                            value={data.conductor_licencia}
                                            onChange={(e) => setData('conductor_licencia', e.target.value)}
                                        />
                                        <InputError message={errors.conductor_licencia} />
                                    </div>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between">
                            <CardTitle>Bienes a trasladar</CardTitle>
                            <Button type="button" onClick={addItem} variant="outline" size="sm">
                                <Plus className="size-4" /> Agregar bien
                            </Button>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <InputError message={errors.items} />
                            {selectedSale && (
                                <p className="text-xs text-muted-foreground">
                                    Bienes precargados desde la venta {selectedSale.numero}. Puedes editarlos o agregar más.
                                </p>
                            )}

                            <div className="rounded-md border">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Descripción</TableHead>
                                            <TableHead className="w-[110px]">Cantidad</TableHead>
                                            <TableHead className="w-[100px]">Unidad</TableHead>
                                            <TableHead className="w-[120px]">Peso (kg)</TableHead>
                                            <TableHead className="w-[60px]"></TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {data.items.map((item, index) => (
                                            <TableRow key={index}>
                                                <TableCell>
                                                    <Input
                                                        value={item.descripcion}
                                                        onChange={(e) => updateItem(index, 'descripcion', e.target.value)}
                                                        placeholder="Descripción del bien"
                                                    />
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
                                                    <Input value={item.unidad} onChange={(e) => updateItem(index, 'unidad', e.target.value)} />
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        type="number"
                                                        min="0"
                                                        step="0.01"
                                                        value={item.peso}
                                                        onChange={(e) => updateItem(index, 'peso', e.target.value)}
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <Button
                                                        type="button"
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => removeItem(index)}
                                                        className="text-destructive hover:text-destructive"
                                                        disabled={data.items.length === 1}
                                                    >
                                                        <Trash2 className="size-4" />
                                                    </Button>
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle>Observaciones</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Textarea
                                value={data.observaciones}
                                onChange={(e) => setData('observaciones', e.target.value)}
                                placeholder="Notas adicionales sobre el traslado (opcional)"
                                rows={2}
                            />
                            <InputError message={errors.observaciones} />
                        </CardContent>
                    </Card>

                    <div className="flex justify-end gap-3">
                        <Button asChild variant="outline">
                            <Link href={route('shipping.index')}>Cancelar</Link>
                        </Button>
                        <Button type="submit" disabled={processing}>
                            {processing ? 'Generando...' : 'Generar guía y enviar a SUNAT'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
