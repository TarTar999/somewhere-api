import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { CreditCard, RefreshCw } from 'lucide-react';

export default function SubscriptionExpired() {
    return (
        <>
            <Head title="Abonnement expiré" />

            <div className="flex min-h-screen items-center justify-center bg-muted/30 p-4">
                <Card className="w-full max-w-md">
                    <CardContent className="py-12 text-center">
                        <CreditCard className="mx-auto h-16 w-16 text-destructive" />
                        <h1 className="mt-4 text-xl font-bold">Abonnement expiré</h1>
                        <p className="mt-2 text-muted-foreground">
                            L'abonnement de cette entreprise a expiré. Renouvelez-le pour continuer à utiliser les fonctionnalités.
                        </p>
                        <div className="mt-6 flex flex-col gap-3">
                            <Link href="/company/subscription">
                                <Button className="w-full">
                                    <RefreshCw className="mr-2 h-4 w-4" />
                                    Renouveler l'abonnement
                                </Button>
                            </Link>
                            <Link href="/company/select">
                                <Button variant="outline" className="w-full">
                                    Changer d'entreprise
                                </Button>
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
