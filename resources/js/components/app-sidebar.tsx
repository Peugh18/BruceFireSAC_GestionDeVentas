import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type NavMainItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import {
    AlertTriangle,
    BarChart3,
    BookOpen,
    Bot,
    ClipboardList,
    FileBadge,
    FileText,
    LayoutGrid,
    Route as RouteIcon,
    Settings,
    ShoppingBag,
    Truck,
    Users,
    Wallet,
    Wrench,
} from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;
    const can = (permission: string) => auth.permissions.includes(permission);

    const panel: NavItem = {
        title: 'Panel',
        url: '/dashboard',
        icon: LayoutGrid,
    };

    const commercialItems: NavItem[] = [
        ...(can('clients.view') ? [{ title: 'Clientes', url: '/clients', icon: Users }] : []),
        ...(can('quotes.view') ? [{ title: 'Cotizaciones', url: route('quotes.index', {}, false), icon: FileText }] : []),
        ...(can('sales.view') ? [{ title: 'Ventas', url: route('sales.index', {}, false), icon: ShoppingBag }] : []),
        ...(can('billing.view') ? [{ title: 'Facturación', url: route('billing.index', {}, false), icon: FileText }] : []),
        ...(can('collections.view') ? [{ title: 'Cobranzas', url: route('collections.index', {}, false), icon: Wallet }] : []),
    ];

    const technicalItems: NavItem[] = [
        ...(can('service_orders.view') ? [{ title: 'Órdenes de Servicio', url: route('service-orders.index', {}, false), icon: ClipboardList }] : []),
        ...(can('pickups.view') ? [{ title: 'Recojos', url: route('pickups.index', {}, false), icon: Truck }] : []),
        ...(can('deficiencies.view') ? [{ title: 'Deficiencias', url: route('deficiencies.index', {}, false), icon: AlertTriangle }] : []),
        ...(can('certificates.view') ? [{ title: 'Certificados', url: route('certificates.index', {}, false), icon: FileBadge }] : []),
    ];

    const catalogItems: NavItem[] = [
        ...(can('catalog.view') ? [{ title: 'Catálogo', url: route('catalog.index', {}, false), icon: BookOpen }] : []),
        ...(can('equipment.view') ? [{ title: 'Equipos', url: route('equipment.index', {}, false), icon: Wrench }] : []),
    ];

    const managementItems: NavItem[] = [
        ...(can('reports.view') ? [{ title: 'Reportes', url: route('reports.index', {}, false), icon: BarChart3 }] : []),
        ...(can('ai_assistant.view') ? [{ title: 'Asistente Gerencial', url: route('ai-assistant.index', {}, false), icon: Bot }] : []),
        ...(can('shipping_guides.view') ? [{ title: 'Guías de Remisión', url: route('shipping.index', {}, false), icon: RouteIcon }] : []),
        ...(can('users.manage') || can('roles.manage')
            ? [
                  {
                      title: 'Administración',
                      url: route(can('users.manage') ? 'administration.users.index' : 'administration.roles.index', {}, false),
                      icon: Settings,
                  },
              ]
            : []),
    ];

    const items: NavMainItem[] = [
        panel,
        { label: 'Comercial', items: commercialItems },
        { label: 'Operaciones técnicas', items: technicalItems },
        { label: 'Catálogo y Stock', items: catalogItems },
        { label: 'Gestión', items: managementItems },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href="/dashboard" prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={items} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
