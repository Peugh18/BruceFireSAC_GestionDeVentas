import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated, type SharedData } from '@/types';
import { type DeficiencyAuthorizationCanal, type DeficiencyData, type DeficiencyEstado } from '@/types/deficiency';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { AlertTriangle, CheckCircle2, Search, ShieldCheck, Wrench } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Deficiencias Técnicas', href: route('deficiencies.index') }];

interface QuoteOption {
    id: number;
    numero: string;
    client_id: number;
}

const ESTADO_BADGE_VARIANT: Record<DeficiencyEstado, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    detectada: 'outline',
    esperando_autorizacion: 'secondary',
    autorizada: 'default',
    rechazada: 'destructive',
    en_correccion: 'secondary',
    resuelta: 'default',
};

// "autorizada" is reached exclusively through the "Autorizar adicional" dialog below,
// which records who authorized it (client), the channel and the linked quote.
const NEXT_STATUS_OPTIONS: Record<DeficiencyEstado, DeficiencyEstado[]> = {
    detectada: ['esperando_autorizacion', 'rechazada'],
    esperando_autorizacion: ['rechazada'],
    autorizada: ['en_correccion', 'resuelta'],
    rechazada: [],
    en_correccion: ['resuelta'],
    resuelta: [],
};

const CANAL_OPTIONS: DeficiencyAuthorizationCanal[] = ['whatsapp', 'presencial'];

export default function DeficienciesIndex({
    deficiencies,
    statusLabels,
    canalLabels,
    quotes,
    filters,
    status,
}: {
    deficiencies: Paginated<DeficiencyData>;
    statusLabels: Record<string, string>;
    canalLabels: Record<string, string>;
    quotes: QuoteOption[];
    filters: { search: string; estado: string };
    status?: string;
}) {
    const { auth } = usePage<SharedData>().props;
    const canAuthorize = auth.permissions.includes('deficiencies.authorize');
    const canResolve = auth.permissions.includes('deficiencies.resolve');

    const [search, setSearch] = useState(filters.search ?? '');
    const [estado, setEstado] = useState(filters.estado || 'todos');
    const [selectedDeficiency, setSelectedDeficiency] = useState<DeficiencyData | null>(null);
    const [authorizingDeficiency, setAuthorizingDeficiency] = useState<DeficiencyData | null>(null);

    const { data: statusData, setData: setStatusData, patch, processing, errors, reset } = useForm({
        estado: '' as DeficiencyEstado | '',
        resolucion: '',
    });

    const {
        data: authData,
        setData: setAuthData,
        post: postAuth,
        processing: authProcessing,
        errors: authErrors,
        reset: resetAuth,
    } = useForm({
        autorizado_por: '',
        canal: 'whatsapp' as DeficiencyAuthorizationCanal,
        fecha: new Date().toISOString().slice(0, 10),
        observacion: '',
        quote_id: '' as number | '',
    });

    const applyFilters = (nextSearch: string, nextEstado: string) => {
        router.get(
            route('deficiencies.index'),
            {
                search: nextSearch || undefined,
                estado: nextEstado === 'todos' ? undefined : nextEstado,
            },
            { preserveState: true, replace: true }
        );
    };

    const submitFilter: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilters(search, estado);
    };

    const openStatusDialog = (deficiency: DeficiencyData) => {
        const options = NEXT_STATUS_OPTIONS[deficiency.estado];
        if (options.length === 0) return;
        setSelectedDeficiency(deficiency);
        setStatusData({
            estado: options[0],
            resolucion: deficiency.resolucion ?? '',
        });
    };

    const handleStatusSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        if (!selectedDeficiency || !statusData.estado) return;

        patch(route('deficiencies.status', selectedDeficiency.id), {
            onSuccess: () => {
                setSelectedDeficiency(null);
                reset();
            },
        });
    };

    const openAuthorizeDialog = (deficiency: DeficiencyData) => {
        setAuthorizingDeficiency(deficiency);
        resetAuth();
        setAuthData({
            autorizado_por: '',
            canal: 'whatsapp',
            fecha: new Date().toISOString().slice(0, 10),
            observacion: '',
            quote_id: '',
        });
    };

    const handleAuthorizeSubmit: FormEventHandler = (e) => {
        e.preventDefault();
        if (!authorizingDeficiency) return;

        postAuth(route('deficiencies.authorizations.store', authorizingDeficiency.id), {
            onSuccess: () => {
                setAuthorizingDeficiency(null);
                resetAuth();
            },
        });
    };

    const clientQuotes = authorizingDeficiency?.service_order?.client
        ? quotes.filter((quote) => quote.client_id === authorizingDeficiency.service_order!.client.id)
        : [];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Deficiencias Técnicas" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <HeadingSmall title="Deficiencias Técnicas" description="Control de componentes observados, autorizaciones y corrección de deficiencias." />
                </div>

                {status && (
                    <div role="status" className="rounded-lg border bg-muted/50 px-4 py-3 text-sm text-foreground flex items-center gap-2">
                        <CheckCircle2 className="size-4 text-emerald-600" />
                        {status}
                    </div>
                )}

                <form onSubmit={submitFilter} className="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div className="relative flex-1 sm:max-w-xs">
                        <Search className="absolute left-3 top-2.5 size-4 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Buscar por orden, equipo o componente"
                            className="pl-9"
                        />
                    </div>

                    <Select
                        value={estado}
                        onValueChange={(val) => {
                            setEstado(val);
                            applyFilters(search, val);
                        }}
                    >
                        <SelectTrigger className="sm:w-52">
                            <SelectValue placeholder="Estado de deficiencia" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="todos">Todos los estados</SelectItem>
                            <SelectItem value="detectada">Detectada</SelectItem>
                            <SelectItem value="esperando_autorizacion">Esperando Autorización</SelectItem>
                            <SelectItem value="autorizada">Autorizada</SelectItem>
                            <SelectItem value="rechazada">Rechazada</SelectItem>
                            <SelectItem value="en_correccion">En Corrección</SelectItem>
                            <SelectItem value="resuelta">Resuelta</SelectItem>
                        </SelectContent>
                    </Select>

                    <Button type="submit" variant="secondary">
                        Buscar
                    </Button>
                </form>

                <div className="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Orden</TableHead>
                                <TableHead>Equipo</TableHead>
                                <TableHead>Componente</TableHead>
                                <TableHead>Observación / Nota</TableHead>
                                <TableHead>Acción / Repuesto</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead className="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {deficiencies.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={7} className="text-center text-muted-foreground py-8">
                                        <AlertTriangle className="mx-auto size-8 mb-2 opacity-50" />
                                        No se encontraron deficiencias registradas.
                                    </TableCell>
                                </TableRow>
                            )}

                            {deficiencies.data.map((def) => {
                                const nextOptions = NEXT_STATUS_OPTIONS[def.estado];
                                return (
                                    <TableRow key={def.id}>
                                        <TableCell className="font-medium">
                                            {def.service_order ? (
                                                <Link href={route('service-orders.show', def.service_order.id)} className="hover:underline">
                                                    {def.service_order.codigo}
                                                </Link>
                                            ) : (
                                                '-'
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {def.equipment ? (
                                                <div className="font-medium">
                                                    <Link href={route('equipment.show', def.equipment.id)} className="hover:underline">
                                                        {def.equipment.codigo}
                                                    </Link>
                                                    <span className="text-xs text-muted-foreground block">{def.equipment.tipo_equipo}</span>
                                                </div>
                                            ) : (
                                                '-'
                                            )}
                                        </TableCell>
                                        <TableCell className="capitalize font-medium">{def.componente.replace('_', ' ')}</TableCell>
                                        <TableCell className="max-w-xs text-xs whitespace-pre-wrap">{def.nota ?? 'Sin nota'}</TableCell>
                                        <TableCell className="max-w-xs text-xs">
                                            <p className="font-medium">{def.accion_recomendada ?? '-'}</p>
                                            {def.repuesto_sugerido && (
                                                <p className="text-muted-foreground">Repuesto: {def.repuesto_sugerido}</p>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant={ESTADO_BADGE_VARIANT[def.estado]}>{statusLabels[def.estado] ?? def.estado}</Badge>
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex flex-wrap justify-end gap-2">
                                                {canAuthorize && def.estado === 'esperando_autorizacion' && (
                                                    <Button size="sm" onClick={() => openAuthorizeDialog(def)}>
                                                        <ShieldCheck className="size-3.5" /> Autorizar adicional
                                                    </Button>
                                                )}
                                                {nextOptions.length > 0 && (
                                                    <Button size="sm" variant="outline" onClick={() => openStatusDialog(def)}>
                                                        Cambiar Estado
                                                    </Button>
                                                )}
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                );
                            })}
                        </TableBody>
                    </Table>
                </div>

                {deficiencies.last_page > 1 && (
                    <div className="flex flex-wrap items-center justify-center gap-1 pt-2">
                        {deficiencies.links.map((link, index) => (
                            <Button
                                key={index}
                                variant={link.active ? 'default' : 'outline'}
                                size="sm"
                                disabled={!link.url}
                                onClick={() => link.url && router.get(link.url, {}, { preserveState: true })}
                                dangerouslySetInnerHTML={{ __html: link.label }}
                            />
                        ))}
                    </div>
                )}
            </div>

            {/* Change Status Dialog */}
            {selectedDeficiency && (
                <Dialog open={Boolean(selectedDeficiency)} onOpenChange={() => setSelectedDeficiency(null)}>
                    <DialogContent>
                        <form onSubmit={handleStatusSubmit} className="space-y-4">
                            <DialogHeader>
                                <DialogTitle>Actualizar Estado de Deficiencia</DialogTitle>
                                <DialogDescription>
                                    Componente: <span className="font-medium text-foreground capitalize">{selectedDeficiency.componente.replace('_', ' ')}</span> ({selectedDeficiency.equipment?.codigo})
                                </DialogDescription>
                            </DialogHeader>

                            <div className="space-y-2">
                                <Label htmlFor="estado-select">Nuevo Estado</Label>
                                <Select
                                    value={statusData.estado}
                                    onValueChange={(val: DeficiencyEstado) => setStatusData('estado', val)}
                                >
                                    <SelectTrigger id="estado-select">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {NEXT_STATUS_OPTIONS[selectedDeficiency.estado].map((st) => (
                                            <SelectItem key={st} value={st}>
                                                {statusLabels[st] ?? st}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.estado} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="resolucion-text">Detalle / Notas de Resolución</Label>
                                <Textarea
                                    id="resolucion-text"
                                    rows={3}
                                    placeholder="Indica las acciones correctivas aplicadas o motivo de la transición..."
                                    value={statusData.resolucion}
                                    onChange={(e) => setStatusData('resolucion', e.target.value)}
                                />
                                <InputError message={errors.resolucion} />
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setSelectedDeficiency(null)}>
                                    Cancelar
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    Guardar Cambio
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            )}

            {/* Authorize Additional Dialog */}
            {authorizingDeficiency && (
                <Dialog open={Boolean(authorizingDeficiency)} onOpenChange={() => setAuthorizingDeficiency(null)}>
                    <DialogContent>
                        <form onSubmit={handleAuthorizeSubmit} className="space-y-4">
                            <DialogHeader>
                                <DialogTitle>Autorizar Adicional</DialogTitle>
                                <DialogDescription>
                                    Registra la autorización del cliente para el adicional en{' '}
                                    <span className="font-medium text-foreground capitalize">
                                        {authorizingDeficiency.componente.replace('_', ' ')}
                                    </span>{' '}
                                    ({authorizingDeficiency.equipment?.codigo}). El técnico no negocia precios: esta autorización debe
                                    provenir del cliente.
                                </DialogDescription>
                            </DialogHeader>

                            <div className="space-y-2">
                                <Label htmlFor="autorizado-por">Autorizado por (nombre del cliente)</Label>
                                <Input
                                    id="autorizado-por"
                                    value={authData.autorizado_por}
                                    onChange={(e) => setAuthData('autorizado_por', e.target.value)}
                                    placeholder="Nombre de quien autoriza"
                                />
                                <InputError message={authErrors.autorizado_por} />
                            </div>

                            <div className="grid gap-4 sm:grid-cols-2">
                                <div className="space-y-2">
                                    <Label htmlFor="canal-select">Canal</Label>
                                    <Select
                                        value={authData.canal}
                                        onValueChange={(val: DeficiencyAuthorizationCanal) => setAuthData('canal', val)}
                                    >
                                        <SelectTrigger id="canal-select">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {CANAL_OPTIONS.map((canal) => (
                                                <SelectItem key={canal} value={canal}>
                                                    {canalLabels[canal] ?? canal}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={authErrors.canal} />
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="fecha-autorizacion">Fecha</Label>
                                    <Input
                                        id="fecha-autorizacion"
                                        type="date"
                                        value={authData.fecha}
                                        onChange={(e) => setAuthData('fecha', e.target.value)}
                                    />
                                    <InputError message={authErrors.fecha} />
                                </div>
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label htmlFor="quote-select">Cotización del adicional (opcional)</Label>
                                    <a
                                        href={route('quotes.create')}
                                        target="_blank"
                                        rel="noreferrer"
                                        className="text-xs text-primary hover:underline"
                                    >
                                        Nueva cotización
                                    </a>
                                </div>
                                <Select
                                    value={authData.quote_id ? String(authData.quote_id) : 'ninguna'}
                                    onValueChange={(val) => setAuthData('quote_id', val === 'ninguna' ? '' : Number(val))}
                                >
                                    <SelectTrigger id="quote-select">
                                        <SelectValue placeholder="Sin cotización asociada" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="ninguna">Sin cotización asociada</SelectItem>
                                        {clientQuotes.map((quote) => (
                                            <SelectItem key={quote.id} value={String(quote.id)}>
                                                {quote.numero}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={authErrors.quote_id} />
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="observacion-autorizacion">Observación / Evidencia (opcional)</Label>
                                <Textarea
                                    id="observacion-autorizacion"
                                    rows={3}
                                    placeholder="Ej. captura de WhatsApp, referencia de la conversación..."
                                    value={authData.observacion}
                                    onChange={(e) => setAuthData('observacion', e.target.value)}
                                />
                                <InputError message={authErrors.observacion} />
                            </div>

                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setAuthorizingDeficiency(null)}>
                                    Cancelar
                                </Button>
                                <Button type="submit" disabled={authProcessing}>
                                    Registrar Autorización
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
            )}
        </AppLayout>
    );
}
