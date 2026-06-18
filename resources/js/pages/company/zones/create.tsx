import { useState, useCallback } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { motion } from 'framer-motion';
import { ArrowLeft, Pentagon, Info } from 'lucide-react';
import CompanyLayout from '@/layouts/company-layout';
import { ZoneEditor, Zone } from '@/components/map/zone-editor';
import { Button } from '@/components/ui/button';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
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
    existingZones?: Zone[];
}

export default function ZonesCreate({
    company,
    userRole,
    labels,
    parentZones,
    defaultColors,
    existingZones = [],
}: Props) {
    const [isSaving, setIsSaving] = useState(false);

    // Handle save
    const handleSave = useCallback((zones: Zone[]) => {
        setIsSaving(true);

        // Convert zones to the format expected by the backend
        const zonesToCreate = zones.filter(z => !z.id); // Only new zones

        if (zonesToCreate.length === 0) {
            router.visit('/company/zones');
            return;
        }

        // Create each zone
        const promises = zonesToCreate.map(zone => {
            const data: Record<string, any> = {
                name: zone.name,
                zone_type: zone.type,
                color: zone.color,
                opacity: zone.opacity,
                is_active: zone.is_active,
            };

            if (zone.type === 'circle' && 'radius' in zone.coordinates) {
                data.center_lat = zone.coordinates.lat;
                data.center_lng = zone.coordinates.lng;
                data.radius_meters = zone.coordinates.radius;
            } else if (Array.isArray(zone.coordinates)) {
                data.polygon_coordinates = JSON.stringify(zone.coordinates);
            }

            return new Promise((resolve, reject) => {
                router.post('/company/zones', data, {
                    preserveState: false,
                    onSuccess: resolve,
                    onError: reject,
                });
            });
        });

        Promise.all(promises)
            .then(() => {
                router.visit('/company/zones');
            })
            .catch(() => {
                setIsSaving(false);
            });
    }, []);

    // Handle cancel
    const handleCancel = useCallback(() => {
        router.visit('/company/zones');
    }, []);

    // Map center (Cameroon - Yaoundé)
    const mapCenter: [number, number] = [3.848, 11.502];

    return (
        <>
            <Head title="Créer des zones" />

            {/* Full-screen zone editor */}
            <div className="fixed inset-0 z-50 bg-white">
                {/* Header overlay */}
                <div className="absolute top-0 left-0 right-0 z-[1001] bg-white/95 backdrop-blur-md border-b">
                    <div className="flex items-center justify-between p-4">
                        <div className="flex items-center gap-4">
                            <Link href="/company/zones">
                                <Button variant="ghost" size="icon">
                                    <ArrowLeft className="h-5 w-5" />
                                </Button>
                            </Link>
                            <div>
                                <h1 className="text-xl font-bold flex items-center gap-2">
                                    <Pentagon className="h-5 w-5" />
                                    Créer des zones
                                </h1>
                                <p className="text-sm text-muted-foreground">
                                    {company.name} - Dessinez vos zones de couverture
                                </p>
                            </div>
                        </div>

                        {isSaving && (
                            <div className="flex items-center gap-2 text-sm text-muted-foreground">
                                <motion.div
                                    animate={{ rotate: 360 }}
                                    transition={{ duration: 1, repeat: Infinity, ease: "linear" }}
                                    className="w-4 h-4 border-2 border-sw-black border-t-transparent rounded-full"
                                />
                                Enregistrement...
                            </div>
                        )}
                    </div>
                </div>

                {/* Help banner */}
                <div className="absolute top-20 left-1/2 -translate-x-1/2 z-[1001] max-w-md">
                    <Alert className="bg-sw-info/10 border-sw-info/20">
                        <Info className="h-4 w-4 text-sw-info" />
                        <AlertTitle>Comment créer une zone ?</AlertTitle>
                        <AlertDescription className="text-xs">
                            1. Sélectionnez un outil (polygone ou cercle) dans la barre d'outils<br />
                            2. Dessinez la zone sur la carte<br />
                            3. Personnalisez les paramètres dans le panneau de droite<br />
                            4. Cliquez sur "Enregistrer" pour sauvegarder
                        </AlertDescription>
                    </Alert>
                </div>

                {/* Zone Editor */}
                <div className="pt-[72px] h-full">
                    <ZoneEditor
                        zones={existingZones}
                        center={mapCenter}
                        zoom={13}
                        onSave={handleSave}
                        onCancel={handleCancel}
                        className="h-full"
                    />
                </div>
            </div>
        </>
    );
}
