import { Link, usePage } from '@inertiajs/react';
import { Building2, Users, MapPin, FileText, CreditCard, Settings, LayoutDashboard, ChevronRight, Shapes, Tags } from 'lucide-react';
import { cn } from '@/lib/utils';
import type { CompanyRole } from '@/types/company';

interface NavItem {
    title: string;
    href: string;
    icon: React.ComponentType<{ className?: string }>;
    roles?: CompanyRole[];
}

const navItems: NavItem[] = [
    { title: 'Dashboard', href: '/company', icon: LayoutDashboard },
    { title: 'Membres', href: '/company/members', icon: Users },
    { title: 'Adresses', href: '/company/addresses', icon: MapPin },
    { title: 'Zones', href: '/company/zones', icon: Shapes, roles: ['admin', 'manager'] },
    { title: 'Labels', href: '/company/labels', icon: Tags, roles: ['admin', 'manager'] },
    { title: 'Abonnement', href: '/company/subscription', icon: CreditCard, roles: ['admin'] },
    { title: 'Paramètres', href: '/company/settings', icon: Settings, roles: ['admin'] },
];

interface CompanySidebarProps {
    company: {
        name: string;
        logo?: string;
    };
    userRole: CompanyRole;
}

export function CompanySidebar({ company, userRole }: CompanySidebarProps) {
    const { url } = usePage();

    const filteredItems = navItems.filter((item) => !item.roles || item.roles.includes(userRole));

    return (
        <aside className="flex h-screen w-64 flex-col border-r bg-background">
            {/* Company Header */}
            <div className="flex h-16 items-center gap-3 border-b px-4">
                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-primary/10">
                    {company.logo ? (
                        <img src={company.logo} alt={company.name} className="h-8 w-8 rounded object-cover" />
                    ) : (
                        <Building2 className="h-5 w-5 text-primary" />
                    )}
                </div>
                <div className="flex-1 truncate">
                    <p className="truncate text-sm font-semibold">{company.name}</p>
                    <p className="text-xs capitalize text-muted-foreground">{userRole}</p>
                </div>
            </div>

            {/* Navigation */}
            <nav className="flex-1 space-y-1 p-2">
                {filteredItems.map((item) => {
                    const isActive = url === item.href || url.startsWith(item.href + '/');
                    const Icon = item.icon;

                    return (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={cn(
                                'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition-colors',
                                isActive ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground'
                            )}
                        >
                            <Icon className="h-4 w-4" />
                            {item.title}
                            {isActive && <ChevronRight className="ml-auto h-4 w-4" />}
                        </Link>
                    );
                })}
            </nav>

            {/* Footer */}
            <div className="border-t p-4">
                <Link href="/company/select" className="flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground">
                    <Building2 className="h-4 w-4" />
                    Changer d'entreprise
                </Link>
            </div>
        </aside>
    );
}
