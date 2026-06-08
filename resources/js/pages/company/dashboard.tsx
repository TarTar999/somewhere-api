import CompanyLayout from '@/layouts/company-layout';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { Users, MapPin, FileText, Calendar, TrendingUp, AlertCircle } from 'lucide-react';
import type { CompanyRole, CompanyStats, CompanySubscription, CompanyDocument, CompanyMember } from '@/types/company';

interface Props {
    company: {
        id: number;
        name: string;
        logo?: string;
        status: string;
    };
    userRole: CompanyRole;
    stats: CompanyStats;
    subscription: CompanySubscription | null;
    recentDocuments: CompanyDocument[];
    recentMembers: CompanyMember[];
}

export default function CompanyDashboard({ company, userRole, stats, subscription, recentDocuments, recentMembers }: Props) {
    const documentsProgress = stats.documentsLimit > 0 ? (stats.documentsThisMonth / stats.documentsLimit) * 100 : 0;
    const membersProgress = stats.maxMembers > 0 ? (stats.totalMembers / stats.maxMembers) * 100 : 0;

    return (
        <CompanyLayout title="Dashboard" company={company} userRole={userRole}>
            <div className="space-y-6">
                {/* Subscription Warning */}
                {subscription && subscription.daysUntilRenewal <= 7 && (
                    <Card className="border-orange-200 bg-orange-50">
                        <CardContent className="flex items-center gap-4 pt-6">
                            <AlertCircle className="h-8 w-8 text-orange-500" />
                            <div>
                                <p className="font-medium">Renouvellement dans {subscription.daysUntilRenewal} jour(s)</p>
                                <p className="text-sm text-muted-foreground">
                                    Votre abonnement expire le{' '}
                                    {new Date(subscription.periodEnd).toLocaleDateString('fr-FR')}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Stats Grid */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Membres</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.totalMembers}/{stats.maxMembers}
                            </div>
                            <Progress value={membersProgress} className="mt-2" />
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Adresses</CardTitle>
                            <MapPin className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.totalAddresses}</div>
                            <p className="text-xs text-muted-foreground">Total des adresses de l'équipe</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Documents ce mois</CardTitle>
                            <FileText className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.documentsThisMonth}/{stats.documentsLimit}
                            </div>
                            <Progress value={documentsProgress} className="mt-2" />
                            <p className="mt-1 text-xs text-muted-foreground">{stats.documentsRemaining} restants</p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between pb-2">
                            <CardTitle className="text-sm font-medium">Abonnement</CardTitle>
                            <Calendar className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            {subscription ? (
                                <>
                                    <div className="text-2xl font-bold">{subscription.planName}</div>
                                    <Badge variant={subscription.status === 'active' ? 'default' : 'destructive'}>
                                        {subscription.status === 'active' ? 'Actif' : subscription.status}
                                    </Badge>
                                </>
                            ) : (
                                <p className="text-muted-foreground">Aucun abonnement</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Recent Activity */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Recent Documents */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <FileText className="h-5 w-5" />
                                Documents récents
                            </CardTitle>
                            <CardDescription>Les derniers documents créés par l'équipe</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {recentDocuments.length > 0 ? (
                                <div className="space-y-4">
                                    {recentDocuments.map((doc) => (
                                        <div key={doc.id} className="flex items-center justify-between">
                                            <div>
                                                <p className="text-sm font-medium">{doc.documentTypeLabel}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {doc.address} - par {doc.createdBy}
                                                </p>
                                            </div>
                                            <Badge variant={doc.isExpired ? 'destructive' : 'outline'}>
                                                {doc.isExpired ? 'Expiré' : doc.status}
                                            </Badge>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-center text-muted-foreground">Aucun document récent</p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Recent Members */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Users className="h-5 w-5" />
                                Membres récents
                            </CardTitle>
                            <CardDescription>Les derniers membres à avoir rejoint</CardDescription>
                        </CardHeader>
                        <CardContent>
                            {recentMembers.length > 0 ? (
                                <div className="space-y-4">
                                    {recentMembers.map((member) => (
                                        <div key={member.id} className="flex items-center justify-between">
                                            <div className="flex items-center gap-3">
                                                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                                                    {member.name
                                                        .split(' ')
                                                        .map((n) => n[0])
                                                        .join('')
                                                        .toUpperCase()}
                                                </div>
                                                <div>
                                                    <p className="text-sm font-medium">{member.name}</p>
                                                    <p className="text-xs text-muted-foreground">{member.email}</p>
                                                </div>
                                            </div>
                                            <Badge variant="outline" className="capitalize">
                                                {member.role}
                                            </Badge>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-center text-muted-foreground">Aucun membre récent</p>
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </CompanyLayout>
    );
}
