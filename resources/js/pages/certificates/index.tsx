import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated } from '@/types';
import { type CertificateData, type CertificateEstado } from '@/types/certificate';
import { Head, Link, router } from '@inertiajs/react';
import { CheckCircle2, FileBadge, Search } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Certificados', href: route('certificates.index') }];

const ESTADO_BADGE_VARIANT: Record<CertificateEstado, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    vigente: 'default',
    vencido: 'destructive',
    reemplazado: 'secondary',
    anulado: 'outline',
};

export default function CertificatesIndex({
    certificates,
    tipoLabels,
    estadoLabels,
    filters,
    status,
}: {
    certificates: Paginated<CertificateData>;
    tipoLabels: Record<string, string>;
    estadoLabels: Record<string, string>;
    filters: { search: string; estado: string };
    status?: string;
}) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [estado, setEstado] = useState(filters.estado || 'todos');

    const applyFilters = (nextSearch: string, nextEstado: string) => {
        router.get(
            route('certificates.index'),
            {
                search: nextSearch || undefined,
                estado: nextEstado === 'todos' ? undefined : nextEstado,
            },
            { preserveState: true, replace: true },
        );
    };

    const submitFilter: FormEventHandler = (e) => {
        e.preventDefault();
        applyFilters(search, estado);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Certificados" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <HeadingSmall title="Certificados" description="Certificados emitidos con número único, QR de autenticidad y verificación pública." />

                {status && (
                    <div role="status" className="bg-muted/50 text-foreground flex items-center gap-2 rounded-lg border px-4 py-3 text-sm">
                        <CheckCircle2 className="text-emerald-600 size-4" />
                        {status}
                    </div>
                )}

                <form onSubmit={submitFilter} className="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div className="relative flex-1 sm:max-w-xs">
                        <Search className="text-muted-foreground absolute top-2.5 left-3 size-4" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Buscar por número, orden o cliente"
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
                            <SelectValue placeholder="Estado del certificado" />
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

                    <Button type="submit" variant="secondary">
                        Buscar
                    </Button>
                </form>

                <div className="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Número</TableHead>
                                <TableHead>Tipo</TableHead>
                                <TableHead>Orden</TableHead>
                                <TableHead>Cliente</TableHead>
                                <TableHead>Equipos</TableHead>
                                <TableHead>Emisión</TableHead>
                                <TableHead>Estado</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {certificates.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={7} className="text-muted-foreground py-8 text-center">
                                        <FileBadge className="mx-auto mb-2 size-8 opacity-50" />
                                        No se encontraron certificados emitidos.
                                    </TableCell>
                                </TableRow>
                            )}

                            {certificates.data.map((certificate) => (
                                <TableRow key={certificate.id}>
                                    <TableCell className="font-medium">
                                        <Link href={route('certificates.show', certificate.id)} className="hover:underline">
                                            {certificate.numero}
                                        </Link>
                                    </TableCell>
                                    <TableCell>{tipoLabels[certificate.tipo] ?? certificate.tipo}</TableCell>
                                    <TableCell>
                                        {certificate.service_order ? (
                                            <Link href={route('service-orders.show', certificate.service_order.id)} className="hover:underline">
                                                {certificate.service_order.codigo}
                                            </Link>
                                        ) : (
                                            '-'
                                        )}
                                    </TableCell>
                                    <TableCell>{certificate.service_order?.client.razon_social ?? '-'}</TableCell>
                                    <TableCell>{certificate.items?.length ?? 0}</TableCell>
                                    <TableCell>{certificate.fecha_emision}</TableCell>
                                    <TableCell>
                                        <Badge variant={ESTADO_BADGE_VARIANT[certificate.estado]}>{estadoLabels[certificate.estado] ?? certificate.estado}</Badge>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {certificates.last_page > 1 && (
                    <div className="flex flex-wrap items-center justify-center gap-1 pt-2">
                        {certificates.links.map((link, index) => (
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
