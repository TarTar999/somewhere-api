import { Link, usePage } from '@inertiajs/react';
import CompanyLayout from '@/layouts/company-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import { CreditCard, Calendar, Users, FileText, CheckCircle, AlertTriangle, RefreshCw } from 'lucide-react';
import type { CompanyRole, CompanySubscription, CompanyUsage, SubscriptionPlan } from '@/types/company';

interface Props {
    hasSubscription: boolean;
    subscription: (CompanySubscription & { priceFormatted?: string; periodStart?: string; periodEnd?: string }) | null;
    usage: CompanyUsage;
    plans: SubscriptionPlan[];
}

export default function SubscriptionIndex({ hasSubscription, subscription, usage, plans }: Props) {
    const { props } = usePage();
    const company = (props as { company?: { name: string; logo?: string } }).company || { name: 'Entreprise' };
    const userRole = ((props as { userRole?: CompanyRole }).userRole || 'admin') as CompanyRole;

    return (
        <CompanyLayout title="Abonnement" company={company} userRole={userRole}>
            <div className="space-y-6">
                {hasSubscription && subscription ? (
                    <>
                        {/* Current Subscription */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div className="flex items-center gap-3">
                                        <CreditCard className="h-6 w-6 text-primary" />
                                        <div>
                                            <CardTitle>Abonnement {subscription.planName}</CardTitle>
                                            <CardDescription>Votre plan actuel</CardDescription>
                                        </div>
                                    </div>
                                    <Badge
                                        variant={subscription.status === 'active' ? 'default' : 'destructive'}
                                        className="text-sm"
                                    >
                                        {subscription.status === 'active' ? (
                                            <>
                                                <CheckCircle className="mr-1 h-3 w-3" />
                                                Actif
                                            </>
                                        ) : (
                                            <>
                                                <AlertTriangle className="mr-1 h-3 w-3" />
                                                {subscription.status}
                                            </>
                                        )}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                                    <div>
                                        <p className="text-sm text-muted-foreground">Prix mensuel</p>
                                        <p className="text-2xl font-bold">{subscription.priceFormatted}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-muted-foreground">Période actuelle</p>
                                        <p className="font-medium">
                                            {subscription.periodStart} - {subscription.periodEnd}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-muted-foreground">Prochain renouvellement</p>
                                        <p className="font-medium">
                                            Dans {subscription.daysUntilRenewal} jour(s)
                                        </p>
                                    </div>
                                    <div className="flex items-end gap-2">
                                        <Link href="/company/subscription/plans">
                                            <Button variant="outline">Changer de plan</Button>
                                        </Link>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Usage Stats */}
                        <div className="grid gap-6 md:grid-cols-2">
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-lg">
                                        <Users className="h-5 w-5" />
                                        Membres
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-bold">
                                        {usage.members?.used || 0}/{usage.members?.limit || 0}
                                    </div>
                                    <Progress
                                        value={usage.members?.limit ? ((usage.members?.used || 0) / usage.members.limit) * 100 : 0}
                                        className="mt-3"
                                    />
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        {(usage.members?.limit || 0) - (usage.members?.used || 0)} places disponibles
                                    </p>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2 text-lg">
                                        <FileText className="h-5 w-5" />
                                        Documents ce mois
                                    </CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="text-3xl font-bold">
                                        {usage.documents?.used || 0}/{usage.documents?.limit || 0}
                                    </div>
                                    <Progress
                                        value={
                                            usage.documents?.limit ? ((usage.documents?.used || 0) / usage.documents.limit) * 100 : 0
                                        }
                                        className="mt-3"
                                    />
                                    <p className="mt-2 text-sm text-muted-foreground">
                                        {usage.documents?.remaining || 0} documents restants
                                    </p>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Renewal Warning */}
                        {subscription.daysUntilRenewal <= 7 && (
                            <Card className="border-orange-200 bg-orange-50">
                                <CardContent className="flex items-center justify-between py-6">
                                    <div className="flex items-center gap-4">
                                        <RefreshCw className="h-8 w-8 text-orange-500" />
                                        <div>
                                            <p className="font-medium">Renouvellement bientôt</p>
                                            <p className="text-sm text-muted-foreground">
                                                Votre abonnement sera renouvelé automatiquement le {subscription.periodEnd}
                                            </p>
                                        </div>
                                    </div>
                                    <Link href="/company/subscription/invoices">
                                        <Button variant="outline">Voir les paiements</Button>
                                    </Link>
                                </CardContent>
                            </Card>
                        )}

                        {/* Actions */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Actions</CardTitle>
                            </CardHeader>
                            <CardContent className="flex gap-4">
                                <Link href="/company/subscription/invoices">
                                    <Button variant="outline">Historique des paiements</Button>
                                </Link>
                            </CardContent>
                        </Card>
                    </>
                ) : (
                    /* No Subscription */
                    <Card>
                        <CardHeader>
                            <CardTitle>Aucun abonnement actif</CardTitle>
                            <CardDescription>
                                Souscrivez à un plan pour commencer à utiliser les fonctionnalités entreprise
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="grid gap-6 md:grid-cols-3">
                                {plans.map((plan) => (
                                    <Card key={plan.code} className="relative overflow-hidden">
                                        {plan.code === 'professional' && (
                                            <div className="absolute right-0 top-0 bg-primary px-3 py-1 text-xs text-primary-foreground">
                                                Populaire
                                            </div>
                                        )}
                                        <CardHeader>
                                            <CardTitle>{plan.name}</CardTitle>
                                            <div className="text-3xl font-bold">{plan.priceFormatted}</div>
                                        </CardHeader>
                                        <CardContent>
                                            <ul className="space-y-2">
                                                {plan.features.map((feature, i) => (
                                                    <li key={i} className="flex items-center gap-2 text-sm">
                                                        <CheckCircle className="h-4 w-4 text-green-500" />
                                                        {feature}
                                                    </li>
                                                ))}
                                            </ul>
                                            <Link href={`/company/subscription/plans?selected=${plan.code}`} className="mt-6 block">
                                                <Button className="w-full" variant={plan.code === 'professional' ? 'default' : 'outline'}>
                                                    Choisir ce plan
                                                </Button>
                                            </Link>
                                        </CardContent>
                                    </Card>
                                ))}
                            </div>
                        </CardContent>
                    </Card>
                )}
            </div>
        </CompanyLayout>
    );
}
