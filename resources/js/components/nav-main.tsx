import { SidebarGroup, SidebarGroupLabel, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavGroup, type NavItem, type NavMainItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';

function isNavGroup(item: NavMainItem): item is NavGroup {
    return 'items' in item;
}

function NavMenuItem({ item, currentUrl }: { item: NavItem; currentUrl: string }) {
    return (
        <SidebarMenuItem>
            <SidebarMenuButton asChild isActive={item.url === currentUrl}>
                <Link href={item.url} prefetch>
                    {item.icon && <item.icon />}
                    <span>{item.title}</span>
                </Link>
            </SidebarMenuButton>
        </SidebarMenuItem>
    );
}

export function NavMain({ items = [] }: { items: NavMainItem[] }) {
    const page = usePage();
    return (
        <>
            {items.map((item) => {
                if (isNavGroup(item)) {
                    if (item.items.length === 0) {
                        return null;
                    }

                    return (
                        <SidebarGroup key={item.label} className="px-2 py-0">
                            <SidebarGroupLabel>{item.label}</SidebarGroupLabel>
                            <SidebarMenu>
                                {item.items.map((groupItem) => (
                                    <NavMenuItem key={groupItem.title} item={groupItem} currentUrl={page.url} />
                                ))}
                            </SidebarMenu>
                        </SidebarGroup>
                    );
                }

                return (
                    <SidebarGroup key={item.title} className="px-2 py-0">
                        <SidebarMenu>
                            <NavMenuItem item={item} currentUrl={page.url} />
                        </SidebarMenu>
                    </SidebarGroup>
                );
            })}
        </>
    );
}
