import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';
import { Link } from '@inertiajs/react';
import { LayoutGrid, MapPin, FileText, FolderOpen, Truck, Building2, Settings, HelpCircle } from 'lucide-react';
import AppLogo from './app-logo';

const mainNavItems: NavItem[] = [
    {
        title: 'Tableau de bord',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Mes Adresses',
        href: '/dashboard#addresses',
        icon: MapPin,
    },
    {
        title: 'Mes Documents',
        href: '/dashboard#documents',
        icon: FileText,
    },
    {
        title: 'Collections',
        href: '/collections',
        icon: FolderOpen,
    },
    {
        title: 'Livraisons',
        href: '/deliveries',
        icon: Truck,
    },
];

const companyNavItems: NavItem[] = [
    {
        title: 'Espace Entreprise',
        href: '/company',
        icon: Building2,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Paramètres',
        href: '/settings',
        icon: Settings,
    },
    {
        title: 'Aide',
        href: '/help',
        icon: HelpCircle,
    },
];

export function AppSidebar() {
    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} label="Personnel" />
                <NavMain items={companyNavItems} label="Entreprise" />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
