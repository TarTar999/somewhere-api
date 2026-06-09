import { useState, useCallback, useEffect } from 'react';
import CompanyLayout from '@/layouts/company-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Slider } from '@/components/ui/slider';
import { Tabs, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Circle, Hexagon, ArrowLeft, Save, Undo2 } from 'lucide-react';
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
    labels: LabelItem[];
    metadata: Record<string, unknown> | null;
    createdAt: string;
    updatedAt: string;
}

interface Props {
    company: { id: number; name: string; logo?: string; status: string };
    userRole: CompanyRole;
    zone: ZoneData;
    labels: LabelItem[];
    parentZones: ParentZone[];
    defaultColors: string[];
}

// Lazy load map component to avoid SSR issues
interface ZoneMapProps {
    zoneType: 'circle' | 'polygon';
    circleCenter: { lat: number; lng: number } | null;
    polygonPoints: Array<{ lat: number; lng: number }>;
    radiusMeters: number;
    fillColor: string;
    fillOpacity: number;
    strokeColor: string;
    strokeWidth: number;
    onCircleClick: (lat: number, lng: number) => void;
    onPolygonClick: (lat: number, lng: number) => void;
}

function ZoneMap({
    zoneType,
    circleCenter,
    polygonPoints,
    radiusMeters,
    fillColor,
    fillOpacity,
    strokeColor,
    strokeWidth,
    onCircleClick,
    onPolygonClick,
}: ZoneMapProps) {
    const [MapComponents, setMapComponents] = useState<{
        MapContainer: any;
        TileLayer: any;
        Circle: any;
        Polygon: any;
        useMapEvents: any;
    } | null>(null);

    useEffect(() => {
        Promise.all([
            import('react-leaflet'),
            import('leaflet/dist/leaflet.css'),
        ]).then(([mod]) => {
            setMapComponents({
                MapContainer: mod.MapContainer,
                TileLayer: mod.TileLayer,
                Circle: mod.Circle,
                Polygon: mod.Polygon,
                useMapEvents: mod.useMapEvents,
            });
        }).catch((err) => {
            console.error('Failed to load map components:', err);
        });
    }, []);

    if (!MapComponents) {
        return (
            <div className="h-full w-full flex items-center justify-center bg-muted">
                <p className="text-muted-foreground">Chargement de la carte...</p>
            </div>
        );
    }

    const { MapContainer, TileLayer, Circle: LeafletCircle, Polygon: LeafletPolygon } = MapComponents;

    // Calculate center
    let center: [number, number] = [3.848, 11.502];
    if (circleCenter) {
        center = [circleCenter.lat, circleCenter.lng];
    } else if (polygonPoints.length > 0) {
        const avgLat = polygonPoints.reduce((sum, p) => sum + p.lat, 0) / polygonPoints.length;
        const avgLng = polygonPoints.reduce((sum, p) => sum + p.lng, 0) / polygonPoints.length;
        center = [avgLat, avgLng];
    }

    return (
        <MapContainer
            center={center}
            zoom={14}
            className="h-full w-full"
            scrollWheelZoom={true}
        >
            <TileLayer
                attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
            />
            <MapClickHandlerInner
                zoneType={zoneType}
                onCircleClick={onCircleClick}
                onPolygonClick={onPolygonClick}
                useMapEvents={MapComponents.useMapEvents}
            />

            {zoneType === 'circle' && circleCenter && (
                <LeafletCircle
                    center={[circleCenter.lat, circleCenter.lng]}
                    radius={radiusMeters}
                    pathOptions={{
                        fillColor,
                        fillOpacity,
                        color: strokeColor,
                        weight: strokeWidth,
                    }}
                />
            )}

            {zoneType === 'polygon' && polygonPoints.length >= 3 && (
                <LeafletPolygon
                    positions={polygonPoints.map((p) => [p.lat, p.lng])}
                    pathOptions={{
                        fillColor,
                        fillOpacity,
                        color: strokeColor,
                        weight: strokeWidth,
                    }}
                />
            )}
        </MapContainer>
    );
}

function MapClickHandlerInner({
    zoneType,
    onCircleClick,
    onPolygonClick,
    useMapEvents,
}: {
    zoneType: 'circle' | 'polygon';
    onCircleClick: (lat: number, lng: number) => void;
    onPolygonClick: (lat: number, lng: number) => void;
    useMapEvents: any;
}) {
    useMapEvents({
        click(e: any) {
            if (zoneType === 'circle') {
                onCircleClick(e.latlng.lat, e.latlng.lng);
            } else {
                onPolygonClick(e.latlng.lat, e.latlng.lng);
            }
        },
    });
    return null;
}

export default function ZonesEdit({ company, userRole, zone, labels, parentZones, defaultColors }: Props) {
    const [zoneType, setZoneType] = useState<'circle' | 'polygon'>(zone.zoneType);
    const [circleCenter, setCircleCenter] = useState<{ lat: number; lng: number } | null>(
        zone.centerLat && zone.centerLng ? { lat: zone.centerLat, lng: zone.centerLng } : null
    );
    const [polygonPoints, setPolygonPoints] = useState<Array<{ lat: number; lng: number }>>(zone.polygonCoordinates || []);

    const form = useForm({
        name: zone.name,
        description: zone.description || '',
        zone_type: zone.zoneType,
        parent_zone_id: zone.parentZoneId ? String(zone.parentZoneId) : '',
        center_lat: zone.centerLat,
        center_lng: zone.centerLng,
        radius_meters: zone.radiusMeters || 500,
        polygon_coordinates: zone.polygonCoordinates || [],
        fill_color: zone.fillColor,
        fill_opacity: zone.fillOpacity,
        stroke_color: zone.strokeColor,
        stroke_width: zone.strokeWidth,
        status: zone.status,
        label_ids: zone.labels.map((l) => l.id),
    });

    const handleCircleClick = useCallback((lat: number, lng: number) => {
        const center = { lat, lng };
        setCircleCenter(center);
        form.setData({
            ...form.data,
            center_lat: lat,
            center_lng: lng,
        });
    }, [form]);

    const handlePolygonClick = useCallback((lat: number, lng: number) => {
        const newPoint = { lat, lng };
        const newPoints = [...polygonPoints, newPoint];
        setPolygonPoints(newPoints);
        form.setData('polygon_coordinates', newPoints);
    }, [polygonPoints, form]);

    const handleZoneTypeChange = (type: 'circle' | 'polygon') => {
        setZoneType(type);
        form.setData('zone_type', type);
    };

    const handleRadiusChange = (value: number[]) => {
        form.setData('radius_meters', value[0]);
    };

    const handleUndoPolygon = () => {
        const newPoints = polygonPoints.slice(0, -1);
        setPolygonPoints(newPoints);
        form.setData('polygon_coordinates', newPoints);
    };

    const handleClearPolygon = () => {
        setPolygonPoints([]);
        form.setData('polygon_coordinates', []);
    };

    const handleLabelToggle = (labelId: number) => {
        const current = form.data.label_ids;
        const newLabels = current.includes(labelId)
            ? current.filter((id) => id !== labelId)
            : [...current, labelId];
        form.setData('label_ids', newLabels);
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        form.put(`/company/zones/${zone.id}`);
    };

    const isValid =
        form.data.name.trim() &&
        ((zoneType === 'circle' && circleCenter) || (zoneType === 'polygon' && polygonPoints.length >= 3));

    return (
        <CompanyLayout title={`Modifier: ${zone.name}`} company={company} userRole={userRole}>
            <form onSubmit={handleSubmit} className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={`/company/zones/${zone.id}`}>
                                <ArrowLeft className="h-5 w-5" />
                            </Link>
                        </Button>
                        <div>
                            <h2 className="text-2xl font-bold tracking-tight">Modifier la zone</h2>
                            <p className="text-muted-foreground">{zone.name}</p>
                        </div>
                    </div>
                    <Button type="submit" disabled={!isValid || form.processing}>
                        <Save className="mr-2 h-4 w-4" />
                        Enregistrer
                    </Button>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Map */}
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <div className="flex items-center justify-between">
                                <div>
                                    <CardTitle>Carte</CardTitle>
                                    <CardDescription>
                                        {zoneType === 'circle'
                                            ? 'Cliquez sur la carte pour repositionner le centre'
                                            : 'Cliquez pour modifier les points du polygone'}
                                    </CardDescription>
                                </div>
                                <Tabs value={zoneType} onValueChange={(v) => handleZoneTypeChange(v as 'circle' | 'polygon')}>
                                    <TabsList>
                                        <TabsTrigger value="circle" className="gap-2">
                                            <Circle className="h-4 w-4" />
                                            Cercle
                                        </TabsTrigger>
                                        <TabsTrigger value="polygon" className="gap-2">
                                            <Hexagon className="h-4 w-4" />
                                            Polygone
                                        </TabsTrigger>
                                    </TabsList>
                                </Tabs>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div className="h-[500px] rounded-lg overflow-hidden border">
                                <ZoneMap
                                    zoneType={zoneType}
                                    circleCenter={circleCenter}
                                    polygonPoints={polygonPoints}
                                    radiusMeters={form.data.radius_meters}
                                    fillColor={form.data.fill_color}
                                    fillOpacity={form.data.fill_opacity}
                                    strokeColor={form.data.stroke_color}
                                    strokeWidth={form.data.stroke_width}
                                    onCircleClick={handleCircleClick}
                                    onPolygonClick={handlePolygonClick}
                                />
                            </div>

                            {zoneType === 'polygon' && (
                                <div className="mt-4 flex items-center justify-between">
                                    <p className="text-sm text-muted-foreground">
                                        {polygonPoints.length} point(s) - {polygonPoints.length >= 3 ? 'Polygone valide' : 'Min. 3 points requis'}
                                    </p>
                                    <div className="flex gap-2">
                                        <Button type="button" variant="outline" size="sm" onClick={handleClearPolygon}>
                                            Effacer tout
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={handleUndoPolygon}
                                            disabled={polygonPoints.length === 0}
                                        >
                                            <Undo2 className="mr-2 h-4 w-4" />
                                            Annuler dernier
                                        </Button>
                                    </div>
                                </div>
                            )}

                            {zoneType === 'circle' && circleCenter && (
                                <div className="mt-4 space-y-2">
                                    <div className="flex items-center justify-between">
                                        <Label>Rayon</Label>
                                        <span className="text-sm font-medium">
                                            {form.data.radius_meters >= 1000
                                                ? `${(form.data.radius_meters / 1000).toFixed(1)} km`
                                                : `${form.data.radius_meters} m`}
                                        </span>
                                    </div>
                                    <Slider
                                        value={[form.data.radius_meters]}
                                        onValueChange={handleRadiusChange}
                                        min={50}
                                        max={10000}
                                        step={50}
                                    />
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Form */}
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <CardTitle>Informations</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="name">Nom *</Label>
                                    <Input
                                        id="name"
                                        value={form.data.name}
                                        onChange={(e) => form.setData('name', e.target.value)}
                                    />
                                    {form.errors.name && <p className="text-sm text-destructive">{form.errors.name}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="description">Description</Label>
                                    <Textarea
                                        id="description"
                                        value={form.data.description}
                                        onChange={(e) => form.setData('description', e.target.value)}
                                        rows={3}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label>Statut</Label>
                                    <Select value={form.data.status} onValueChange={(v) => form.setData('status', v as ZoneData['status'])}>
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="active">Actif</SelectItem>
                                            <SelectItem value="inactive">Inactif</SelectItem>
                                            <SelectItem value="archived">Archivé</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>

                                <div className="space-y-2">
                                    <Label>Zone parente</Label>
                                    <Select
                                        value={form.data.parent_zone_id}
                                        onValueChange={(v) => form.setData('parent_zone_id', v)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue placeholder="Aucune (zone racine)" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="">Aucune (zone racine)</SelectItem>
                                            {parentZones.map((z) => (
                                                <SelectItem key={z.id} value={String(z.id)}>
                                                    {z.name}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </CardContent>
                        </Card>

                        <Card>
                            <CardHeader>
                                <CardTitle>Style</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="space-y-2">
                                    <Label>Couleur de remplissage</Label>
                                    <div className="flex flex-wrap gap-2">
                                        {defaultColors.map((color) => (
                                            <button
                                                key={color}
                                                type="button"
                                                onClick={() => form.setData('fill_color', color)}
                                                className={`h-8 w-8 rounded-full border-2 ${form.data.fill_color === color ? 'border-foreground' : 'border-transparent'}`}
                                                style={{ backgroundColor: color }}
                                            />
                                        ))}
                                    </div>
                                </div>

                                <div className="space-y-2">
                                    <div className="flex items-center justify-between">
                                        <Label>Opacité</Label>
                                        <span className="text-sm">{Math.round(form.data.fill_opacity * 100)}%</span>
                                    </div>
                                    <Slider
                                        value={[form.data.fill_opacity]}
                                        onValueChange={([v]) => form.setData('fill_opacity', v)}
                                        min={0}
                                        max={1}
                                        step={0.1}
                                    />
                                </div>

                                <div className="space-y-2">
                                    <Label>Couleur du contour</Label>
                                    <div className="flex flex-wrap gap-2">
                                        {defaultColors.map((color) => (
                                            <button
                                                key={color}
                                                type="button"
                                                onClick={() => form.setData('stroke_color', color)}
                                                className={`h-8 w-8 rounded-full border-2 ${form.data.stroke_color === color ? 'border-foreground' : 'border-transparent'}`}
                                                style={{ backgroundColor: color }}
                                            />
                                        ))}
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        {labels.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle>Labels</CardTitle>
                                </CardHeader>
                                <CardContent>
                                    <div className="flex flex-wrap gap-2">
                                        {labels.map((label) => (
                                            <Badge
                                                key={label.id}
                                                variant={form.data.label_ids.includes(label.id) ? 'default' : 'outline'}
                                                className="cursor-pointer"
                                                style={
                                                    form.data.label_ids.includes(label.id)
                                                        ? { backgroundColor: label.color, borderColor: label.color }
                                                        : { borderColor: label.color, color: label.color }
                                                }
                                                onClick={() => handleLabelToggle(label.id)}
                                            >
                                                {label.name}
                                            </Badge>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </form>
        </CompanyLayout>
    );
}
