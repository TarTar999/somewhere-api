import { FormEvent, useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Building2, ArrowLeft } from 'lucide-react';
import type { SubscriptionPlan } from '@/types/company';

interface Props {
    plans: SubscriptionPlan[];
}

export default function CreateCompany({ plans }: Props) {
    const { errors } = usePage().props;

    const [formData, setFormData] = useState({
        name: '',
        email: '',
        phone: '',
        legal_name: '',
        registration_number: '',
        tax_id: '',
        description: '',
        address: '',
        city: '',
    });
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        router.post('/company/create', formData, {
            onFinish: () => setIsSubmitting(false),
        });
    };

    return (
        <>
            <Head title="Créer une entreprise" />

            <div className="min-h-screen bg-muted/30 py-8">
                <div className="mx-auto max-w-3xl px-4">
                    {/* Back Button */}
                    <Link href="/company/select">
                        <Button variant="ghost" size="sm" className="mb-6">
                            <ArrowLeft className="mr-2 h-4 w-4" />
                            Retour
                        </Button>
                    </Link>

                    {/* Header */}
                    <div className="mb-8 text-center">
                        <Building2 className="mx-auto h-12 w-12 text-primary" />
                        <h1 className="mt-4 text-2xl font-bold">Créer votre entreprise</h1>
                        <p className="mt-2 text-muted-foreground">
                            Remplissez les informations de votre entreprise pour commencer
                        </p>
                    </div>

                    <form onSubmit={handleSubmit}>
                        {/* Basic Info */}
                        <Card className="mb-6">
                            <CardHeader>
                                <CardTitle>Informations générales</CardTitle>
                                <CardDescription>Les informations de base de votre entreprise</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="name">Nom de l'entreprise *</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            value={formData.name}
                                            onChange={handleChange}
                                            placeholder="Mon Entreprise"
                                            required
                                        />
                                        {(errors as Record<string, string>).name && (
                                            <p className="text-sm text-destructive">{(errors as Record<string, string>).name}</p>
                                        )}
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="email">Email *</Label>
                                        <Input
                                            id="email"
                                            name="email"
                                            type="email"
                                            value={formData.email}
                                            onChange={handleChange}
                                            placeholder="contact@entreprise.com"
                                            required
                                        />
                                        {(errors as Record<string, string>).email && (
                                            <p className="text-sm text-destructive">{(errors as Record<string, string>).email}</p>
                                        )}
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="phone">Téléphone</Label>
                                    <Input
                                        id="phone"
                                        name="phone"
                                        value={formData.phone}
                                        onChange={handleChange}
                                        placeholder="6XXXXXXXX"
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="description">Description</Label>
                                    <Textarea
                                        id="description"
                                        name="description"
                                        value={formData.description}
                                        onChange={handleChange}
                                        placeholder="Décrivez brièvement votre entreprise..."
                                        rows={3}
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        {/* Legal Info */}
                        <Card className="mb-6">
                            <CardHeader>
                                <CardTitle>Informations légales</CardTitle>
                                <CardDescription>Optionnel - Ces informations apparaîtront sur vos documents</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="legal_name">Raison sociale</Label>
                                    <Input
                                        id="legal_name"
                                        name="legal_name"
                                        value={formData.legal_name}
                                        onChange={handleChange}
                                        placeholder="Nom légal complet"
                                    />
                                </div>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="space-y-2">
                                        <Label htmlFor="registration_number">Numéro RCCM</Label>
                                        <Input
                                            id="registration_number"
                                            name="registration_number"
                                            value={formData.registration_number}
                                            onChange={handleChange}
                                            placeholder="RC/XXX/XXXX"
                                        />
                                    </div>
                                    <div className="space-y-2">
                                        <Label htmlFor="tax_id">NIU (Numéro d'Identification Unique)</Label>
                                        <Input
                                            id="tax_id"
                                            name="tax_id"
                                            value={formData.tax_id}
                                            onChange={handleChange}
                                            placeholder="XXXXXXXXXXXXXXX"
                                        />
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {/* Address */}
                        <Card className="mb-6">
                            <CardHeader>
                                <CardTitle>Adresse</CardTitle>
                                <CardDescription>L'adresse physique de votre entreprise</CardDescription>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="address">Adresse</Label>
                                    <Input
                                        id="address"
                                        name="address"
                                        value={formData.address}
                                        onChange={handleChange}
                                        placeholder="Rue, quartier, boîte postale..."
                                    />
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="city">Ville</Label>
                                    <Input
                                        id="city"
                                        name="city"
                                        value={formData.city}
                                        onChange={handleChange}
                                        placeholder="Yaoundé, Douala..."
                                    />
                                </div>
                            </CardContent>
                        </Card>

                        {/* Plans Preview */}
                        <Card className="mb-6">
                            <CardHeader>
                                <CardTitle>Plans disponibles</CardTitle>
                                <CardDescription>
                                    Après la création, vous pourrez choisir un plan d'abonnement
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="grid gap-4 sm:grid-cols-3">
                                    {plans.map((plan) => (
                                        <div key={plan.code} className="rounded-lg border p-4">
                                            <h4 className="font-semibold">{plan.name}</h4>
                                            <p className="text-lg font-bold text-primary">{plan.priceFormatted}</p>
                                            <ul className="mt-2 space-y-1 text-sm text-muted-foreground">
                                                <li>{plan.maxMembers} membres</li>
                                                <li>{plan.documentsPerMonth} docs/mois</li>
                                            </ul>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>

                        {/* Submit */}
                        <div className="flex justify-end gap-4">
                            <Link href="/company/select">
                                <Button type="button" variant="outline">
                                    Annuler
                                </Button>
                            </Link>
                            <Button type="submit" disabled={isSubmitting}>
                                {isSubmitting ? 'Création en cours...' : 'Créer l\'entreprise'}
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
