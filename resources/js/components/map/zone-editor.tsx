import { useState, useEffect, useRef, useCallback } from 'react';
import { MapContainer, TileLayer, FeatureGroup, Polygon, Circle, useMap } from 'react-leaflet';
import { EditControl } from 'react-leaflet-draw';
import L from 'leaflet';
import { motion, AnimatePresence } from 'framer-motion';
import {
    Pencil,
    Trash2,
    Save,
    X,
    Circle as CircleIcon,
    Pentagon,
    Palette,
    Eye,
    EyeOff,
    Check,
    RotateCcw,
    Plus,
    Settings2,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Slider } from '@/components/ui/slider';
import { cn } from '@/lib/utils';

// Import Leaflet Draw CSS
import 'leaflet/dist/leaflet.css';
import 'leaflet-draw/dist/leaflet.draw.css';

// Zone types
export interface Zone {
    id?: number;
    name: string;
    type: 'circle' | 'polygon';
    color: string;
    opacity: number;
    coordinates: number[][] | { lat: number; lng: number; radius: number };
    is_active: boolean;
}

interface ZoneEditorProps {
    zones: Zone[];
    center?: [number, number];
    zoom?: number;
    onSave: (zones: Zone[]) => void;
    onCancel: () => void;
    className?: string;
}

// Color presets
const colorPresets = [
    '#000000', // Black (SW brand)
    '#1a1a1a', // Charcoal
    '#3b82f6', // Blue
    '#22c55e', // Green
    '#f59e0b', // Amber
    '#ef4444', // Red
    '#8b5cf6', // Purple
    '#06b6d4', // Cyan
];

// Map controller for programmatic access
function MapController({
    onMapReady
}: {
    onMapReady: (map: L.Map) => void
}) {
    const map = useMap();

    useEffect(() => {
        onMapReady(map);
    }, [map, onMapReady]);

    return null;
}

export function ZoneEditor({
    zones: initialZones,
    center = [5.95, 10.15],
    zoom = 12,
    onSave,
    onCancel,
    className,
}: ZoneEditorProps) {
    // State
    const [zones, setZones] = useState<Zone[]>(initialZones);
    const [selectedZoneIndex, setSelectedZoneIndex] = useState<number | null>(null);
    const [isDrawing, setIsDrawing] = useState(false);
    const [drawType, setDrawType] = useState<'circle' | 'polygon' | null>(null);
    const [showSettings, setShowSettings] = useState(false);
    const [hasChanges, setHasChanges] = useState(false);

    // Refs
    const featureGroupRef = useRef<L.FeatureGroup | null>(null);
    const mapRef = useRef<L.Map | null>(null);

    // Selected zone
    const selectedZone = selectedZoneIndex !== null ? zones[selectedZoneIndex] : null;

    // Handle map ready
    const handleMapReady = useCallback((map: L.Map) => {
        mapRef.current = map;
    }, []);

    // Handle draw created
    const handleCreated = useCallback((e: any) => {
        const layer = e.layer;
        const type = e.layerType;

        let newZone: Zone;

        if (type === 'circle') {
            const center = layer.getLatLng();
            const radius = layer.getRadius();
            newZone = {
                name: `Zone ${zones.length + 1}`,
                type: 'circle',
                color: colorPresets[zones.length % colorPresets.length],
                opacity: 0.3,
                coordinates: { lat: center.lat, lng: center.lng, radius },
                is_active: true,
            };
        } else if (type === 'polygon' || type === 'rectangle') {
            const latlngs = layer.getLatLngs()[0];
            newZone = {
                name: `Zone ${zones.length + 1}`,
                type: 'polygon',
                color: colorPresets[zones.length % colorPresets.length],
                opacity: 0.3,
                coordinates: latlngs.map((ll: L.LatLng) => [ll.lat, ll.lng]),
                is_active: true,
            };
        } else {
            return;
        }

        setZones(prev => [...prev, newZone]);
        setSelectedZoneIndex(zones.length);
        setHasChanges(true);
        setIsDrawing(false);
        setDrawType(null);

        // Remove the layer as we'll render it ourselves
        if (featureGroupRef.current) {
            featureGroupRef.current.removeLayer(layer);
        }
    }, [zones.length]);

    // Handle draw edited
    const handleEdited = useCallback((e: any) => {
        const layers = e.layers;
        layers.eachLayer((layer: any) => {
            // Find and update the corresponding zone
            const zoneIndex = layer.options.zoneIndex;
            if (zoneIndex !== undefined) {
                setZones(prev => {
                    const updated = [...prev];
                    const zone = updated[zoneIndex];

                    if (zone.type === 'circle') {
                        const center = layer.getLatLng();
                        const radius = layer.getRadius();
                        updated[zoneIndex] = {
                            ...zone,
                            coordinates: { lat: center.lat, lng: center.lng, radius },
                        };
                    } else {
                        const latlngs = layer.getLatLngs()[0];
                        updated[zoneIndex] = {
                            ...zone,
                            coordinates: latlngs.map((ll: L.LatLng) => [ll.lat, ll.lng]),
                        };
                    }

                    return updated;
                });
                setHasChanges(true);
            }
        });
    }, []);

    // Handle draw deleted
    const handleDeleted = useCallback((e: any) => {
        const layers = e.layers;
        const indicesToDelete: number[] = [];

        layers.eachLayer((layer: any) => {
            const zoneIndex = layer.options.zoneIndex;
            if (zoneIndex !== undefined) {
                indicesToDelete.push(zoneIndex);
            }
        });

        if (indicesToDelete.length > 0) {
            setZones(prev => prev.filter((_, index) => !indicesToDelete.includes(index)));
            setSelectedZoneIndex(null);
            setHasChanges(true);
        }
    }, []);

    // Update zone property
    const updateZone = useCallback((index: number, updates: Partial<Zone>) => {
        setZones(prev => {
            const updated = [...prev];
            updated[index] = { ...updated[index], ...updates };
            return updated;
        });
        setHasChanges(true);
    }, []);

    // Delete zone
    const deleteZone = useCallback((index: number) => {
        setZones(prev => prev.filter((_, i) => i !== index));
        setSelectedZoneIndex(null);
        setHasChanges(true);
    }, []);

    // Reset changes
    const resetChanges = useCallback(() => {
        setZones(initialZones);
        setSelectedZoneIndex(null);
        setHasChanges(false);
    }, [initialZones]);

    // Save changes
    const handleSave = useCallback(() => {
        onSave(zones);
    }, [zones, onSave]);

    // Start drawing
    const startDrawing = useCallback((type: 'circle' | 'polygon') => {
        setDrawType(type);
        setIsDrawing(true);
        setSelectedZoneIndex(null);
    }, []);

    return (
        <div className={cn("relative h-full w-full", className)}>
            {/* Map */}
            <MapContainer
                center={center}
                zoom={zoom}
                className="h-full w-full z-0"
                zoomControl={false}
            >
                <TileLayer
                    attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                    url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
                />

                <MapController onMapReady={handleMapReady} />

                <FeatureGroup ref={featureGroupRef}>
                    <EditControl
                        position="topleft"
                        onCreated={handleCreated}
                        onEdited={handleEdited}
                        onDeleted={handleDeleted}
                        draw={{
                            rectangle: isDrawing && drawType === 'polygon',
                            polygon: isDrawing && drawType === 'polygon',
                            circle: isDrawing && drawType === 'circle',
                            polyline: false,
                            marker: false,
                            circlemarker: false,
                        }}
                        edit={{
                            featureGroup: featureGroupRef.current!,
                            remove: true,
                        }}
                    />
                </FeatureGroup>

                {/* Render zones */}
                {zones.map((zone, index) => {
                    if (!zone.is_active && selectedZoneIndex !== index) return null;

                    const isSelected = selectedZoneIndex === index;
                    const opacity = isSelected ? zone.opacity + 0.2 : zone.opacity;

                    if (zone.type === 'circle' && 'radius' in zone.coordinates) {
                        return (
                            <Circle
                                key={index}
                                center={[zone.coordinates.lat, zone.coordinates.lng]}
                                radius={zone.coordinates.radius}
                                pathOptions={{
                                    color: zone.color,
                                    fillColor: zone.color,
                                    fillOpacity: opacity,
                                    weight: isSelected ? 3 : 2,
                                }}
                                eventHandlers={{
                                    click: () => setSelectedZoneIndex(index),
                                }}
                            />
                        );
                    }

                    if (zone.type === 'polygon' && Array.isArray(zone.coordinates)) {
                        return (
                            <Polygon
                                key={index}
                                positions={zone.coordinates as [number, number][]}
                                pathOptions={{
                                    color: zone.color,
                                    fillColor: zone.color,
                                    fillOpacity: opacity,
                                    weight: isSelected ? 3 : 2,
                                }}
                                eventHandlers={{
                                    click: () => setSelectedZoneIndex(index),
                                }}
                            />
                        );
                    }

                    return null;
                })}
            </MapContainer>

            {/* Toolbar - Top */}
            <div className="absolute top-4 left-1/2 -translate-x-1/2 z-[1000]">
                <div className="bg-white/95 backdrop-blur-md rounded-lg shadow-lg border p-2 flex items-center gap-2">
                    {/* Draw tools */}
                    <div className="flex items-center gap-1 pr-2 border-r">
                        <Button
                            variant={drawType === 'polygon' ? 'default' : 'ghost'}
                            size="sm"
                            onClick={() => startDrawing('polygon')}
                            title="Dessiner un polygone"
                        >
                            <Pentagon className="h-4 w-4" />
                        </Button>
                        <Button
                            variant={drawType === 'circle' ? 'default' : 'ghost'}
                            size="sm"
                            onClick={() => startDrawing('circle')}
                            title="Dessiner un cercle"
                        >
                            <CircleIcon className="h-4 w-4" />
                        </Button>
                    </div>

                    {/* Cancel drawing */}
                    {isDrawing && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={() => {
                                setIsDrawing(false);
                                setDrawType(null);
                            }}
                        >
                            <X className="h-4 w-4 mr-1" />
                            Annuler
                        </Button>
                    )}

                    {/* Drawing instructions */}
                    {isDrawing && (
                        <span className="text-sm text-muted-foreground px-2">
                            {drawType === 'polygon'
                                ? 'Cliquez pour créer les points, double-cliquez pour terminer'
                                : 'Cliquez et glissez pour créer le cercle'
                            }
                        </span>
                    )}
                </div>
            </div>

            {/* Zone List - Left Panel */}
            <div className="absolute top-20 left-4 z-[1000] w-64">
                <div className="bg-white/95 backdrop-blur-md rounded-lg shadow-lg border overflow-hidden">
                    <div className="p-3 border-b bg-sw-slate-100/50">
                        <h3 className="font-semibold text-sm flex items-center gap-2">
                            <Pentagon className="h-4 w-4" />
                            Zones ({zones.length})
                        </h3>
                    </div>

                    <div className="max-h-80 overflow-y-auto">
                        {zones.length === 0 ? (
                            <div className="p-4 text-center text-muted-foreground text-sm">
                                <Pentagon className="h-8 w-8 mx-auto mb-2 opacity-50" />
                                <p>Aucune zone créée</p>
                                <p className="text-xs mt-1">
                                    Utilisez les outils ci-dessus pour créer une zone
                                </p>
                            </div>
                        ) : (
                            <div className="divide-y">
                                {zones.map((zone, index) => (
                                    <div
                                        key={index}
                                        className={cn(
                                            "p-3 cursor-pointer transition-colors",
                                            selectedZoneIndex === index
                                                ? "bg-sw-slate-100"
                                                : "hover:bg-sw-slate-100/50"
                                        )}
                                        onClick={() => setSelectedZoneIndex(index)}
                                    >
                                        <div className="flex items-center gap-3">
                                            <div
                                                className="w-4 h-4 rounded-full border-2"
                                                style={{
                                                    backgroundColor: zone.color,
                                                    opacity: zone.opacity + 0.5,
                                                }}
                                            />
                                            <div className="flex-1 min-w-0">
                                                <p className="font-medium text-sm truncate">
                                                    {zone.name}
                                                </p>
                                                <p className="text-xs text-muted-foreground">
                                                    {zone.type === 'circle' ? 'Cercle' : 'Polygone'}
                                                </p>
                                            </div>
                                            <button
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    updateZone(index, { is_active: !zone.is_active });
                                                }}
                                                className="p-1 hover:bg-sw-slate-300/50 rounded"
                                            >
                                                {zone.is_active ? (
                                                    <Eye className="h-4 w-4 text-muted-foreground" />
                                                ) : (
                                                    <EyeOff className="h-4 w-4 text-muted-foreground" />
                                                )}
                                            </button>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </div>

            {/* Zone Settings - Right Panel */}
            <AnimatePresence>
                {selectedZone && (
                    <motion.div
                        initial={{ opacity: 0, x: 20 }}
                        animate={{ opacity: 1, x: 0 }}
                        exit={{ opacity: 0, x: 20 }}
                        className="absolute top-20 right-4 z-[1000] w-72"
                    >
                        <div className="bg-white/95 backdrop-blur-md rounded-lg shadow-lg border overflow-hidden">
                            <div className="p-3 border-b bg-sw-slate-100/50 flex items-center justify-between">
                                <h3 className="font-semibold text-sm flex items-center gap-2">
                                    <Settings2 className="h-4 w-4" />
                                    Paramètres de zone
                                </h3>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => setSelectedZoneIndex(null)}
                                >
                                    <X className="h-4 w-4" />
                                </Button>
                            </div>

                            <div className="p-4 space-y-4">
                                {/* Zone name */}
                                <div className="space-y-2">
                                    <Label htmlFor="zone-name" className="text-sm">
                                        Nom de la zone
                                    </Label>
                                    <Input
                                        id="zone-name"
                                        value={selectedZone.name}
                                        onChange={(e) => updateZone(selectedZoneIndex!, { name: e.target.value })}
                                        placeholder="Ex: Zone de livraison"
                                    />
                                </div>

                                {/* Color */}
                                <div className="space-y-2">
                                    <Label className="text-sm">Couleur</Label>
                                    <div className="flex flex-wrap gap-2">
                                        {colorPresets.map((color) => (
                                            <button
                                                key={color}
                                                className={cn(
                                                    "w-8 h-8 rounded-full border-2 transition-transform hover:scale-110",
                                                    selectedZone.color === color
                                                        ? "border-sw-black ring-2 ring-sw-black ring-offset-2"
                                                        : "border-transparent"
                                                )}
                                                style={{ backgroundColor: color }}
                                                onClick={() => updateZone(selectedZoneIndex!, { color })}
                                            />
                                        ))}
                                    </div>

                                    {/* Custom color */}
                                    <div className="flex items-center gap-2 mt-2">
                                        <Input
                                            type="color"
                                            value={selectedZone.color}
                                            onChange={(e) => updateZone(selectedZoneIndex!, { color: e.target.value })}
                                            className="w-12 h-8 p-0 border-0"
                                        />
                                        <span className="text-xs text-muted-foreground">
                                            Couleur personnalisée
                                        </span>
                                    </div>
                                </div>

                                {/* Opacity */}
                                <div className="space-y-2">
                                    <div className="flex items-center justify-between">
                                        <Label className="text-sm">Opacité</Label>
                                        <span className="text-xs text-muted-foreground">
                                            {Math.round(selectedZone.opacity * 100)}%
                                        </span>
                                    </div>
                                    <Slider
                                        value={[selectedZone.opacity * 100]}
                                        onValueChange={([value]) => updateZone(selectedZoneIndex!, { opacity: value / 100 })}
                                        min={10}
                                        max={80}
                                        step={5}
                                    />
                                </div>

                                {/* Zone type (read-only) */}
                                <div className="space-y-2">
                                    <Label className="text-sm">Type</Label>
                                    <div className="flex items-center gap-2 p-2 bg-sw-slate-100/50 rounded">
                                        {selectedZone.type === 'circle' ? (
                                            <>
                                                <CircleIcon className="h-4 w-4" />
                                                <span className="text-sm">Cercle</span>
                                            </>
                                        ) : (
                                            <>
                                                <Pentagon className="h-4 w-4" />
                                                <span className="text-sm">Polygone</span>
                                            </>
                                        )}
                                    </div>
                                </div>

                                {/* Circle radius info */}
                                {selectedZone.type === 'circle' && 'radius' in selectedZone.coordinates && (
                                    <div className="space-y-2">
                                        <Label className="text-sm">Rayon</Label>
                                        <div className="p-2 bg-sw-slate-100/50 rounded">
                                            <span className="text-sm font-mono">
                                                {Math.round(selectedZone.coordinates.radius)} m
                                            </span>
                                        </div>
                                    </div>
                                )}

                                {/* Delete zone */}
                                <div className="pt-4 border-t">
                                    <Button
                                        variant="destructive"
                                        size="sm"
                                        className="w-full"
                                        onClick={() => deleteZone(selectedZoneIndex!)}
                                    >
                                        <Trash2 className="h-4 w-4 mr-2" />
                                        Supprimer cette zone
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </motion.div>
                )}
            </AnimatePresence>

            {/* Action Buttons - Bottom */}
            <div className="absolute bottom-4 left-1/2 -translate-x-1/2 z-[1000]">
                <div className="bg-white/95 backdrop-blur-md rounded-lg shadow-lg border p-3 flex items-center gap-3">
                    {hasChanges && (
                        <Button
                            variant="ghost"
                            size="sm"
                            onClick={resetChanges}
                        >
                            <RotateCcw className="h-4 w-4 mr-2" />
                            Réinitialiser
                        </Button>
                    )}

                    <Button
                        variant="outline"
                        onClick={onCancel}
                    >
                        <X className="h-4 w-4 mr-2" />
                        Annuler
                    </Button>

                    <Button
                        onClick={handleSave}
                        disabled={!hasChanges}
                        className="bg-sw-black hover:bg-sw-charcoal"
                    >
                        <Save className="h-4 w-4 mr-2" />
                        Enregistrer {zones.length > 0 && `(${zones.length})`}
                    </Button>
                </div>
            </div>
        </div>
    );
}

export default ZoneEditor;
