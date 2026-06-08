import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Building2, Users, Plus, CheckCircle, AlertTriangle } from 'lucide-react';
import type { CompanyRole } from '@/types/company';

interface CompanyItem {
    id: number;
    name: string;
    logo?: string;
    role: CompanyRole;
    membersCount: number;
    hasActiveSubscription: boolean;
}

interface Props {
    companies: CompanyItem[];
    currentCompanyId?: number;
}

export default function SelectCompany({ companies, currentCompanyId }: Props) {
    const handleSelect = (companyId: number) => {
        router.post(`/company/select/${companyId}`);
    };

    return (
        <>
            <Head title="Sélectionner une entreprise" />

            <div className="flex min-h-screen items-center justify-center bg-muted/30 p-4">
                <div className="w-full max-w-2xl space-y-6">
                    {/* Header */}
                    <div className="text-center">
                        <Building2 className="mx-auto h-12 w-12 text-primary" />
                        <h1 className="mt-4 text-2xl font-bold">Sélectionner une entreprise</h1>
                        <p className="mt-2 text-muted-foreground">Choisissez l'entreprise avec laquelle vous souhaitez travailler</p>
                    </div>

                    {/* Companies List */}
                    {companies.length > 0 ? (
                        <div className="space-y-4">
                            {companies.map((company) => (
                                <Card
                                    key={company.id}
                                    className={`cursor-pointer transition-colors hover:bg-muted/50 ${
                                        company.id === currentCompanyId ? 'border-primary' : ''
                                    }`}
                                    onClick={() => handleSelect(company.id)}
                                >
                                    <CardContent className="flex items-center justify-between p-6">
                                        <div className="flex items-center gap-4">
                                            <div className="flex h-14 w-14 items-center justify-center rounded-lg bg-primary/10">
                                                {company.logo ? (
                                                    <img
                                                        src={company.logo}
                                                        alt={company.name}
                                                        className="h-10 w-10 rounded object-cover"
                                                    />
                                                ) : (
                                                    <Building2 className="h-7 w-7 text-primary" />
                                                )}
                                            </div>
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <h3 className="font-semibold">{company.name}</h3>
                                                    {company.id === currentCompanyId && (
                                                        <Badge variant="outline" className="text-xs">
                                                            Actuelle
                                                        </Badge>
                                                    )}
                                                </div>
                                                <div className="mt-1 flex items-center gap-4 text-sm text-muted-foreground">
                                                    <span className="flex items-center gap-1">
                                                        <Users className="h-4 w-4" />
                                                        {company.membersCount} membres
                                                    </span>
                                                    <Badge variant="outline" className="capitalize">
                                                        {company.role}
                                                    </Badge>
                                                </div>
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            {company.hasActiveSubscription ? (
                                                <Badge className="bg-green-500">
                                                    <CheckCircle className="mr-1 h-3 w-3" />
                                                    Actif
                                                </Badge>
                                            ) : (
                                                <Badge variant="destructive">
                                                    <AlertTriangle className="mr-1 h-3 w-3" />
                                                    Inactif
                                                </Badge>
                                            )}
                                        </div>
                                    </CardContent>
                                </Card>
                            ))}
                        </div>
                    ) : (
                        <Card>
                            <CardContent className="py-12 text-center">
                                <Building2 className="mx-auto h-12 w-12 text-muted-foreground opacity-50" />
                                <p className="mt-4 text-muted-foreground">Vous n'appartenez à aucune entreprise</p>
                            </CardContent>
                        </Card>
                    )}

                    {/* Create Company */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="text-lg">Créer une nouvelle entreprise</CardTitle>
                            <CardDescription>
                                Démarrez votre propre espace entreprise pour gérer votre équipe
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Link href="/company/create">
                                <Button className="w-full">
                                    <Plus className="mr-2 h-4 w-4" />
                                    Créer une entreprise
                                </Button>
                            </Link>
                        </CardContent>
                    </Card>

                    {/* Back to Dashboard */}
                    <div className="text-center">
                        <Link href="/dashboard" className="text-sm text-muted-foreground hover:text-foreground">
                            Retour au dashboard personnel
                        </Link>
                    </div>
                </div>
            </div>
        </>
    );
}
