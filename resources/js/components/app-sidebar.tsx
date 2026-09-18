import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem, type SharedData } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BookOpen, ClipboardList, FileText, LayoutGrid, Package, ShoppingBag, Users, Wrench } from 'lucide-react';
import AppLogo from './app-logo';

export function AppSidebar() {
    const { auth } = usePage<SharedData>().props;

    const items: NavItem[] = [
        {
            title: 'Dashboard',
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
