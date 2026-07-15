import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { UserMenuContent } from '@/components/user-menu-content';
import { useIsMobile } from '@/hooks/use-mobile';
import { useInitials } from '@/hooks/use-initials';
import type { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';
import { ChevronsUpDown } from 'lucide-react';

export function NavUser() {
    const { auth } = usePage<SharedData>().props;
    const { state } = useSidebar();
    const isMobile = useIsMobile();
    const getInitials = useInitials();
    const isCollapsed = state === 'collapsed';
    const fullName = auth.user.name || `${auth.user.first_name} ${auth.user.last_name}`;

    return (
        <SidebarMenu>
            <SidebarMenuItem>
                <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                        <SidebarMenuButton
                            size="lg"
                            className="text-foreground hover:bg-muted data-[state=open]:bg-muted rounded-xl"
                            data-test="sidebar-menu-button"
                        >
                            <Avatar className="overflow-hidden rounded-xl flex-shrink-0 h-8 w-8 group-data-[collapsible=icon]:h-10 group-data-[collapsible=icon]:w-10">
                                <AvatarImage src={auth.user.avatar} alt={fullName} />
                                <AvatarFallback className="rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 text-white font-semibold text-sm group-data-[collapsible=icon]:text-base">
                                    {getInitials(fullName)}
                                </AvatarFallback>
                            </Avatar>
                            <div className="grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:hidden">
                                <span className="truncate font-semibold text-foreground">{fullName}</span>
                            </div>
                            <ChevronsUpDown className="ml-auto size-4 text-muted-foreground group-data-[collapsible=icon]:hidden" />
                        </SidebarMenuButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent
                        className="w-(--radix-dropdown-menu-trigger-width) min-w-56 rounded-xl shadow-lg border-border"
                        align="end"
                        side={isMobile ? 'bottom' : isCollapsed ? 'right' : 'bottom'}
                    >
                        <UserMenuContent user={auth.user} />
                    </DropdownMenuContent>
                </DropdownMenu>
            </SidebarMenuItem>
        </SidebarMenu>
    );
}
