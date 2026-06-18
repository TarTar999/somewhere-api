import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { useInitials } from '@/hooks/use-initials';
import type { User } from '@/types';

export function UserInfo({
    user,
    showEmail = false,
}: {
    user: User;
    showEmail?: boolean;
}) {
    const getInitials = useInitials();
    const fullName = user.name || `${user.first_name} ${user.last_name}`;

    return (
        <>
            <Avatar className="h-8 w-8 overflow-hidden rounded-xl flex-shrink-0 group-data-[collapsible=icon]:h-10 group-data-[collapsible=icon]:w-10">
                <AvatarImage src={user.avatar} alt={fullName} />
                <AvatarFallback className="rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 text-white font-semibold text-sm group-data-[collapsible=icon]:text-base">
                    {getInitials(fullName)}
                </AvatarFallback>
            </Avatar>
            <div className="grid flex-1 text-left text-sm leading-tight group-data-[collapsible=icon]:!hidden">
                <span className="truncate font-semibold text-gray-900">{fullName}</span>
                {showEmail && (
                    <span className="truncate text-xs text-gray-500">
                        {user.email}
                    </span>
                )}
            </div>
        </>
    );
}
