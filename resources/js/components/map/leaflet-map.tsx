import { useEffect, useRef } from 'react';
import L from 'leaflet';
import 'leaflet/dist/leaflet.css';

// Fix Leaflet default marker icon issue
import markerIcon2x from 'leaflet/dist/images/marker-icon-2x.png';
import markerIcon from 'leaflet/dist/images/marker-icon.png';
import markerShadow from 'leaflet/dist/images/marker-shadow.png';

delete (L.Icon.Default.prototype as unknown as { _getIconUrl?: () => void })._getIconUrl;
L.Icon.Default.mergeOptions({
    iconRetinaUrl: markerIcon2x,
    iconUrl: markerIcon,
    shadowUrl: markerShadow,
});

interface MapConfig {
    zoom: number;
    maxZoom: number;
    tileUrl: string;
    attribution?: string;
}

interface Marker {
    id: number;
    position: [number, number];
    popup?: string;
    onClick?: () => void;
}

interface LeafletMapProps {
    center: [number, number];
    config: MapConfig;
    markers?: Marker[];
    onMapClick?: (lat: number, lng: number) => void;
    className?: string;
}

export function LeafletMap({ center, config, markers = [], onMapClick, className = '' }: LeafletMapProps) {
    const mapRef = useRef<L.Map | null>(null);
    const containerRef = useRef<HTMLDivElement>(null);
    const markersRef = useRef<L.Marker[]>([]);

    useEffect(() => {
        if (!containerRef.current || mapRef.current) return;

        // Initialize map
        const map = L.map(containerRef.current).setView(center, config.zoom);

        // Add tile layer
        L.tileLayer(config.tileUrl, {
            maxZoom: config.maxZoom,
            attribution: config.attribution,
        }).addTo(map);

        // Add click handler
        if (onMapClick) {
            map.on('click', (e) => {
                onMapClick(e.latlng.lat, e.latlng.lng);
            });
        }

        mapRef.current = map;

        return () => {
            map.remove();
            mapRef.current = null;
        };
    }, []);

    // Update center when it changes
    useEffect(() => {
        if (mapRef.current) {
            mapRef.current.setView(center, config.zoom);
        }
    }, [center, config.zoom]);

    // Update markers
    useEffect(() => {
        if (!mapRef.current) return;

        // Clear existing markers
        markersRef.current.forEach((marker) => marker.remove());
        markersRef.current = [];

        // Add new markers
        markers.forEach((markerData) => {
            const marker = L.marker(markerData.position).addTo(mapRef.current!);

            if (markerData.popup) {
                marker.bindPopup(markerData.popup);
            }

            if (markerData.onClick) {
                marker.on('click', markerData.onClick);
            }

            markersRef.current.push(marker);
        });
    }, [markers]);

    return <div ref={containerRef} className={`h-full w-full ${className}`} />;
}
