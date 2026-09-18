import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { catalogTypes, type CatalogItem } from '@/types/catalog';
import { Head, Link, useForm } from '@inertiajs/react';
import { PackageSearch, Plus, Search } from 'lucide-react';
import { type FormEventHandler } from 'react';

interface CatalogProps {
    items: {
        data: CatalogItem[];
        current_page: number;
        last_page: number;
        total: number;
        prev_page_url: string | null;
        next_page_url: string | null;
    };
    filters: { search: string; tipo: string };
    can: { create: boolean; update: boolean };
    status?: string;
}

const price = new Intl.NumberFormat('es-PE', { style: 'currency', currency: 'PEN' });

export default function Index({ items, filters, can, status }: CatalogProps) {
    const { data, setData, get, processing, errors } = useForm({ search: filters.search, tipo: filters.tipo });
    const submit: FormEventHandler = (event) => {
        event.preventDefault();
        get(route('catalog.index'), { preserveScroll: true, preserveState: false });
    };

    return (
        <AppLayout breadcrumbs={[{ title: 'Catálogo', href: route('catalog.index') }]}>
            <Head title="Catálogo" />
            <div className="space-y-6 p-4 md:p-6">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <HeadingSmall title="Catálogo" description="Administra productos, servicios y repuestos o componentes." />
                    {can.create && (
                        <Button asChild>
                            <Link href={route('catalog.create')}>
                                <Plus className="size-4" />
                                Nuevo registro
                            </Link>
                        </Button>
                    )}
                </div>
                {status && (
                    <div role="status" className="bg-muted/50 rounded-lg border px-4 py-3 text-sm">
                        {status}
                    </div>
                )}
                <form onSubmit={submit} className="flex flex-col gap-4 sm:flex-row sm:items-end">
                    <div className="grid flex-1 gap-2">
                        <Label htmlFor="search">Buscar</Label>
                        <div className="relative">
                            <Search className="text-muted-foreground absolute top-2.5 left-3 size-4" aria-hidden="true" />
                            <Input
                                id="search"
                                className="pl-9"
                                placeholder="Nombre, código o categoría"
                                maxLength={255}
                                value={data.search}
                                onChange={(event) => setData('search', event.target.value)}
                            />
                        </div>
                        <InputError message={errors.search} />
                    </div>
                    <div className="grid gap-2 sm:w-60">
                        <Label htmlFor="filter-tipo">Tipo</Label>
                        <Select value={data.tipo || 'todos'} onValueChange={(value) => setData('tipo', value === 'todos' ? '' : value)}>
                            <SelectTrigger id="filter-tipo">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todos">Todos los tipos</SelectItem>
                                {Object.entries(catalogTypes).map(([value, label]) => (
                                    <SelectItem key={value} value={value}>
                                        {label}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.tipo} />
                    </div>
                    <Button type="submit" variant="secondary" disabled={processing}>
                        {processing ? 'Buscando...' : 'Filtrar'}
                    </Button>
                    {(filters.search || filters.tipo) && (
                        <Button variant="ghost" asChild>
                            <Link href={route('catalog.index')}>Limpiar</Link>
                        </Button>
                    )}
                </form>
                {items.data.length === 0 ? (
                    <div className="flex flex-col items-center gap-3 rounded-xl border border-dashed px-6 py-16 text-center">
                        <PackageSearch className="text-muted-foreground size-8" aria-hidden="true" />
                        <h2 className="font-medium">No hay registros para mostrar</h2>
                        <p className="text-muted-foreground text-sm">
                            {filters.search || filters.tipo
                                ? 'Prueba otra búsqueda o cambia el filtro de tipo.'
                                : 'Agrega el primer producto, servicio o repuesto al catálogo.'}
                        </p>
                    </div>
                ) : (
                    <>
                        <div className="hidden overflow-x-auto rounded-xl border md:block">
                            <table className="w-full text-left text-sm">
                                <caption className="sr-only">Registros del catálogo</caption>
                                <thead className="bg-muted/50 text-muted-foreground border-b">
                                    <tr>
                                        {['Registro', 'Tipo', 'Unidad', 'Precio', 'IGV', 'Estado', 'Acciones'].map((label) => (
                                            <th key={label} scope="col" className="px-4 py-3 font-medium">
                                                {label}
                                            </th>
                                        ))}
                                    </tr>
                                </thead>
                                <tbody className="divide-y">
                                    {items.data.map((item) => (
                                        <tr key={item.id} className="hover:bg-muted/30">
                                            <td className="max-w-sm px-4 py-4">
                                                <div className="font-medium break-words">{item.nombre}</div>
                                                <div className="text-muted-foreground mt-1 text-xs">{item.categoria}</div>
                                                <div className="text-muted-foreground mt-1 font-mono text-xs">{item.codigo}</div>
                                            </td>
                                            <td className="px-4 py-4">{catalogTypes[item.tipo]}</td>
                                            <td className="px-4 py-4">{item.unidad}</td>
                                            <td className="px-4 py-4 whitespace-nowrap tabular-nums">{price.format(Number(item.precio))}</td>
                                            <td className="px-4 py-4">{item.aplica_igv ? 'Aplica' : 'No aplica'}</td>
                                            <td className="px-4 py-4">
                                                <Badge variant={item.activo ? 'secondary' : 'outline'}>{item.activo ? 'Activo' : 'Inactivo'}</Badge>
                                            </td>
                                            <td className="px-4 py-4">
                                                {can.update && (
                                                    <Button variant="ghost" size="sm" asChild>
                                                        <Link href={route('catalog.edit', item.id)} aria-label={`Editar ${item.nombre}`}>
                                                            Editar
                                                        </Link>
                                                    </Button>
                                                )}
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                        <div className="grid gap-3 md:hidden">
                            {items.data.map((item) => (
                                <article key={item.id} className="space-y-3 rounded-xl border p-4">
                                    <div className="flex items-start justify-between gap-3">
                                        <h2 className="min-w-0 font-medium break-words">{item.nombre}</h2>
                                        <Badge variant={item.activo ? 'secondary' : 'outline'}>{item.activo ? 'Activo' : 'Inactivo'}</Badge>
                                    </div>
                                    <p className="text-muted-foreground text-sm">
                                        {catalogTypes[item.tipo]} · {item.categoria}
                                    </p>
                                    <p className="text-muted-foreground font-mono text-xs break-all">{item.codigo}</p>
                                    <p className="text-sm">
                                        {price.format(Number(item.precio))} / {item.unidad} · {item.aplica_igv ? 'Aplica IGV' : 'No aplica IGV'}
                                    </p>
                                    {can.update && (
                                        <Button variant="outline" size="sm" asChild>
                                            <Link href={route('catalog.edit', item.id)}>Editar</Link>
                                        </Button>
                                    )}
                                </article>
                            ))}
                        </div>
                    </>
                )}
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <p className="text-muted-foreground text-sm">
                        {items.total} registros · Página {items.current_page} de {items.last_page}
                    </p>
                    <nav aria-label="Paginación del catálogo" className="flex gap-2">
                        {items.prev_page_url ? (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={items.prev_page_url}>Anterior</Link>
                            </Button>
                        ) : (
                            <Button variant="outline" size="sm" disabled>
                                Anterior
                            </Button>
                        )}
                        {items.next_page_url ? (
                            <Button variant="outline" size="sm" asChild>
                                <Link href={items.next_page_url}>Siguiente</Link>
                            </Button>
                        ) : (
                            <Button variant="outline" size="sm" disabled>
                                Siguiente
                            </Button>
                        )}
                    </nav>
                </div>
            </div>
        </AppLayout>
    );
}
