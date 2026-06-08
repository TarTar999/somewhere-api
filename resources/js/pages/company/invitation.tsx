import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Building2, UserPlus, XCircle } from 'lucide-react';
import type { Invitation } from '@/types/company';

interface Props {
    invitation: Invitation | null;
    error?: string;
}

export default function InvitationPage({ invitation, error }: Props) {
    const handleAccept = () => {
        if (!invitation) return;
        router.post(`/company/invitation/${invitation.token}/accept`);
    };

    if (error || !invitation) {
        return (
            <>
                <Head title="Invitation invalide" />

                <div className="flex min-h-screen items-center justify-center bg-muted/30 p-4">
                    <Card className="w-full max-w-md">
                        <CardContent className="py-12 text-center">
                            <XCircle className="mx-auto h-16 w-16 text-destructive" />
                            <h1 className="mt-4 text-xl font-bold">Invitation invalide</h1>
                            <p className="mt-2 text-muted-foreground">{error || 'Cette invitation n\'existe pas ou a expiré.'}</p>
                            <Link href="/dashboard" className="mt-6 inline-block">
                                <Button>Retour au dashboard</Button>
                            </Link>
                        </CardContent>
                    </Card>
                </div>
            </>
        );
    }

    const getRoleLabel = (role: string) => {
        switch (role) {
            case 'admin':
                return 'Administrateur';
            case 'manager':
                return 'Manager';
            default:
                return 'Membre';
        }
    };

    return (
        <>
            <Head title={`Rejoindre ${invitation.company.name}`} />

            <div className="flex min-h-screen items-center justify-center bg-muted/30 p-4">
                <Card className="w-full max-w-md">
                    <CardHeader className="text-center">
                        <div className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
                            {invitation.company.logo ? (
                                <img
                                    src={invitation.company.logo}
                                    alt={invitation.company.name}
                                    className="h-12 w-12 rounded-full object-cover"
                                />
                            ) : (
                                <Building2 className="h-8 w-8 text-primary" />
                            )}
                        </div>
                        <CardTitle className="mt-4">Invitation à rejoindre</CardTitle>
                        <CardDescription className="text-lg font-semibold">{invitation.company.name}</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-6">
                        <div className="rounded-lg bg-muted p-4 text-center">
                            <p className="text-sm text-muted-foreground">Vous êtes invité en tant que</p>
                            <Badge className="mt-2 text-lg" variant="secondary">
                                {getRoleLabel(invitation.role)}
                            </Badge>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            <p>Cette invitation expire {invitation.expiresAt}</p>
                        </div>

                        <div className="flex flex-col gap-3">
                            <Button onClick={handleAccept} className="w-full" size="lg">
                                <UserPlus className="mr-2 h-5 w-5" />
                                Accepter l'invitation
                            </Button>
                            <Link href="/dashboard" className="w-full">
                                <Button variant="outline" className="w-full">
                                    Refuser
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
