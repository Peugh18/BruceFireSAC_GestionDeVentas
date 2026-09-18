import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type PermissionGroups, type RoleDetail } from '@/types/administration';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, CheckCircle2, ShieldAlert } from 'lucide-react';
import { FormEventHandler } from 'react';

const PROTECTED_PERMISSIONS = ['users.manage', 'roles.manage'];

function formatGroupName(group: string): string {
    return group.replace(/_/g, ' ').replace(/^./, (c) => c.toUpperCase());
}

function formatPermissionLabel(permission: string): string {
    const action = permission.includes('.') ? permission.split('.').slice(1).join('.') : permission;
    return action.replace(/_/g, ' ');
}

export default function AdministrationRoleShow({
    role,
    permissionGroups,
    rolePermissions,
    status,
}: {
    role: RoleDetail;
    permissionGroups: PermissionGroups;
    rolePermissions: string[];
    status?: string;
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Administración', href: route('administration.users.index') },
        { title: 'Roles', href: route('administration.roles.index') },
        { title: role.name, href: route('administration.roles.show', role.id) },
    ];

    const isAdminRole = role.name === 'Gerente';

    const { data, setData, put, processing, errors } = useForm({
        permissions: rolePermissions,
    });

    const toggle = (permission: string, checked: boolean) => {
        if (isAdminRole && PROTECTED_PERMISSIONS.includes(permission)) {
            return;
        }

        setData('permissions', checked ? [...data.permissions, permission] : data.permissions.filter((p) => p !== permission));
    };

    const toggleGroup = (permissions: string[], checked: boolean) => {
        const editable = permissions.filter((p) => !(isAdminRole && PROTECTED_PERMISSIONS.includes(p)));
        const withoutGroup = data.permissions.filter((p) => !editable.includes(p));
        setData('permissions', checked ? [...withoutGroup, ...editable] : withoutGroup);
    };

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        put(route('administration.roles.update', role.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Rol ${role.name}`} />

            <div className="mx-auto flex w-full max-w-4xl flex-1 flex-col gap-6 p-4 md:p-6">
                <div className="flex items-center gap-3">
                    <Button asChild variant="outline" size="icon" className="shrink-0">
                        <Link href={route('administration.roles.index')}>
                            <ArrowLeft className="size-4" />
                        </Link>
                    </Button>
                    <HeadingSmall title={role.name} description="Permisos del sistema agrupados por modulo. Marca los que este rol debe tener." />
                </div>

                {status && (
                    <div role="status" className="flex items-center gap-2 rounded-lg border bg-muted/50 px-4 py-3 text-sm">
                        <CheckCircle2 className="size-4 text-emerald-600" />
                        {status}
                    </div>
                )}

                {isAdminRole && (
                    <div className="flex items-start gap-2 rounded-lg border border-warning/50 bg-warning/5 px-4 py-3 text-sm">
                        <ShieldAlert className="mt-0.5 size-4 shrink-0 text-warning" />
                        <p>
                            Este rol siempre conserva <span className="font-medium">users.manage</span> y{' '}
                            <span className="font-medium">roles.manage</span>: sin ellos nadie podria administrar el sistema.
                        </p>
                    </div>
                )}

                <InputError message={errors.permissions} />

                <form onSubmit={submit} className="space-y-4">
                    <div className="grid gap-4 sm:grid-cols-2">
                        {Object.entries(permissionGroups).map(([group, permissions]) => {
                            const allChecked = permissions.every((p) => data.permissions.includes(p));
                            const someChecked = permissions.some((p) => data.permissions.includes(p));

                            return (
                                <Card key={group}>
                                    <CardHeader className="flex flex-row items-center justify-between gap-2 pb-2">
                                        <CardTitle className="text-sm">{formatGroupName(group)}</CardTitle>
                                        <button
                                            type="button"
                                            onClick={() => toggleGroup(permissions, !allChecked)}
                                            className="text-xs text-muted-foreground hover:text-foreground hover:underline"
                                        >
                                            {allChecked ? 'Ninguno' : 'Todos'}
                                        </button>
                                    </CardHeader>
                                    <CardContent className="space-y-2">
                                        {permissions.map((permission) => {
                                            const locked = isAdminRole && PROTECTED_PERMISSIONS.includes(permission);
                                            const checked = data.permissions.includes(permission);

                                            return (
                                                <div key={permission} className="flex items-center gap-2">
                                                    <Checkbox
                                                        id={`perm-${permission}`}
                                                        checked={checked}
                                                        disabled={locked}
                                                        onCheckedChange={(value) => toggle(permission, Boolean(value))}
                                                    />
                                                    <label
                                                        htmlFor={`perm-${permission}`}
                                                        className="text-sm capitalize"
                                                        title={permission}
                                                    >
                                                        {formatPermissionLabel(permission)}
                                                    </label>
                                                    {locked && <span className="text-xs text-muted-foreground">(requerido)</span>}
                                                </div>
                                            );
                                        })}
                                        {someChecked && !allChecked && (
                                            <p className="pt-1 text-xs text-muted-foreground">
                                                {permissions.filter((p) => data.permissions.includes(p)).length} de {permissions.length}
                                            </p>
                                        )}
                                    </CardContent>
                                </Card>
                            );
                        })}
                    </div>

                    <Button type="submit" disabled={processing} className="w-full sm:w-auto">
                        {processing ? 'Guardando...' : 'Guardar permisos'}
                    </Button>
                </form>
            </div>
        </AppLayout>
    );
}
