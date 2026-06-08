import { FormEvent, useState } from 'react';
import { router, usePage } from '@inertiajs/react';
import CompanyLayout from '@/layouts/company-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Building2, Save } from 'lucide-react';
import type { CompanyRole } from '@/types/company';

interface Company {
    id: number;
    name: string;
    email: string;
    phone?: string;
    legalName?: string;
    registrationNumber?: string;
    taxId?: string;
    description?: string;
    address?: string;
    city?: string;
    country?: string;
    logo?: string;
}

interface Props {
    company: Company;
}

export default function CompanySettings({ company }: Props) {
    const { errors } = usePage();
    const userRole = 'admin' as CompanyRole;

    const [formData, setFormData] = useState({
        name: company.name,
        email: company.email,
        phone: company.phone || '',
        legal_name: company.legalName || '',
        registration_number: company.registrationNumber || '',
        tax_id: company.taxId || '',
        description: company.description || '',
        address: company.address || '',
        city: company.city || '',
    });
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleChange = (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) => {
        setFormData({ ...formData, [e.target.name]: e.target.value });
    };

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        router.put('/company/settings', formData, {
            onFinish: () => setIsSubmitting(false),
        });
    };

    return (
        <CompanyLayout title="Paramètres" company={{ name: company.name, logo: company.logo }} userRole={userRole}>
            <form onSubmit={handleSubmit} className="space-y-6">
                {/* Basic Info */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Building2 className="h-5 w-5" />
                            Informations générales
                        </CardTitle>
                        <CardDescription>Modifiez les informations de base de votre entreprise</CardDescription>
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
                                    required
                                />
                                {(errors as Record<string, string>).email && (
                                    <p className="text-sm text-destructive">{(errors as Record<string, string>).email}</p>
                                )}
                            </div>
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="phone">Téléphone</Label>
                            <Input id="phone" name="phone" value={formData.phone} onChange={handleChange} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="description">Description</Label>
                            <Textarea
                                id="description"
                                name="description"
                                value={formData.description}
                                onChange={handleChange}
                                rows={3}
                            />
                        </div>
                    </CardContent>
                </Card>

                {/* Legal Info */}
                <Card>
                    <CardHeader>
                        <CardTitle>Informations légales</CardTitle>
                        <CardDescription>Ces informations apparaissent sur vos documents</CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="legal_name">Raison sociale</Label>
                            <Input
                                id="legal_name"
                                name="legal_name"
                                value={formData.legal_name}
                                onChange={handleChange}
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
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="tax_id">NIU</Label>
                                <Input
                                    id="tax_id"
                                    name="tax_id"
                                    value={formData.tax_id}
                                    onChange={handleChange}
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* Address */}
                <Card>
                    <CardHeader>
                        <CardTitle>Adresse</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="address">Adresse</Label>
                            <Input id="address" name="address" value={formData.address} onChange={handleChange} />
                        </div>
                        <div className="space-y-2">
                            <Label htmlFor="city">Ville</Label>
                            <Input id="city" name="city" value={formData.city} onChange={handleChange} />
                        </div>
                    </CardContent>
                </Card>

                {/* Submit */}
                <div className="flex justify-end">
                    <Button type="submit" disabled={isSubmitting}>
                        <Save className="mr-2 h-4 w-4" />
                        {isSubmitting ? 'Enregistrement...' : 'Enregistrer les modifications'}
                    </Button>
                </div>
            </form>
        </CompanyLayout>
    );
}
