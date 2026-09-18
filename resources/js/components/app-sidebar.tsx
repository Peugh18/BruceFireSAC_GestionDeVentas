import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
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
    Package,
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

    const items: NavItem[] = [
        {
            title: 'Panel',
            url: '/dashboard',
            icon: LayoutGrid,
        },
        ...(auth.permissions.includes('clients.view')
            ? [
                  {
                      title: 'Clientes',
                      url: '/clients',
                      icon: Users,
                  },
              ]
            : []),
        ...(auth.permissions.includes('catalog.view')
            ? [
                  {
                      title: 'Catálogo',
                      url: route('catalog.index', {}, false),
                      icon: BookOpen,
                  },
              ]
            : []),
        ...(auth.permissions.includes('equipment.view')
            ? [
                  {
                      title: 'Equipos',
                      url: route('equipment.index', {}, false),
                      icon: Wrench,
                  },
              ]
            : []),
        ...(auth.permissions.includes('inventory.view') ? [{ title: 'Inventario', url: route('inventory.index', {}, false), icon: Package }] : []),
        ...(auth.permissions.includes('quotes.view')
            ? [{ title: 'Cotizaciones', url: route('quotes.index', {}, false), icon: FileText }]
            : []),
        ...(auth.permissions.includes('sales.view')
            ? [{ title: 'Ventas', url: route('sales.index', {}, false), icon: ShoppingBag }]
            : []),
        ...(auth.permissions.includes('service_orders.view')
            ? [{ title: 'Órdenes de Servicio', url: route('service-orders.index', {}, false), icon: ClipboardList }]
            : []),
        ...(auth.permissions.includes('pickups.view')
            ? [{ title: 'Recojos', url: route('pickups.index', {}, false), icon: Truck }]
            : []),
        ...(auth.permissions.includes('deficiencies.view')
            ? [{ title: 'Deficiencias', url: route('deficiencies.index', {}, false), icon: AlertTriangle }]
            : []),
        ...(auth.permissions.includes('certificates.view')
            ? [{ title: 'Certificados', url: route('certificates.index', {}, false), icon: FileBadge }]
            : []),
        ...(auth.permissions.includes('collections.view')
            ? [{ title: 'Cobranzas', url: route('collections.index', {}, false), icon: Wallet }]
            : []),
        ...(auth.permissions.includes('reports.view')
            ? [{ title: 'Reportes', url: route('reports.index', {}, false), icon: BarChart3 }]
            : []),
        ...(auth.permissions.includes('reports.view')
            ? [{ title: 'Asistente Gerencial', url: route('ai-assistant.index', {}, false), icon: Bot }]
            : []),
        ...(auth.permissions.includes('shipping_guides.view')
            ? [{ title: 'Guías de Remisión', url: route('shipping.index', {}, false), icon: RouteIcon }]
            : []),
        ...(auth.permissions.includes('users.manage') || auth.permissions.includes('roles.manage')
            ? [
                  {
                      title: 'Administración',
                      url: route(auth.permissions.includes('users.manage') ? 'administration.users.index' : 'administration.roles.index', {}, false),
                      icon: Settings,
                  },
              ]
            : []),
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
