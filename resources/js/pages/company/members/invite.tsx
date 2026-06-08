import { FormEvent, useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import CompanyLayout from '@/layouts/company-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { ArrowLeft, UserPlus, Shield, Users, User } from 'lucide-react';
import type { CompanyRole } from '@/types/company';

export default function InviteMember() {
    const { props, errors } = usePage();
    const company = (props as { company?: { name: string; logo?: string } }).company || { name: 'Entreprise' };
    const userRole = ((props as { userRole?: CompanyRole }).userRole || 'member') as CompanyRole;
    const isAdmin = userRole === 'admin';

    const [emailOrPhone, setEmailOrPhone] = useState('');
    const [role, setRole] = useState<CompanyRole>('member');
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);

        router.post(
            '/company/members/invite',
            { emailOrPhone, role },
            {
                onFinish: () => setIsSubmitting(false),
            }
        );
    };

    const roles = [
        {
            value: 'member',
            label: 'Membre',
            description: 'Peut créer des documents et gérer ses propres adresses',
            icon: User,
            available: true,
        },
        {
            value: 'manager',
            label: 'Manager',
            description: 'Peut inviter des membres et voir toutes les adresses',
            icon: Users,
            available: true,
        },
        {
            value: 'admin',
            label: 'Admin',
            description: 'Accès complet à toutes les fonctionnalités',
            icon: Shield,
            available: isAdmin,
        },
    ];

    return (
        <CompanyLayout title="Inviter un membre" company={company} userRole={userRole}>
            <div className="mx-auto max-w-2xl space-y-6">
                {/* Back Button */}
                <Link href="/company/members">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Retour aux membres
                    </Button>
                </Link>

                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <UserPlus className="h-5 w-5" />
                            Inviter un nouveau membre
                        </CardTitle>
                        <CardDescription>
                            Envoyez une invitation à un utilisateur existant pour rejoindre votre équipe.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            {/* Email or Phone */}
                            <div className="space-y-2">
                                <Label htmlFor="emailOrPhone">Email ou numéro de téléphone</Label>
                                <Input
                                    id="emailOrPhone"
                                    type="text"
                                    value={emailOrPhone}
                                    onChange={(e) => setEmailOrPhone(e.target.value)}
                                    placeholder="exemple@email.com ou 6XXXXXXXX"
                                    required
                                />
                                {(errors as { emailOrPhone?: string }).emailOrPhone && (
                                    <p className="text-sm text-destructive">{(errors as { emailOrPhone?: string }).emailOrPhone}</p>
                                )}
                                <p className="text-sm text-muted-foreground">
                                    L'utilisateur doit déjà avoir un compte SomeWhere.
                                </p>
                            </div>

                            {/* Role Selection */}
                            <div className="space-y-3">
                                <Label>Rôle</Label>
                                <RadioGroup value={role} onValueChange={(v) => setRole(v as CompanyRole)} className="space-y-3">
                                    {roles.map((roleOption) => {
                                        const Icon = roleOption.icon;
                                        return (
                                            <div key={roleOption.value}>
                                                <label
                                                    className={`flex cursor-pointer items-start gap-4 rounded-lg border p-4 transition-colors ${
                                                        role === roleOption.value
                                                            ? 'border-primary bg-primary/5'
                                                            : roleOption.available
                                                              ? 'hover:bg-muted'
                                                              : 'cursor-not-allowed opacity-50'
                                                    }`}
                                                >
                                                    <RadioGroupItem
                                                        value={roleOption.value}
                                                        disabled={!roleOption.available}
                                                        className="mt-1"
                                                    />
                                                    <div className="flex-1">
                                                        <div className="flex items-center gap-2">
                                                            <Icon className="h-4 w-4" />
                                                            <span className="font-medium">{roleOption.label}</span>
                                                        </div>
                                                        <p className="mt-1 text-sm text-muted-foreground">{roleOption.description}</p>
                                                    </div>
                                                </label>
                                            </div>
                                        );
                                    })}
                                </RadioGroup>
                                {(errors as { role?: string }).role && (
                                    <p className="text-sm text-destructive">{(errors as { role?: string }).role}</p>
                                )}
                            </div>

                            {/* Submit */}
                            <div className="flex justify-end gap-4">
                                <Link href="/company/members">
                                    <Button type="button" variant="outline">
                                        Annuler
                                    </Button>
                                </Link>
                                <Button type="submit" disabled={isSubmitting}>
                                    {isSubmitting ? 'Envoi en cours...' : 'Envoyer l\'invitation'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </CompanyLayout>
    );
}
