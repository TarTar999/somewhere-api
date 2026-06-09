import { useState, useEffect } from 'react';

interface ZoneMapViewProps {
    zoneType: 'circle' | 'polygon';
    centerLat: number | null;
    centerLng: number | null;
    radiusMeters: number | null;
    polygonCoordinates: Array<{ lat: number; lng: number }> | null;
    fillColor: string;
    fillOpacity: number;
    strokeColor: string;
    strokeWidth: number;
    className?: string;
}

export function ZoneMapView({
    zoneType,
    centerLat,
    centerLng,
    radiusMeters,
    polygonCoordinates,
    fillColor,
    fillOpacity,
    strokeColor,
    strokeWidth,
    className = '',
}: ZoneMapViewProps) {
    const [MapComponents, setMapComponents] = useState<{
        MapContainer: any;
        TileLayer: any;
        Circle: any;
        Polygon: any;
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
            });
        }).catch((err) => {
            console.error('Failed to load map components:', err);
        });
    }, []);

    if (!MapComponents) {
        return (
            <div className={`flex items-center justify-center bg-muted ${className}`}>
                <p className="text-muted-foreground">Chargement de la carte...</p>
            </div>
        );
    }

    const { MapContainer, TileLayer, Circle, Polygon } = MapComponents;

    // Determine center
    let center: [number, number] = [3.848, 11.502]; // Default Cameroon
    if (zoneType === 'circle' && centerLat && centerLng) {
        center = [centerLat, centerLng];
    } else if (zoneType === 'polygon' && polygonCoordinates && polygonCoordinates.length > 0) {
        // Calculate centroid of polygon
        const avgLat = polygonCoordinates.reduce((sum, p) => sum + p.lat, 0) / polygonCoordinates.length;
        const avgLng = polygonCoordinates.reduce((sum, p) => sum + p.lng, 0) / polygonCoordinates.length;
        center = [avgLat, avgLng];
    }

    return (
        <MapContainer
            center={center}
            zoom={14}
            className={`h-full w-full ${className}`}
            scrollWheelZoom={true}
        >
            <TileLayer
                attribution='&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
                url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
            />

            {zoneType === 'circle' && centerLat && centerLng && radiusMeters && (
                <Circle
                    center={[centerLat, centerLng]}
                    radius={radiusMeters}
                    pathOptions={{
                        fillColor,
                        fillOpacity,
                        color: strokeColor,
                        weight: strokeWidth,
                    }}
                />
            )}

            {zoneType === 'polygon' && polygonCoordinates && polygonCoordinates.length >= 3 && (
                <Polygon
                    positions={polygonCoordinates.map((p) => [p.lat, p.lng])}
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
