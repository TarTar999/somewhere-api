import { useState, useCallback, useRef, useEffect } from 'react';
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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Circle, Hexagon, ArrowLeft, Save, MapPin, Undo2 } from 'lucide-react';
import { Link, useForm } from '@inertiajs/react';
import type { CompanyRole } from '@/types/company';
import { MapContainer, TileLayer, Circle as LeafletCircle, Polygon, useMapEvents, useMap } from 'react-leaflet';
import type { LatLng } from 'leaflet';
import 'leaflet/dist/leaflet.css';

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

function MapClickHandler({
    zoneType,
    onCircleClick,
    onPolygonClick,
}: {
    zoneType: 'circle' | 'polygon';
    onCircleClick: (latlng: LatLng) => void;
    onPolygonClick: (latlng: LatLng) => void;
}) {
    useMapEvents({
        click(e) {
            if (zoneType === 'circle') {
                onCircleClick(e.latlng);
            } else {
                onPolygonClick(e.latlng);
            }
        },
    });
    return null;
}

function MapCenterOnCircle({ center }: { center: { lat: number; lng: number } | null }) {
    const map = useMap();
    useEffect(() => {
        if (center) {
            map.setView([center.lat, center.lng], 14);
        }
    }, [center, map]);
    return null;
}

export default function ZonesCreate({ company, userRole, labels, parentZones, defaultColors }: Props) {
    const [zoneType, setZoneType] = useState<'circle' | 'polygon'>('circle');
    const [circleCenter, setCircleCenter] = useState<{ lat: number; lng: number } | null>(null);
    const [polygonPoints, setPolygonPoints] = useState<Array<{ lat: number; lng: number }>>([]);

    const form = useForm({
        name: '',
        description: '',
        zone_type: 'circle' as 'circle' | 'polygon',
        parent_zone_id: '',
        center_lat: null as number | null,
        center_lng: null as number | null,
        radius_meters: 500,
        polygon_coordinates: [] as Array<{ lat: number; lng: number }>,
        fill_color: defaultColors[0] || '#3B82F6',
        fill_opacity: 0.3,
        stroke_color: defaultColors[1] || '#2563EB',
        stroke_width: 2,
        label_ids: [] as number[],
    });

    const handleCircleClick = useCallback((latlng: LatLng) => {
        const center = { lat: latlng.lat, lng: latlng.lng };
        setCircleCenter(center);
        form.setData({
            ...form.data,
            center_lat: latlng.lat,
            center_lng: latlng.lng,
        });
    }, [form]);

    const handlePolygonClick = useCallback((latlng: LatLng) => {
        const newPoint = { lat: latlng.lat, lng: latlng.lng };
        const newPoints = [...polygonPoints, newPoint];
        setPolygonPoints(newPoints);
        form.setData('polygon_coordinates', newPoints);
    }, [polygonPoints, form]);

    const handleZoneTypeChange = (type: 'circle' | 'polygon') => {
        setZoneType(type);
        form.setData('zone_type', type);
        // Reset drawing data
        setCircleCenter(null);
        setPolygonPoints([]);
        form.setData({
            ...form.data,
            zone_type: type,
            center_lat: null,
            center_lng: null,
            polygon_coordinates: [],
        });
    };

    const handleRadiusChange = (value: number[]) => {
        form.setData('radius_meters', value[0]);
    };

    const handleUndoPolygon = () => {
        const newPoints = polygonPoints.slice(0, -1);
        setPolygonPoints(newPoints);
        form.setData('polygon_coordinates', newPoints);
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
        form.post('/company/zones');
    };

    const isValid =
        form.data.name.trim() &&
        ((zoneType === 'circle' && circleCenter) || (zoneType === 'polygon' && polygonPoints.length >= 3));

    // Default center for Cameroon
    const defaultCenter: [number, number] = [3.848, 11.502];

    return (
        <CompanyLayout title="Nouvelle zone" company={company} userRole={userRole}>
            <form onSubmit={handleSubmit} className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href="/company/zones">
                                <ArrowLeft className="h-5 w-5" />
                            </Link>
                        </Button>
                        <div>
                            <h2 className="text-2xl font-bold tracking-tight">Créer une zone</h2>
                            <p className="text-muted-foreground">Dessinez une zone sur la carte</p>
                        </div>
                    </div>
                    <Button type="submit" disabled={!isValid || form.processing}>
                        <Save className="mr-2 h-4 w-4" />
                        Créer la zone
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
                                            ? 'Cliquez sur la carte pour placer le centre du cercle'
                                            : 'Cliquez pour ajouter des points au polygone'}
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
                                <MapContainer
                                    center={defaultCenter}
                                    zoom={12}
                                    className="h-full w-full"
                                    scrollWheelZoom={true}
                                >
                                    <TileLayer
                                        attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                                        url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                                    />
                                    <MapClickHandler
                                        zoneType={zoneType}
                                        onCircleClick={handleCircleClick}
                                        onPolygonClick={handlePolygonClick}
                                    />
                                    <MapCenterOnCircle center={circleCenter} />

                                    {/* Circle preview */}
                                    {zoneType === 'circle' && circleCenter && (
                                        <LeafletCircle
                                            center={[circleCenter.lat, circleCenter.lng]}
                                            radius={form.data.radius_meters}
                                            pathOptions={{
                                                fillColor: form.data.fill_color,
                                                fillOpacity: form.data.fill_opacity,
                                                color: form.data.stroke_color,
                                                weight: form.data.stroke_width,
                                            }}
                                        />
                                    )}

                                    {/* Polygon preview */}
                                    {zoneType === 'polygon' && polygonPoints.length >= 3 && (
                                        <Polygon
                                            positions={polygonPoints.map((p) => [p.lat, p.lng])}
                                            pathOptions={{
                                                fillColor: form.data.fill_color,
                                                fillOpacity: form.data.fill_opacity,
                                                color: form.data.stroke_color,
                                                weight: form.data.stroke_width,
                                            }}
                                        />
                                    )}
                                </MapContainer>
                            </div>

                            {/* Polygon controls */}
                            {zoneType === 'polygon' && (
                                <div className="mt-4 flex items-center justify-between">
                                    <p className="text-sm text-muted-foreground">
                                        {polygonPoints.length} point(s) - {polygonPoints.length >= 3 ? 'Polygone valide' : 'Min. 3 points requis'}
                                    </p>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={handleUndoPolygon}
                                        disabled={polygonPoints.length === 0}
                                    >
                                        <Undo2 className="mr-2 h-4 w-4" />
                                        Annuler dernier point
                                    </Button>
                                </div>
                            )}

                            {/* Circle radius slider */}
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
                                        placeholder="Ex: Zone Nord, Secteur A..."
                                    />
                                    {form.errors.name && <p className="text-sm text-destructive">{form.errors.name}</p>}
                                </div>

                                <div className="space-y-2">
                                    <Label htmlFor="description">Description</Label>
                                    <Textarea
                                        id="description"
                                        value={form.data.description}
                                        onChange={(e) => form.setData('description', e.target.value)}
                                        placeholder="Description de la zone..."
                                        rows={3}
                                    />
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
                                            {parentZones.map((zone) => (
                                                <SelectItem key={zone.id} value={String(zone.id)}>
                                                    {zone.name}
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
                                    <CardDescription>Associez des labels à cette zone</CardDescription>
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
