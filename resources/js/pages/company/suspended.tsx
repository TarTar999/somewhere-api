import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { AlertTriangle, Mail } from 'lucide-react';

export default function CompanySuspended() {
    return (
        <>
            <Head title="Entreprise suspendue" />

            <div className="flex min-h-screen items-center justify-center bg-muted/30 p-4">
                <Card className="w-full max-w-md">
                    <CardContent className="py-12 text-center">
                        <AlertTriangle className="mx-auto h-16 w-16 text-orange-500" />
                        <h1 className="mt-4 text-xl font-bold">Entreprise suspendue</h1>
                        <p className="mt-2 text-muted-foreground">
                            L'accès à cette entreprise a été temporairement suspendu. Veuillez contacter l'administrateur.
                        </p>
                        <div className="mt-6 flex flex-col gap-3">
                            <Link href="/company/select">
                                <Button className="w-full">Changer d'entreprise</Button>
                            </Link>
                            <a href="mailto:support@somewhere.cm">
                                <Button variant="outline" className="w-full">
                                    <Mail className="mr-2 h-4 w-4" />
                                    Contacter le support
                                </Button>
                            </a>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </>
    );
}
