import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Panel',
        href: '/dashboard',
    },
];

export default function Dashboard() {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Panel" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="border-sidebar-border/70 dark:border-sidebar-border flex min-h-[60vh] flex-1 flex-col items-center justify-center gap-2 rounded-xl border text-center">
                    <p className="text-muted-foreground text-sm">El panel operativo de BRUCE FIRE se construirá próximamente.</p>
                </div>
            </div>
        </AppLayout>
    );
}
