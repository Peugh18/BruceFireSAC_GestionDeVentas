import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type Paginated } from '@/types';
import { type AdminUserData } from '@/types/administration';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { CheckCircle2, Search, ShieldCheck, UserPlus, Users as UsersIcon } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Administración', href: route('administration.users.index') }];

function SubNav() {
    return (
        <div className="flex gap-2 border-b pb-2">
            <Button asChild variant="secondary" size="sm">
                <Link href={route('administration.users.index')}>
                    <UsersIcon className="size-4" /> Usuarios
                </Link>
            </Button>
            <Button asChild variant="ghost" size="sm">
                <Link href={route('administration.roles.index')}>
                    <ShieldCheck className="size-4" /> Roles
                </Link>
            </Button>
        </div>
    );
}

function CreateUserDialog({ roles, open, onOpenChange }: { roles: string[]; open: boolean; onOpenChange: (open: boolean) => void }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        name: '',
        email: '',
        password: '',
        autoPassword: true as boolean,
        role: roles[0] ?? '',
        activo: true as boolean,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('administration.users.store'), {
            onSuccess: () => {
                reset();
                onOpenChange(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Nuevo Usuario</DialogTitle>
                        <DialogDescription>Crea una cuenta y asigna uno de los roles del sistema.</DialogDescription>
                    </DialogHeader>

                    <div className="space-y-2">
                        <Label htmlFor="create-name">Nombre</Label>
                        <Input id="create-name" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        <InputError message={errors.name} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="create-email">Email</Label>
                        <Input id="create-email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                        <InputError message={errors.email} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="create-role">Rol</Label>
                        <Select value={data.role} onValueChange={(value) => setData('role', value)}>
                            <SelectTrigger id="create-role">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {roles.map((role) => (
                                    <SelectItem key={role} value={role}>
                                        {role}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.role} />
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox
                            id="create-auto-password"
                            checked={data.autoPassword}
                            onCheckedChange={(checked) => {
                                const auto = Boolean(checked);
                                setData((prev) => ({ ...prev, autoPassword: auto, password: auto ? '' : prev.password }));
                            }}
                        />
                        <label htmlFor="create-auto-password" className="text-sm">
                            Generar contraseña automáticamente
                        </label>
                    </div>

                    {!data.autoPassword && (
                        <div className="space-y-2">
                            <Label htmlFor="create-password">Contraseña</Label>
                            <Input
                                id="create-password"
                                type="password"
                                value={data.password}
                                onChange={(e) => setData('password', e.target.value)}
                            />
                            <InputError message={errors.password} />
                        </div>
                    )}

                    <div className="flex items-center gap-2">
                        <Checkbox id="create-activo" checked={data.activo} onCheckedChange={(checked) => setData('activo', Boolean(checked))} />
                        <label htmlFor="create-activo" className="text-sm">
                            Usuario activo
                        </label>
                    </div>

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={() => onOpenChange(false)}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing}>
                            Crear usuario
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

function EditUserDialog({ user, roles, onClose }: { user: AdminUserData; roles: string[]; onClose: () => void }) {
    const { data, setData, put, processing, errors } = useForm({
        name: user.name,
        email: user.email,
        password: '',
        role: user.roles[0]?.name ?? roles[0] ?? '',
        activo: user.activo,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('administration.users.update', user.id), {
            onSuccess: () => onClose(),
        });
    };

    return (
        <Dialog open onOpenChange={(open) => !open && onClose()}>
            <DialogContent>
                <form onSubmit={submit} className="space-y-4">
                    <DialogHeader>
                        <DialogTitle>Editar Usuario</DialogTitle>
                        <DialogDescription>{user.email}</DialogDescription>
                    </DialogHeader>

                    <div className="space-y-2">
                        <Label htmlFor="edit-name">Nombre</Label>
                        <Input id="edit-name" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        <InputError message={errors.name} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="edit-email">Email</Label>
                        <Input id="edit-email" type="email" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                        <InputError message={errors.email} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="edit-role">Rol</Label>
                        <Select value={data.role} onValueChange={(value) => setData('role', value)}>
                            <SelectTrigger id="edit-role">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                {roles.map((role) => (
                                    <SelectItem key={role} value={role}>
                                        {role}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <InputError message={errors.role} />
                    </div>

                    <div className="space-y-2">
                        <Label htmlFor="edit-password">Nueva contraseña (opcional)</Label>
                        <Input
                            id="edit-password"
                            type="password"
                            placeholder="Dejar en blanco para no cambiarla"
                            value={data.password}
                            onChange={(e) => setData('password', e.target.value)}
                        />
                        <InputError message={errors.password} />
                    </div>

                    <div className="flex items-center gap-2">
                        <Checkbox id="edit-activo" checked={data.activo} onCheckedChange={(checked) => setData('activo', Boolean(checked))} />
                        <label htmlFor="edit-activo" className="text-sm">
                            Usuario activo
                        </label>
                    </div>
                    <InputError message={errors.activo} />

                    <DialogFooter>
                        <Button type="button" variant="outline" onClick={onClose}>
                            Cancelar
                        </Button>
                        <Button type="submit" disabled={processing}>
                            Guardar cambios
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

export default function AdministrationUsersIndex({
    users,
    roles,
    filters,
    status,
}: {
    users: Paginated<AdminUserData>;
    roles: string[];
    filters: { search: string; estado: string };
    status?: string;
}) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [estado, setEstado] = useState(filters.estado || 'todos');
    const [createOpen, setCreateOpen] = useState(false);
    const [editingUser, setEditingUser] = useState<AdminUserData | null>(null);

    const applyFilters = (nextSearch: string, nextEstado: string) => {
        router.get(
            route('administration.users.index'),
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
            <Head title="Usuarios" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <SubNav />

                <div className="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                    <HeadingSmall title="Usuarios" description="Cuentas del sistema, su rol asignado y estado." />
                    <Button onClick={() => setCreateOpen(true)}>
                        <UserPlus className="size-4" /> Nuevo usuario
                    </Button>
                </div>

                {status && (
                    <div role="status" className="flex items-center gap-2 rounded-lg border bg-muted/50 px-4 py-3 text-sm">
                        <CheckCircle2 className="size-4 text-emerald-600" />
                        {status}
                    </div>
                )}

                <form onSubmit={submitFilter} className="flex flex-col gap-3 sm:flex-row sm:items-center">
                    <div className="relative flex-1 sm:max-w-xs">
                        <Search className="absolute top-2.5 left-3 size-4 text-muted-foreground" />
                        <Input value={search} onChange={(e) => setSearch(e.target.value)} placeholder="Buscar por nombre o email" className="pl-9" />
                    </div>

                    <Select
                        value={estado}
                        onValueChange={(val) => {
                            setEstado(val);
                            applyFilters(search, val);
                        }}
                    >
                        <SelectTrigger className="sm:w-48">
                            <SelectValue placeholder="Estado" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="todos">Todos</SelectItem>
                            <SelectItem value="activo">Activos</SelectItem>
                            <SelectItem value="inactivo">Inactivos</SelectItem>
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
                                <TableHead>Nombre</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Rol</TableHead>
                                <TableHead>Estado</TableHead>
                                <TableHead className="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {users.data.length === 0 && (
                                <TableRow>
                                    <TableCell colSpan={5} className="py-8 text-center text-muted-foreground">
                                        No se encontraron usuarios.
                                    </TableCell>
                                </TableRow>
                            )}

                            {users.data.map((user) => (
                                <TableRow key={user.id}>
                                    <TableCell className="font-medium">{user.name}</TableCell>
                                    <TableCell className="text-muted-foreground">{user.email}</TableCell>
                                    <TableCell>
                                        {user.roles.length > 0 ? (
                                            <div className="flex flex-wrap gap-1">
                                                {user.roles.map((role) => (
                                                    <Badge key={role.id} variant="outline">
                                                        {role.name}
                                                    </Badge>
                                                ))}
                                            </div>
                                        ) : (
                                            <span className="text-xs text-muted-foreground">Sin rol asignado</span>
                                        )}
                                    </TableCell>
                                    <TableCell>
                                        <Badge variant={user.activo ? 'default' : 'secondary'}>{user.activo ? 'Activo' : 'Inactivo'}</Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button size="sm" variant="outline" onClick={() => setEditingUser(user)}>
                                            Editar
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                {users.last_page > 1 && (
                    <div className="flex flex-wrap items-center justify-center gap-1 pt-2">
                        {users.links.map((link, index) => (
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

            <CreateUserDialog roles={roles} open={createOpen} onOpenChange={setCreateOpen} />
            {editingUser && <EditUserDialog user={editingUser} roles={roles} onClose={() => setEditingUser(null)} />}
        </AppLayout>
    );
}
