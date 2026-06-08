import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import CompanyLayout from '@/layouts/company-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { UserPlus, MoreHorizontal, Shield, UserMinus, Users } from 'lucide-react';
import type { CompanyMember, CompanyRole } from '@/types/company';

interface Props {
    members: CompanyMember[];
    canManageMembers: boolean;
    canChangeRoles: boolean;
    canAddMore: boolean;
    memberLimit: number;
}

export default function MembersIndex({ members, canManageMembers, canChangeRoles, canAddMore, memberLimit }: Props) {
    const { props } = usePage();
    const company = (props as { company?: { name: string; logo?: string } }).company || { name: 'Entreprise' };
    const userRole = ((props as { userRole?: CompanyRole }).userRole || 'member') as CompanyRole;

    const [memberToRemove, setMemberToRemove] = useState<CompanyMember | null>(null);

    const getRoleBadgeVariant = (role: CompanyRole) => {
        switch (role) {
            case 'admin':
                return 'default';
            case 'manager':
                return 'secondary';
            default:
                return 'outline';
        }
    };

    const handleChangeRole = (memberId: number, newRole: CompanyRole) => {
        router.put(`/company/members/${memberId}/role`, { role: newRole });
    };

    const handleRemoveMember = () => {
        if (!memberToRemove) return;
        router.delete(`/company/members/${memberToRemove.id}`, {
            onSuccess: () => setMemberToRemove(null),
        });
    };

    return (
        <CompanyLayout title="Membres" company={company} userRole={userRole}>
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-bold">Gestion des membres</h2>
                        <p className="text-muted-foreground">
                            {members.length}/{memberLimit} membres
                        </p>
                    </div>
                    {canManageMembers && (
                        <Link href="/company/members/invite">
                            <Button disabled={!canAddMore}>
                                <UserPlus className="mr-2 h-4 w-4" />
                                Inviter un membre
                            </Button>
                        </Link>
                    )}
                </div>

                {/* Members List */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Users className="h-5 w-5" />
                            Équipe
                        </CardTitle>
                        <CardDescription>Tous les membres de votre entreprise</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="space-y-4">
                            {members.map((member) => (
                                <div key={member.id} className="flex items-center justify-between rounded-lg border p-4">
                                    <div className="flex items-center gap-4">
                                        <Avatar className="h-12 w-12">
                                            <AvatarImage src={member.avatar} alt={member.name} />
                                            <AvatarFallback>
                                                {member.name
                                                    .split(' ')
                                                    .map((n) => n[0])
                                                    .join('')
                                                    .toUpperCase()}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div>
                                            <p className="font-medium">{member.name}</p>
                                            <p className="text-sm text-muted-foreground">{member.email}</p>
                                            {member.phone && <p className="text-sm text-muted-foreground">{member.phone}</p>}
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-4">
                                        <div className="text-right">
                                            <Badge variant={getRoleBadgeVariant(member.role)} className="capitalize">
                                                {member.role}
                                            </Badge>
                                            {member.status === 'pending' && (
                                                <Badge variant="secondary" className="ml-2">
                                                    En attente
                                                </Badge>
                                            )}
                                            {member.joinedAt && (
                                                <p className="mt-1 text-xs text-muted-foreground">Rejoint {member.joinedAt}</p>
                                            )}
                                        </div>
                                        {canManageMembers && member.status === 'active' && (
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button variant="ghost" size="icon">
                                                        <MoreHorizontal className="h-4 w-4" />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuLabel>Actions</DropdownMenuLabel>
                                                    {canChangeRoles && (
                                                        <>
                                                            <DropdownMenuSeparator />
                                                            <DropdownMenuLabel className="text-xs text-muted-foreground">
                                                                Changer le rôle
                                                            </DropdownMenuLabel>
                                                            <DropdownMenuItem
                                                                onClick={() => handleChangeRole(member.id, 'admin')}
                                                                disabled={member.role === 'admin'}
                                                            >
                                                                <Shield className="mr-2 h-4 w-4" />
                                                                Admin
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem
                                                                onClick={() => handleChangeRole(member.id, 'manager')}
                                                                disabled={member.role === 'manager'}
                                                            >
                                                                Manager
                                                            </DropdownMenuItem>
                                                            <DropdownMenuItem
                                                                onClick={() => handleChangeRole(member.id, 'member')}
                                                                disabled={member.role === 'member'}
                                                            >
                                                                Membre
                                                            </DropdownMenuItem>
                                                        </>
                                                    )}
                                                    <DropdownMenuSeparator />
                                                    <DropdownMenuItem
                                                        className="text-destructive"
                                                        onClick={() => setMemberToRemove(member)}
                                                    >
                                                        <UserMinus className="mr-2 h-4 w-4" />
                                                        Retirer
                                                    </DropdownMenuItem>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        )}
                                    </div>
                                </div>
                            ))}
                        </div>
                    </CardContent>
                </Card>
            </div>

            {/* Remove Member Dialog */}
            <AlertDialog open={!!memberToRemove} onOpenChange={() => setMemberToRemove(null)}>
                <AlertDialogContent>
                    <AlertDialogHeader>
                        <AlertDialogTitle>Retirer ce membre ?</AlertDialogTitle>
                        <AlertDialogDescription>
                            Êtes-vous sûr de vouloir retirer {memberToRemove?.name} de l'équipe ? Cette action est irréversible.
                        </AlertDialogDescription>
                    </AlertDialogHeader>
                    <AlertDialogFooter>
                        <AlertDialogCancel>Annuler</AlertDialogCancel>
                        <AlertDialogAction onClick={handleRemoveMember} className="bg-destructive text-destructive-foreground">
                            Retirer
                        </AlertDialogAction>
                    </AlertDialogFooter>
                </AlertDialogContent>
            </AlertDialog>
        </CompanyLayout>
    );
}
