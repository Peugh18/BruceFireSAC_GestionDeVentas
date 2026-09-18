import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated } from '@/types';
import { type ElectronicDocumentEstado } from '@/types/electronic-document';
import { Head, Link, router } from '@inertiajs/react';
import { RefreshCw, Search } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Envio de comprobantes', href: route('billing.index') }];

type BillingDocument = {
    id: number;
    sale_id: number;
    tipo: string;
    serie: string;
    correlativo: string;
    estado: ElectronicDocumentEstado;
    respuesta_sunat: string | null;
    error: string | null;
    intentos: number;
    fecha_envio: string | null;
    created_at: string | null;
    xml_path: string | null;
    cdr_path: string | null;
    has_xml: boolean;
    has_cdr: boolean;
    sale: {
        id: number | null;
        numero: string | null;
        fecha: string | null;
        client: {
            razon_social: string | null;
            numero_documento: string | null;
        };
    };
};

const ESTADO_BADGE_VARIANT: Record<ElectronicDocumentEstado, 'default' | 'secondary' | 'destructive'> = {
    pendiente: 'secondary',
    aceptado: 'default',
    rechazado: 'destructive',
    error: 'destructive',
};

export default function BillingIndex({
    documents,
    filters,
    estadoLabels,
    hasDownloadRoutes,
}: {
    documents: Paginated<BillingDocument>;
    filters: { desde: string; hasta: string; estado: string };
    estadoLabels: Record<ElectronicDocumentEstado, string>;
    hasDownloadRoutes: boolean;
}) {
    const [desde, setDesde] = useState(filters.desde ?? '');
    const [hasta, setHasta] = useState(filters.hasta ?? '');
    const [estado, setEstado] = useState(filters.estado || 'todos');

    const applyFilters = (nextEstado = estado) => {
        router.get(
            route('billing.index'),
            {
                desde: desde || undefined,
                hasta: hasta || undefined,
                estado: nextEstado === 'todos' ? undefined : nextEstado,
            },
            { preserveState: true, replace: true },
        );
    };

    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        applyFilters();
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Envio de comprobantes" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <HeadingSmall title="Envio de comprobantes" description="Seguimiento de comprobantes enviados a SUNAT." />

                <form onSubmit={submit} className="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div className="space-y-2">
                        <label htmlFor="desde" className="text-sm font-medium">
                            Desde
                        </label>
                        <Input id="desde" type="date" value={desde} onChange={(event) => setDesde(event.target.value)} />
                    </div>
                    <div className="space-y-2">
                        <label htmlFor="hasta" className="text-sm font-medium">
                            Hasta
                        </label>
                        <Input id="hasta" type="date" value={hasta} onChange={(event) => setHasta(event.target.value)} />
                    </div>
                    <div className="space-y-2">
                        <label htmlFor="estado" className="text-sm font-medium">
                            Estado SUNAT
                        </label>
                        <Select
                            value={estado}
                            onValueChange={(value) => {
                                setEstado(value);
                                applyFilters(value);
                            }}
                        >
                            <SelectTrigger id="estado" className="sm:w-52">
                                <SelectValue placeholder="Estado SUNAT" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todos">Todos los estados</SelectItem>
                                {Object.entries(estadoLabels).map(([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                    <Button type="submit" variant="secondary">
                        <Search className="mr-1 size-4" />
                        Filtrar
                    </Button>
                </form>

                <div className="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Comprobante</TableHead>
                                <TableHead>Venta</TableHead>
                                <TableHead>Cliente</TableHead>
                                <TableHead>Fecha envio</TableHead>
                                <TableHead>Estado SUNAT</TableHead>
                                <TableHead>Respuesta SUNAT</TableHead>
                                <TableHead>Archivos</TableHead>
                                <TableHead className="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {documents.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={8} className="py-8 text-center text-muted-foreground">
                                        No se encontraron comprobantes.
                                    </TableCell>
                                </TableRow>
                            )}

                            {documents.data.map((document) => (
                                <TableRow key={document.id}>
                                    <TableCell className="font-medium">
                                        {document.serie}-{document.correlativo}
                                        <div className="text-xs text-muted-foreground capitalize">{document.tipo}</div>
                                    </TableCell>
                                    <TableCell>
                                        {document.sale.id ? (
                                            <Link href={route('sales.show', document.sale.id)} className="font-medium hover:underline">
                                                {document.sale.numero}
                                            </Link>
                                        ) : (
                                            '-'
                                        )}
                                        <div className="text-xs text-muted-foreground">{document.sale.fecha}</div>
                                    </TableCell>
                                    <TableCell>
                                        <div className="font-medium">{document.sale.client.razon_social ?? '-'}</div>
                                        <div className="text-xs text-muted-foreground">{document.sale.client.numero_documento}</div>
                                    </TableCell>
                                    <TableCell>{document.fecha_envio ?? '-'}</TableCell>
                                    <TableCell>
                                        <Badge variant={ESTADO_BADGE_VARIANT[document.estado]}>{estadoLabels[document.estado]}</Badge>
                                        <div className="text-xs text-muted-foreground">{document.intentos} intento(s)</div>
                                    </TableCell>
                                    <TableCell className="max-w-xs">
                                        <p className="line-clamp-2 text-sm">{document.error ?? document.respuesta_sunat ?? '-'}</p>
                                    </TableCell>
                                    <TableCell>
                                        <div className="flex flex-col gap-1 text-xs">
                                            {document.has_xml ? (
                                                <a href={route('billing.documents.xml', document.id)} className="font-medium hover:underline">
                                                    Descargar XML
                                                </a>
                                            ) : (
                                                <span>XML: No generado</span>
                                            )}
                                            {document.has_cdr ? (
                                                <a href={route('billing.documents.cdr', document.id)} className="font-medium hover:underline">
                                                    Descargar CDR
                                                </a>
                                            ) : (
                                                <span>CDR: No generado</span>
                                            )}
                                            <span>PDF: No generado</span>
                                        </div>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button asChild variant="ghost" size="sm" disabled={document.estado !== 'error'}>
                                            <Link href={route('billing.retry', document.id)} method="post" as="button" preserveScroll>
                                                <RefreshCw className="mr-1 size-4" />
                                                Reintentar
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {documents.last_page > 1 && (
                    <div className="flex flex-wrap items-center justify-center gap-1 pt-2">
                        {documents.links.map((link, index) => (
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
        </AppLayout>
    );
}
