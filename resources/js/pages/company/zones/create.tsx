import { useState } from 'react';
import CompanyLayout from '@/layouts/company-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ArrowLeft, Save } from 'lucide-react';
import { Link, useForm } from '@inertiajs/react';
import type { CompanyRole } from '@/types/company';

interface LabelItem {
    id: number;
    name: string;
    color: string;
    icon: string | null;
}

interface ParentZone {
    id: number;
    name: string;
}

interface Props {
    company: { id: number; name: string; logo?: string; status: string };
    userRole: CompanyRole;
    labels: LabelItem[];
    parentZones: ParentZone[];
    defaultColors: string[];
}

export default function ZonesCreate({ company, userRole, labels, parentZones, defaultColors }: Props) {
    const form = useForm({
        name: '',
        description: '',
        zone_type: 'circle',
        center_lat: 3.848,
        center_lng: 11.502,
        radius_meters: 500,
    });

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.post('/company/zones');
    };

    return (
        <CompanyLayout title="Nouvelle zone" company={company} userRole={userRole}>
            <form onSubmit={handleSubmit} className="space-y-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/company/zones">
                                <ArrowLeft className="h-5 w-5" />
                            </Link>
                        </Button>
                        <div>
                            <h2 className="text-2xl font-bold tracking-tight">Créer une zone</h2>
                            <p className="text-muted-foreground">Version simplifiée pour test</p>
                        </div>
                    </div>
                    <Button type="submit" disabled={form.processing}>
                        <Save className="mr-2 h-4 w-4" />
                        Créer la zone
                    </Button>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Informations</CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="name">Nom de la zone</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                                placeholder="Ex: Zone Nord"
                            />
                        </div>
                        <div className="text-sm text-muted-foreground">
                            <p>Labels disponibles: {labels.length}</p>
                            <p>Zones parentes disponibles: {parentZones.length}</p>
                            <p>Couleurs: {defaultColors.join(', ')}</p>
                        </div>
                    </CardContent>
                </Card>
            </form>
        </CompanyLayout>
    );
}
