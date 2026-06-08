import { PropsWithChildren } from 'react';
import { Head, Link, usePage } from '@inertiajs/react';
import { CompanySidebar } from '@/components/company/company-sidebar';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Bell, LogOut, Settings, User } from 'lucide-react';
import type { CompanyRole } from '@/types/company';

interface CompanyLayoutProps extends PropsWithChildren {
    title?: string;
    company: {
        name: string;
        logo?: string;
    };
    userRole: CompanyRole;
}

export default function CompanyLayout({ children, title, company, userRole }: CompanyLayoutProps) {
    const { auth } = usePage().props as { auth: { user: { name: string; email: string; avatar?: string } } };
    const user = auth?.user;

    return (
        <>
            {title && <Head title={`${title} - ${company.name}`} />}

            <div className="flex h-screen bg-muted/30">
                {/* Sidebar */}
                <CompanySidebar company={company} userRole={userRole} />

                {/* Main Content */}
                <div className="flex flex-1 flex-col overflow-hidden">
                    {/* Header */}
                    <header className="flex h-16 items-center justify-between border-b bg-background px-6">
                        <h1 className="text-lg font-semibold">{title || 'Dashboard'}</h1>

                        <div className="flex items-center gap-4">
                            {/* Notifications */}
                            <Button variant="ghost" size="icon">
                                <Bell className="h-5 w-5" />
                            </Button>

                            {/* User Menu */}
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="ghost" className="relative h-10 w-10 rounded-full">
                                        <Avatar className="h-10 w-10">
                                            <AvatarImage src={user?.avatar} alt={user?.name} />
                                            <AvatarFallback>
                                                {user?.name
                                                    ?.split(' ')
                                                    .map((n) => n[0])
                                                    .join('')
                                                    .toUpperCase()}
                                            </AvatarFallback>
                                        </Avatar>
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent className="w-56" align="end" forceMount>
                                    <DropdownMenuLabel className="font-normal">
                                        <div className="flex flex-col space-y-1">
                                            <p className="text-sm font-medium leading-none">{user?.name}</p>
                                            <p className="text-xs leading-none text-muted-foreground">{user?.email}</p>
                                        </div>
                                    </DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem asChild>
                                        <Link href="/settings/profile">
                                            <User className="mr-2 h-4 w-4" />
                                            Profil
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem asChild>
                                        <Link href="/settings">
                                            <Settings className="mr-2 h-4 w-4" />
                                            Paramètres
                                        </Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem asChild>
                                        <Link href="/logout" method="post" as="button" className="w-full">
                                            <LogOut className="mr-2 h-4 w-4" />
                                            Déconnexion
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </header>

                    {/* Page Content */}
                    <main className="flex-1 overflow-auto p-6">{children}</main>
                </div>
            </div>
        </>
    );
}
