import CompanyLayout from '@/layouts/company-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, Edit2, Copy, Trash2, Circle, Hexagon, Calendar, User, Layers } from 'lucide-react';
import { Link, router } from '@inertiajs/react';
import type { CompanyRole } from '@/types/company';
import { ZoneMapView } from '@/components/map/zone-map-view';

interface LabelItem {
    id: number;
    name: string;
    color: string;
    icon: string | null;
}

interface ZoneChild {
    id: number;
    name: string;
    status: string;
}

interface ZoneData {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    zoneType: 'circle' | 'polygon';
    status: 'active' | 'inactive' | 'archived';
    centerLat: number | null;
    centerLng: number | null;
    radiusMeters: number | null;
    polygonCoordinates: Array<{ lat: number; lng: number }> | null;
    fillColor: string;
    fillOpacity: number;
    strokeColor: string;
    strokeWidth: number;
    parentZoneId: number | null;
    parent: { id: number; name: string } | null;
    children: ZoneChild[];
    creator: { id: number; name: string } | null;
    labels: LabelItem[];
    metadata: Record<string, unknown> | null;
    createdAt: string;
    updatedAt: string;
}

interface Statistics {
    id: number;
    name: string;
    zone_type: string;
    area_sqm: number;
    area_sqkm: number;
    children_count: number;
    labels_count: number;
    bounding_box: {
        north: number;
        south: number;
        east: number;
        west: number;
    };
}

interface Props {
    company: { id: number; name: string; logo?: string; status: string };
    userRole: CompanyRole;
    zone: ZoneData;
    statistics: Statistics;
    geoJson: unknown;
}

export default function ZonesShow({ company, userRole, zone, statistics }: Props) {
    const handleDuplicate = () => {
        router.post(`/company/zones/${zone.id}/duplicate`);
    };

    const handleDelete = () => {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette zone ?')) {
            router.delete(`/company/zones/${zone.id}`);
        }
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'active':
                return <Badge className="bg-green-100 text-green-800">Actif</Badge>;
            case 'inactive':
                return <Badge className="bg-gray-100 text-gray-800">Inactif</Badge>;
            case 'archived':
                return <Badge className="bg-red-100 text-red-800">Archivé</Badge>;
            default:
                return <Badge variant="secondary">{status}</Badge>;
        }
    };

    const formatArea = (sqm: number) => {
        if (sqm >= 1_000_000) {
            return `${(sqm / 1_000_000).toFixed(2)} km²`;
        } else if (sqm >= 10_000) {
            return `${(sqm / 10_000).toFixed(2)} ha`;
        }
        return `${sqm.toFixed(0)} m²`;
    };

    return (
        <CompanyLayout title={zone.name} company={company} userRole={userRole}>
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/company/zones">
                                <ArrowLeft className="h-5 w-5" />
                            </Link>
                        </Button>
                        <div>
                            <div className="flex items-center gap-3">
                                {zone.zoneType === 'circle' ? (
                                    <Circle className="h-6 w-6 text-muted-foreground" />
                                ) : (
                                    <Hexagon className="h-6 w-6 text-muted-foreground" />
                                )}
                                <h2 className="text-2xl font-bold tracking-tight">{zone.name}</h2>
                                {getStatusBadge(zone.status)}
                            </div>
                            {zone.description && <p className="mt-1 text-muted-foreground">{zone.description}</p>}
                        </div>
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={handleDuplicate}>
                            <Copy className="mr-2 h-4 w-4" />
                            Dupliquer
                        </Button>
                        <Button variant="outline" asChild>
                            <Link href={`/company/zones/${zone.id}/edit`}>
                                <Edit2 className="mr-2 h-4 w-4" />
                                Modifier
                            </Link>
                        </Button>
                        <Button variant="destructive" onClick={handleDelete}>
                            <Trash2 className="mr-2 h-4 w-4" />
                            Supprimer
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Map */}
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle>Carte</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="h-[400px] rounded-lg overflow-hidden border">
                                <ZoneMapView
                                    zoneType={zone.zoneType}
                                    centerLat={zone.centerLat}
                                    centerLng={zone.centerLng}
                                    radiusMeters={zone.radiusMeters}
                                    polygonCoordinates={zone.polygonCoordinates}
                                    fillColor={zone.fillColor}
                                    fillOpacity={zone.fillOpacity}
                                    strokeColor={zone.strokeColor}
                                    strokeWidth={zone.strokeWidth}
                                />
                            </div>
                        </CardContent>
                    </Card>

                    {/* Info */}
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Statistiques</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex items-center justify-between">
                                    <span className="text-muted-foreground">Type</span>
                                    <span className="font-medium capitalize">{zone.zoneType}</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-muted-foreground">Surface</span>
                                    <span className="font-medium">{formatArea(statistics.area_sqm)}</span>
                                </div>
                                {zone.zoneType === 'circle' && zone.radiusMeters && (
                                    <div className="flex items-center justify-between">
                                        <span className="text-muted-foreground">Rayon</span>
                                        <span className="font-medium">
                                            {zone.radiusMeters >= 1000
                                                ? `${(zone.radiusMeters / 1000).toFixed(1)} km`
                                                : `${zone.radiusMeters} m`}
                                        </span>
                                    </div>
                                )}
                                {zone.zoneType === 'polygon' && zone.polygonCoordinates && (
                                    <div className="flex items-center justify-between">
                                        <span className="text-muted-foreground">Points</span>
                                        <span className="font-medium">{zone.polygonCoordinates.length}</span>
                                    </div>
                                )}
                                <div className="flex items-center justify-between">
                                    <span className="text-muted-foreground">Sous-zones</span>
                                    <span className="font-medium">{statistics.children_count}</span>
                                </div>
                                <div className="flex items-center justify-between">
                                    <span className="text-muted-foreground">Labels</span>
                                    <span className="font-medium">{statistics.labels_count}</span>
                                </div>
                            </CardContent>
                        </Card>

                        {zone.labels.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Labels</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex flex-wrap gap-2">
                                        {zone.labels.map((label) => (
                                            <Badge key={label.id} style={{ backgroundColor: label.color }} className="text-white">
                                                {label.name}
                                            </Badge>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        {zone.parent && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Zone parente</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <Link href={`/company/zones/${zone.parent.id}`} className="flex items-center gap-2 hover:underline">
                                        <Layers className="h-4 w-4" />
                                        {zone.parent.name}
                                    </Link>
                                </CardContent>
                            </Card>
                        )}

                        {zone.children.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Sous-zones</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="space-y-2">
                                        {zone.children.map((child) => (
                                            <Link
                                                key={child.id}
                                                href={`/company/zones/${child.id}`}
                                                className="flex items-center justify-between rounded-lg border p-2 hover:bg-muted"
                                            >
                                                <span>{child.name}</span>
                                                {getStatusBadge(child.status)}
                                            </Link>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}

                        <Card>
                            <CardHeader>
                                <CardTitle>Métadonnées</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3 text-sm">
                                {zone.creator && (
                                    <div className="flex items-center gap-2 text-muted-foreground">
                                        <User className="h-4 w-4" />
                                        Créé par {zone.creator.name}
                                    </div>
                                )}
                                <div className="flex items-center gap-2 text-muted-foreground">
                                    <Calendar className="h-4 w-4" />
                                    Créé le {new Date(zone.createdAt).toLocaleDateString('fr-FR')}
                                </div>
                                <div className="flex items-center gap-2 text-muted-foreground">
                                    <Calendar className="h-4 w-4" />
                                    Modifié le {new Date(zone.updatedAt).toLocaleDateString('fr-FR')}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </CompanyLayout>
    );
}
