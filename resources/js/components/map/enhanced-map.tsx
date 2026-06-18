import * as React from 'react';
import { MapContainer, TileLayer, Marker, Popup, Circle, Polygon, useMap, ZoomControl, AttributionControl } from 'react-leaflet';
import MarkerClusterGroup from 'react-leaflet-cluster';
import { usePage } from '@inertiajs/react';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Types
export interface Address {
    id: number;
    latitude: number;
    longitude: number;
    sw_address?: string;
    street_name?: string;
    quarter?: string;
    verification_status?: 'pending' | 'approved' | 'rejected';
    label?: {
        name: string;
        color: string;
        icon?: string;
    };
}

export interface Zone {
    id: number;
    name: string;
    type: 'circle' | 'polygon';
    coordinates?: [number, number][];
    center?: [number, number];
    radius?: number;
    fill_color?: string;
    fill_opacity?: number;
    stroke_color?: string;
    stroke_width?: number;
    status?: 'active' | 'inactive' | 'archived';
}

export interface HeatmapPoint {
    lat: number;
    lng: number;
    intensity?: number;
}

export interface EnhancedMapProps {
    center?: [number, number];
    zoom?: number;
    className?: string;
    addresses?: Address[];
    zones?: Zone[];
    heatmapData?: HeatmapPoint[];
    showHeatmap?: boolean;
    showClusters?: boolean;
    showZones?: boolean;
    selectedAddressId?: number;
    selectedZoneId?: number;
    onAddressClick?: (address: Address) => void;
    onZoneClick?: (zone: Zone) => void;
    onMapClick?: (latlng: { lat: number; lng: number }) => void;
    onBoundsChange?: (bounds: L.LatLngBounds) => void;
    interactive?: boolean;
    tileStyle?: 'default' | 'light' | 'dark' | 'satellite';
    children?: React.ReactNode;
}

// Default center: Douala, Cameroon
const DEFAULT_CENTER: [number, number] = [4.0511, 9.7679];
const DEFAULT_ZOOM = 13;

// SomeWhere map styles - Mapbox style IDs
// Mix of elegant and vibrant styles for unique identity
const mapboxStyles: Record<string, string> = {
    // Style par défaut SomeWhere - Streets coloré et vivant
    default: 'mapbox/streets-v12',

    // Standard - Le plus récent de Mapbox, 3D, moderne et coloré
    standard: 'mapbox/standard',

    // Navigation Day - Couleurs vives optimisées pour la lisibilité
    vibrant: 'mapbox/navigation-day-v1',

    // Outdoors - Coloré avec relief et nature
    outdoors: 'mapbox/outdoors-v12',

    // Mode sombre - Élégant avec couleurs vives sur fond sombre
    dark: 'mapbox/navigation-night-v1',

    // Minimaliste clair
    light: 'mapbox/light-v11',

    // Satellite avec routes colorées
    satellite: 'mapbox/satellite-streets-v12',
};

// Get Mapbox tile URL with style
const getMapboxTileUrl = (styleKey: string, token: string, customStyleUrl?: string) => {
    // Si un style personnalisé est défini, l'utiliser
    if (customStyleUrl && customStyleUrl.startsWith('mapbox://styles/')) {
        const styleId = customStyleUrl.replace('mapbox://styles/', '');
        return `https://api.mapbox.com/styles/v1/${styleId}/tiles/256/{z}/{x}/{y}@2x?access_token=${token}`;
    }

    const styleId = mapboxStyles[styleKey] || mapboxStyles.default;
    return `https://api.mapbox.com/styles/v1/${styleId}/tiles/256/{z}/{x}/{y}@2x?access_token=${token}`;
};

// Fallback tile layers (when no Mapbox token)
const fallbackTileLayers: Record<string, string> = {
    default: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    standard: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    vibrant: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    outdoors: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
    dark: 'https://tiles.stadiamaps.com/tiles/alidade_smooth_dark/{z}/{x}/{y}{r}.png',
    light: 'https://tiles.stadiamaps.com/tiles/alidade_smooth/{z}/{x}/{y}{r}.png',
    satellite: 'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}',
};

// SomeWhere brand color - Blue-Violet vibrant
const SW_PRIMARY_COLOR = '#6366F1'; // Indigo-500 avec nuance violet
const SW_SELECTED_COLOR = '#4F46E5'; // Indigo-600 plus foncé pour sélection

// Custom marker icons
const createMarkerIcon = (color: string = SW_PRIMARY_COLOR, isSelected: boolean = false) => {
    const size = isSelected ? 40 : 32;
    const markerColor = isSelected ? SW_SELECTED_COLOR : color;
    return L.divIcon({
        className: 'custom-marker',
        html: `
            <div class="flex items-center justify-center rounded-full shadow-xl border-3 border-white ${isSelected ? 'marker-pulse' : ''}"
                 style="width: ${size}px; height: ${size}px; background: linear-gradient(135deg, ${markerColor} 0%, #8B5CF6 100%); box-shadow: 0 4px 14px rgba(99, 102, 241, 0.4);">
                <svg xmlns="http://www.w3.org/2000/svg" width="${size * 0.45}" height="${size * 0.45}" viewBox="0 0 24 24" fill="none" stroke="white" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M20 10c0 6-8 12-8 12s-8-6-8-12a8 8 0 0 1 16 0Z"/>
                    <circle cx="12" cy="10" r="3"/>
                </svg>
            </div>
        `,
        iconSize: [size, size],
        iconAnchor: [size / 2, size],
        popupAnchor: [0, -size],
    });
};

// Cluster icon
const createClusterIcon = (count: number) => {
    let size = 'sw-cluster-small';
    if (count > 50) {
        size = 'sw-cluster-large';
    } else if (count > 10) {
        size = 'sw-cluster-medium';
    }

    return L.divIcon({
        className: `sw-cluster ${size}`,
        html: `<span>${count}</span>`,
        iconSize: [40, 40],
        iconAnchor: [20, 20],
    });
};

// Map controller component for bounds changes
function MapController({ onBoundsChange }: { onBoundsChange?: (bounds: L.LatLngBounds) => void }) {
    const map = useMap();

    React.useEffect(() => {
        if (!onBoundsChange) return;

        const handleMoveEnd = () => {
            onBoundsChange(map.getBounds());
        };

        map.on('moveend', handleMoveEnd);
        return () => {
            map.off('moveend', handleMoveEnd);
        };
    }, [map, onBoundsChange]);

    return null;
}

// Heatmap Layer Component
function HeatmapLayer({ data }: { data: HeatmapPoint[] }) {
    const map = useMap();

    React.useEffect(() => {
        if (!data || data.length === 0) return;

        // @ts-ignore - leaflet.heat types
        const heat = L.heatLayer(
            data.map((point) => [point.lat, point.lng, point.intensity || 0.5]),
            {
                radius: 25,
                blur: 15,
                maxZoom: 17,
                gradient: {
                    0.0: '#4ADE80',
                    0.5: '#FACC15',
                    1.0: '#EF4444',
                },
            }
        ).addTo(map);

        return () => {
            map.removeLayer(heat);
        };
    }, [map, data]);

    return null;
}

// Address Marker Component
function AddressMarker({
    address,
    isSelected,
    onClick,
}: {
    address: Address;
    isSelected: boolean;
    onClick?: (address: Address) => void;
}) {
    const color = address.label?.color || SW_PRIMARY_COLOR;
    const icon = createMarkerIcon(color, isSelected);

    return (
        <Marker
            position={[address.latitude, address.longitude]}
            icon={icon}
            eventHandlers={{
                click: () => onClick?.(address),
            }}
        >
            <Popup>
                <div className="min-w-[200px]">
                    <p className="font-semibold text-foreground">
                        {address.sw_address || 'Adresse'}
                    </p>
                    {address.street_name && (
                        <p className="text-sm text-muted-foreground">{address.street_name}</p>
                    )}
                    {address.quarter && (
                        <p className="text-sm text-muted-foreground">{address.quarter}</p>
                    )}
                    {address.label && (
                        <span
                            className="inline-block mt-2 px-2 py-0.5 rounded-full text-xs text-white"
                            style={{ backgroundColor: address.label.color }}
                        >
                            {address.label.name}
                        </span>
                    )}
                </div>
            </Popup>
        </Marker>
    );
}

// Zone Component
function ZoneLayer({
    zone,
    isSelected,
    onClick,
}: {
    zone: Zone;
    isSelected: boolean;
    onClick?: (zone: Zone) => void;
}) {
    const baseOptions = {
        fillColor: zone.fill_color || 'var(--zone-fill)',
        fillOpacity: zone.fill_opacity || 0.2,
        color: zone.stroke_color || 'var(--zone-stroke)',
        weight: isSelected ? 3 : (zone.stroke_width || 2),
        dashArray: zone.status === 'inactive' ? '5, 5' : undefined,
    };

    const handleClick = () => onClick?.(zone);

    if (zone.type === 'circle' && zone.center && zone.radius) {
        return (
            <Circle
                center={zone.center}
                radius={zone.radius}
                pathOptions={baseOptions}
                eventHandlers={{ click: handleClick }}
            >
                <Popup>
                    <div className="min-w-[150px]">
                        <p className="font-semibold">{zone.name}</p>
                        <p className="text-sm text-muted-foreground">
                            Rayon: {zone.radius}m
                        </p>
                    </div>
                </Popup>
            </Circle>
        );
    }

    if (zone.type === 'polygon' && zone.coordinates) {
        return (
            <Polygon
                positions={zone.coordinates}
                pathOptions={baseOptions}
                eventHandlers={{ click: handleClick }}
            >
                <Popup>
                    <div className="min-w-[150px]">
                        <p className="font-semibold">{zone.name}</p>
                        <p className="text-sm text-muted-foreground">Zone polygone</p>
                    </div>
                </Popup>
            </Polygon>
        );
    }

    return null;
}

// Shared props type
interface MapboxConfig {
    token?: string;
    style?: string;
    custom_style_url?: string;
}

interface SharedProps {
    mapbox?: MapboxConfig;
}

// Main EnhancedMap Component
export function EnhancedMap({
    center = DEFAULT_CENTER,
    zoom = DEFAULT_ZOOM,
    className,
    addresses = [],
    zones = [],
    heatmapData = [],
    showHeatmap = false,
    showClusters = true,
    showZones = true,
    selectedAddressId,
    selectedZoneId,
    onAddressClick,
    onZoneClick,
    onMapClick,
    onBoundsChange,
    interactive = true,
    tileStyle = 'default',
    children,
}: EnhancedMapProps) {
    // Get Mapbox config from shared props
    const { mapbox } = usePage<{ props: SharedProps }>().props as unknown as SharedProps;
    const mapboxToken = mapbox?.token;
    const mapboxStyle = mapbox?.style || 'default';
    const customStyleUrl = mapbox?.custom_style_url;

    // Determine tile URL based on Mapbox token availability
    const tileUrl = React.useMemo(() => {
        if (mapboxToken) {
            // Use tileStyle prop if explicitly set, otherwise use config style
            const effectiveStyle = tileStyle !== 'default' ? tileStyle : mapboxStyle;
            return getMapboxTileUrl(effectiveStyle, mapboxToken, customStyleUrl);
        }
        return fallbackTileLayers[tileStyle] || fallbackTileLayers.default;
    }, [mapboxToken, mapboxStyle, customStyleUrl, tileStyle]);

    // Attribution based on tile provider
    const attribution = mapboxToken
        ? '&copy; <a href="https://www.mapbox.com/about/maps/">Mapbox</a> &copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
        : '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>';

    const handleMapClick = React.useCallback(
        (e: L.LeafletMouseEvent) => {
            onMapClick?.({ lat: e.latlng.lat, lng: e.latlng.lng });
        },
        [onMapClick]
    );

    // Compute center from addresses if not provided and addresses exist
    const computedCenter = React.useMemo(() => {
        if (addresses.length === 0) return center;
        const lats = addresses.map((a) => a.latitude);
        const lngs = addresses.map((a) => a.longitude);
        return [
            (Math.min(...lats) + Math.max(...lats)) / 2,
            (Math.min(...lngs) + Math.max(...lngs)) / 2,
        ] as [number, number];
    }, [addresses, center]);

    return (
        <MapContainer
            center={computedCenter}
            zoom={zoom}
            className={className}
            style={{ width: '100%', height: '100%' }}
            zoomControl={false}
            attributionControl={false}
            dragging={interactive}
            scrollWheelZoom={interactive}
            doubleClickZoom={interactive}
            touchZoom={interactive}
        >
            {/* Tile Layer */}
            <TileLayer
                url={tileUrl}
                attribution={attribution}
                tileSize={mapboxToken ? 512 : 256}
                zoomOffset={mapboxToken ? -1 : 0}
            />

            {/* Zoom Control - Top Right */}
            <ZoomControl position="topright" />

            {/* Attribution - Bottom Right */}
            <AttributionControl position="bottomright" />

            {/* Map Controller */}
            <MapController onBoundsChange={onBoundsChange} />

            {/* Heatmap Layer */}
            {showHeatmap && heatmapData.length > 0 && (
                <HeatmapLayer data={heatmapData} />
            )}

            {/* Zone Layers */}
            {showZones &&
                zones.map((zone) => (
                    <ZoneLayer
                        key={zone.id}
                        zone={zone}
                        isSelected={zone.id === selectedZoneId}
                        onClick={onZoneClick}
                    />
                ))}

            {/* Address Markers with Clustering */}
            {showClusters ? (
                <MarkerClusterGroup
                    chunkedLoading
                    maxClusterRadius={60}
                    spiderfyOnMaxZoom
                    showCoverageOnHover={false}
                    iconCreateFunction={(cluster) => createClusterIcon(cluster.getChildCount())}
                >
                    {addresses.map((address) => (
                        <AddressMarker
                            key={address.id}
                            address={address}
                            isSelected={address.id === selectedAddressId}
                            onClick={onAddressClick}
                        />
                    ))}
                </MarkerClusterGroup>
            ) : (
                addresses.map((address) => (
                    <AddressMarker
                        key={address.id}
                        address={address}
                        isSelected={address.id === selectedAddressId}
                        onClick={onAddressClick}
                    />
                ))
            )}

            {/* Custom children (drawing tools, etc.) */}
            {children}
        </MapContainer>
    );
}

export default EnhancedMap;
