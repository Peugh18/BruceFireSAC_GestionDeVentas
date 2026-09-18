import HeadingSmall from '@/components/heading-small';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { type RoleListItem } from '@/types/administration';
import { Head, Link } from '@inertiajs/react';
import { ShieldCheck, Users as UsersIcon } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Administración', href: route('administration.users.index') }];

function SubNav() {
    return (
        <div className="flex gap-2 border-b pb-2">
            <Button asChild variant="ghost" size="sm">
                <Link href={route('administration.users.index')}>
                    <UsersIcon className="size-4" /> Usuarios
                </Link>
            </Button>
            <Button asChild variant="secondary" size="sm">
                <Link href={route('administration.roles.index')}>
                    <ShieldCheck className="size-4" /> Roles
                </Link>
            </Button>
        </div>
    );
}

export default function AdministrationRolesIndex({ roles }: { roles: RoleListItem[] }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Roles" />

            <div className="flex flex-1 flex-col gap-6 p-4 md:p-6">
                <SubNav />

                <HeadingSmall title="Roles" description="Los 6 roles del sistema y cuantos permisos/usuarios tiene cada uno." />

                <div className="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Rol</TableHead>
                                <TableHead className="text-right">Permisos</TableHead>
                                <TableHead className="text-right">Usuarios</TableHead>
                                <TableHead className="text-right">Acciones</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {roles.map((role) => (
                                <TableRow key={role.id}>
                                    <TableCell className="font-medium">{role.name}</TableCell>
                                    <TableCell className="text-right">
                                        <Badge variant="outline">{role.permissions_count}</Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Badge variant="outline">{role.users_count}</Badge>
                                    </TableCell>
                                    <TableCell className="text-right">
                                        <Button asChild size="sm" variant="outline">
                                            <Link href={route('administration.roles.show', role.id)}>Ver permisos</Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </AppLayout>
    );
}
