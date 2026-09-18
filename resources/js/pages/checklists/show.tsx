import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type CatalogItem } from '@/types/catalog';
import { type ChecklistItemData, type CondicionComponente, type ServiceOrderChecklistData } from '@/types/checklist';
import { type Equipment } from '@/types/equipment';
import { type ServiceOrder } from '@/types/service-order';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Check, AlertTriangle, Minus, Save, Wrench } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

interface ChecklistFormState {
    [key: string]: any;
    estado: 'borrador' | 'completado';
    items: ChecklistItemData[];
}

export default function ChecklistShow({
    serviceOrder,
    equipment,
    checklist,
    componentLabels,
    spareParts,
}: {
    serviceOrder: ServiceOrder;
    equipment: Equipment;
    checklist: ServiceOrderChecklistData;
    componentLabels: Record<string, string>;
    spareParts: CatalogItem[];
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Órdenes de Servicio', href: route('service-orders.index') },
        { title: serviceOrder.codigo, href: route('service-orders.show', serviceOrder.id) },
        { title: `Checklist ${equipment.codigo}`, href: route('checklists.show', [serviceOrder.id, equipment.id]) },
    ];

    const initialItems: ChecklistItemData[] = (checklist.items ?? []).map((item) => ({
        id: item.id,
        componente: item.componente,
        condicion: item.condicion as CondicionComponente,
        nota: item.nota ?? '',
        accion_recomendada: item.accion_recomendada ?? '',
        repuesto_sugerido: item.repuesto_sugerido ?? '',
        catalog_item_id: item.catalog_item_id ?? null,
        requiere_autorizacion: true,
    }));

    const { data, setData, post, processing, errors } = useForm<ChecklistFormState>({
        estado: checklist.estado ?? 'completado',
        items: initialItems,
    });

    const setCondition = (index: number, condicion: CondicionComponente) => {
        const next = [...data.items];
        next[index] = { ...next[index], condicion };
        if (condicion === 'observado' && !next[index].accion_recomendada) {
            next[index].accion_recomendada = 'Revisar / Reemplazar componente';
        }
        setData('items', next);
    };

    const updateItem = (index: number, field: keyof ChecklistItemData, value: any) => {
        const next = [...data.items];
        next[index] = { ...next[index], [field]: value };
        setData('items', next);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('checklists.store', [serviceOrder.id, equipment.id]));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Checklist ${equipment.codigo}`} />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6 max-w-4xl mx-auto w-full">
                {/* Mobile Top Navigation */}
                <div className="flex items-center gap-3">
                    <Button asChild variant="outline" size="icon" className="shrink-0">
                        <Link href={route('service-orders.show', serviceOrder.id)}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <div className="min-w-0">
                        <h1 className="text-xl font-bold tracking-tight truncate">Checklist Técnico</h1>
                        <p className="text-xs text-muted-foreground truncate">
                            {serviceOrder.codigo} · {equipment.codigo}
                        </p>
                    </div>
                </div>

                {/* Equipment Reused Master Data Summary */}
                <Card className="border-primary/20 bg-muted/20">
                    <CardHeader className="pb-3">
                        <CardTitle className="text-sm font-semibold flex items-center gap-2">
                            <Wrench className="size-4 text-primary" /> Ficha Técnica del Equipo
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
                        <div>
                            <span className="text-muted-foreground block">Código BRUCE FIRE</span>
                            <span className="font-semibold text-foreground text-sm">{equipment.codigo}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block">Tipo de Equipo</span>
                            <span className="font-medium text-foreground">{equipment.tipo_equipo}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block">Agente / Capacidad</span>
                            <span className="font-medium text-foreground">{equipment.agente ?? '-'} {equipment.capacidad ? `(${equipment.capacidad})` : ''}</span>
                        </div>
                        <div>
                            <span className="text-muted-foreground block">Serie Fabricante</span>
                            <span className="font-medium text-foreground">{equipment.serie_fabricante ?? 'No legible / Sin serie'}</span>
                        </div>
                    </CardContent>
                </Card>

                <form onSubmit={submit} className="space-y-4">
                    <div className="space-y-3">
                        {data.items.map((item, index) => {
                            const label = componentLabels[item.componente] ?? item.componente;
                            const isObservado = item.condicion === 'observado';

                            return (
                                <Card key={index} className={`transition-all ${isObservado ? 'border-warning/50 bg-warning/5' : ''}`}>
                                    <CardContent className="p-4 space-y-3">
                                        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                            <span className="font-semibold text-sm text-foreground">
                                                {index + 1}. {label}
                                            </span>

                                            {/* Large Mobile Touchable Buttons */}
                                            <div className="grid grid-cols-3 gap-1.5 w-full sm:w-auto">
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant={item.condicion === 'conforme' ? 'default' : 'outline'}
                                                    className={`h-10 text-xs gap-1 ${item.condicion === 'conforme' ? 'bg-emerald-600 hover:bg-emerald-700 text-white' : ''}`}
                                                    onClick={() => setCondition(index, 'conforme')}
                                                >
                                                    <Check className="size-3.5" /> Conforme
                                                </Button>

                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant={item.condicion === 'observado' ? 'default' : 'outline'}
                                                    className={`h-10 text-xs gap-1 ${item.condicion === 'observado' ? 'bg-warning hover:bg-warning/90 text-warning-foreground' : ''}`}
                                                    onClick={() => setCondition(index, 'observado')}
                                                >
                                                    <AlertTriangle className="size-3.5" /> Observado
                                                </Button>

                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant={item.condicion === 'no_aplica' ? 'secondary' : 'outline'}
                                                    className="h-10 text-xs gap-1"
                                                    onClick={() => setCondition(index, 'no_aplica')}
                                                >
                                                    <Minus className="size-3.5" /> N/A
                                                </Button>
                                            </div>
                                        </div>

                                        {/* Dynamic Expansion for Observed Condition */}
                                        {isObservado && (
                                            <div className="pt-3 border-t space-y-3 text-xs">
                                                <div className="space-y-1">
                                                    <Label className="text-xs text-warning font-medium">
                                                        Detalle de la Observación / Nota
                                                    </Label>
                                                    <Textarea
                                                        rows={2}
                                                        placeholder="Describe la deficiencia detectada en este componente..."
                                                        value={item.nota ?? ''}
                                                        onChange={(e) => updateItem(index, 'nota', e.target.value)}
                                                        className="text-xs"
                                                    />
                                                </div>

                                                <div className="grid gap-3 sm:grid-cols-2">
                                                    <div className="space-y-1">
                                                        <Label className="text-xs font-medium">Acción Recomendada</Label>
                                                        <Input
                                                            placeholder="Ej. Reemplazar manguera / Limpiar válvula"
                                                            value={item.accion_recomendada ?? ''}
                                                            onChange={(e) => updateItem(index, 'accion_recomendada', e.target.value)}
                                                            className="text-xs"
                                                        />
                                                    </div>

                                                    <div className="space-y-1">
                                                        <Label className="text-xs font-medium">Repuesto del Catálogo (Opcional)</Label>
                                                        <Select
                                                            value={item.catalog_item_id ? String(item.catalog_item_id) : ''}
                                                            onValueChange={(val) => updateItem(index, 'catalog_item_id', val ? Number(val) : null)}
                                                        >
                                                            <SelectTrigger className="text-xs">
                                                                <SelectValue placeholder="Seleccionar repuesto" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {spareParts.map((sp) => (
                                                                    <SelectItem key={sp.id} value={String(sp.id)}>
                                                                        [{sp.codigo}] {sp.nombre}
                                                                    </SelectItem>
                                                                ))}
                                                            </SelectContent>
                                                        </Select>
                                                    </div>
                                                </div>

                                                <div className="flex items-center space-x-2 pt-1">
                                                    <Checkbox
                                                        id={`auth-${index}`}
                                                        checked={item.requiere_autorizacion ?? true}
                                                        onCheckedChange={(checked) => updateItem(index, 'requiere_autorizacion', Boolean(checked))}
                                                    />
                                                    <label htmlFor={`auth-${index}`} className="text-xs text-muted-foreground font-medium cursor-pointer">
                                                        Requiere autorización del vendedor / cliente para repuesto o servicio adicional
                                                    </label>
                                                </div>
                                            </div>
                                        )}
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>

                    {/* Fixed Mobile Bottom Action */}
                    <div className="sticky bottom-4 pt-2">
                        <Button type="submit" size="lg" disabled={processing} className="w-full shadow-lg text-base h-12 font-semibold">
                            <Save className="size-5 mr-2" /> Guardar Checklist Técnico
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
