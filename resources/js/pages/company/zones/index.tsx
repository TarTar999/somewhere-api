import { useState, useMemo } from 'react';
import CompanyLayout from '@/layouts/company-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Plus, Search, Eye, Edit2, Trash2, Copy, Circle, Hexagon, Shapes, MapPin } from 'lucide-react';
import { Link, router } from '@inertiajs/react';
import type { CompanyRole } from '@/types/company';

interface LabelItem {
    id: number;
    name: string;
    color: string;
    icon: string | null;
}

interface ZoneItem {
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
    labels: LabelItem[];
    createdAt: string;
}

interface Props {
    company: { id: number; name: string; logo?: string; status: string };
    userRole: CompanyRole;
    zones: ZoneItem[];
    labels: LabelItem[];
    filters: { status?: string; zone_type?: string; search?: string };
    stats: {
        total: number;
        active: number;
        inactive: number;
        circles: number;
        polygons: number;
    };
}

export default function ZonesIndex({ company, userRole, zones, labels, filters, stats }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || 'all');
    const [zoneType, setZoneType] = useState(filters.zone_type || 'all');
    const [deletingZone, setDeletingZone] = useState<ZoneItem | null>(null);

    const filteredZones = useMemo(() => {
        return zones.filter((zone) => {
            const matchesSearch =
                zone.name.toLowerCase().includes(search.toLowerCase()) ||
                (zone.description?.toLowerCase() || '').includes(search.toLowerCase());
            const matchesStatus = status === 'all' || zone.status === status;
            const matchesType = zoneType === 'all' || zone.zoneType === zoneType;
            return matchesSearch && matchesStatus && matchesType;
        });
    }, [zones, search, status, zoneType]);

    const handleFilter = () => {
        router.get(
            '/company/zones',
            {
                ...(search && { search }),
                ...(status !== 'all' && { status }),
                ...(zoneType !== 'all' && { zone_type: zoneType }),
            },
            { preserveState: true }
        );
    };

    const handleDelete = () => {
        if (!deletingZone) return;
        router.delete(`/company/zones/${deletingZone.id}`, {
            onSuccess: () => setDeletingZone(null),
        });
    };

    const handleDuplicate = (zone: ZoneItem) => {
        router.post(`/company/zones/${zone.id}/duplicate`);
    };

    const getStatusBadge = (zoneStatus: string) => {
        switch (zoneStatus) {
            case 'active':
                return <Badge className="bg-green-100 text-green-800">Actif</Badge>;
            case 'inactive':
                return <Badge className="bg-gray-100 text-gray-800">Inactif</Badge>;
            case 'archived':
                return <Badge className="bg-red-100 text-red-800">Archivé</Badge>;
            default:
                return <Badge variant="secondary">{zoneStatus}</Badge>;
        }
    };

    return (
        <CompanyLayout title="Zones" company={company} userRole={userRole}>
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight">Zones géographiques</h2>
                        <p className="text-muted-foreground">Créez et gérez vos zones d'intervention</p>
                    </div>
                    <Button asChild>
                        <Link href="/company/zones/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Nouvelle zone
                        </Link>
                    </Button>
                </div>

                {/* Stats */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium">Total</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.total}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium text-green-600">Actives</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold text-green-600">{stats.active}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium flex items-center gap-2">
                                <Circle className="h-4 w-4" />
                                Cercles
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.circles}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="pb-2">
                            <CardTitle className="text-sm font-medium flex items-center gap-2">
                                <Hexagon className="h-4 w-4" />
                                Polygones
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.polygons}</div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <div className="flex flex-wrap gap-4">
                    <div className="relative flex-1 min-w-[200px]">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Rechercher une zone..."
                            className="pl-9"
                        />
                    </div>
                    <Select value={status} onValueChange={setStatus}>
                        <SelectTrigger className="w-[150px]">
                            <SelectValue placeholder="Statut" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Tous les statuts</SelectItem>
                            <SelectItem value="active">Actif</SelectItem>
                            <SelectItem value="inactive">Inactif</SelectItem>
                            <SelectItem value="archived">Archivé</SelectItem>
                        </SelectContent>
                    </Select>
                    <Select value={zoneType} onValueChange={setZoneType}>
                        <SelectTrigger className="w-[150px]">
                            <SelectValue placeholder="Type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">Tous les types</SelectItem>
                            <SelectItem value="circle">Cercle</SelectItem>
                            <SelectItem value="polygon">Polygone</SelectItem>
                        </SelectContent>
                    </Select>
                    <Button variant="secondary" onClick={handleFilter}>
                        Filtrer
                    </Button>
                </div>

                {/* Zones Grid */}
                {filteredZones.length > 0 ? (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {filteredZones.map((zone) => (
                            <Card key={zone.id} className="relative overflow-hidden">
                                <div
                                    className="absolute inset-x-0 top-0 h-1"
                                    style={{ backgroundColor: zone.fillColor }}
                                />
                                <CardHeader className="pb-2">
                                    <div className="flex items-start justify-between">
                                        <div className="flex items-center gap-2">
                                            {zone.zoneType === 'circle' ? (
                                                <Circle className="h-4 w-4 text-muted-foreground" />
                                            ) : (
                                                <Hexagon className="h-4 w-4 text-muted-foreground" />
                                            )}
                                            <CardTitle className="text-base">{zone.name}</CardTitle>
                                        </div>
                                        {getStatusBadge(zone.status)}
                                    </div>
                                    {zone.description && (
                                        <CardDescription className="mt-1 line-clamp-2">{zone.description}</CardDescription>
                                    )}
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {/* Zone Info */}
                                    <div className="text-sm text-muted-foreground">
                                        {zone.zoneType === 'circle' && zone.radiusMeters && (
                                            <span>Rayon: {zone.radiusMeters >= 1000 ? `${(zone.radiusMeters / 1000).toFixed(1)} km` : `${zone.radiusMeters} m`}</span>
                                        )}
                                        {zone.zoneType === 'polygon' && zone.polygonCoordinates && (
                                            <span>{zone.polygonCoordinates.length} points</span>
                                        )}
                                    </div>

                                    {/* Labels */}
                                    {zone.labels.length > 0 && (
                                        <div className="flex flex-wrap gap-1">
                                            {zone.labels.map((label) => (
                                                <Badge
                                                    key={label.id}
                                                    variant="outline"
                                                    className="text-xs"
                                                    style={{ borderColor: label.color, color: label.color }}
                                                >
                                                    {label.name}
                                                </Badge>
                                            ))}
                                        </div>
                                    )}

                                    {/* Actions */}
                                    <div className="flex gap-1 pt-2">
                                        <Button variant="ghost" size="sm" asChild>
                                            <Link href={`/company/zones/${zone.id}`}>
                                                <Eye className="mr-1 h-4 w-4" />
                                                Voir
                                            </Link>
                                        </Button>
                                        <Button variant="ghost" size="sm" asChild>
                                            <Link href={`/company/zones/${zone.id}/edit`}>
                                                <Edit2 className="mr-1 h-4 w-4" />
                                                Modifier
                                            </Link>
                                        </Button>
                                        <Button variant="ghost" size="sm" onClick={() => handleDuplicate(zone)}>
                                            <Copy className="mr-1 h-4 w-4" />
                                            Dupliquer
                                        </Button>
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            className="text-destructive hover:text-destructive"
                                            onClick={() => setDeletingZone(zone)}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                ) : (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <Shapes className="h-12 w-12 text-muted-foreground/50" />
                            <p className="mt-4 text-center text-muted-foreground">
                                {search || status !== 'all' || zoneType !== 'all'
                                    ? 'Aucune zone trouvée avec ces filtres'
                                    : 'Aucune zone créée'}
                            </p>
                            {!search && status === 'all' && zoneType === 'all' && (
                                <Button variant="outline" className="mt-4" asChild>
                                    <Link href="/company/zones/create">
                                        <Plus className="mr-2 h-4 w-4" />
                                        Créer votre première zone
                                    </Link>
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                )}

                {/* Delete Confirmation */}
                <AlertDialog open={!!deletingZone} onOpenChange={(open) => !open && setDeletingZone(null)}>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Supprimer la zone ?</AlertDialogTitle>
                            <AlertDialogDescription>
                                Cette action est irréversible. La zone "{deletingZone?.name}" sera définitivement supprimée.
                                Les zones enfants seront rattachées à la zone parente.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Annuler</AlertDialogCancel>
                            <AlertDialogAction onClick={handleDelete} className="bg-destructive text-destructive-foreground">
                                Supprimer
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </div>
        </CompanyLayout>
    );
}
