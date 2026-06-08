import { useState, useCallback } from 'react';
import { Head, Link } from '@inertiajs/react';
import { LeafletMap } from '@/components/map/leaflet-map';
import { SearchBar } from '@/components/map/search-bar';
import { Button } from '@/components/ui/button';
import { MapPin, ExternalLink } from 'lucide-react';

interface MapConfig {
    zoom: number;
    maxZoom: number;
    tileUrl: string;
    attribution: string;
}

interface Address {
    id: number;
    swAddress: string;
    latitude: number;
    longitude: number;
    streetName?: string;
    lieuDit?: string;
    quarter?: string;
    commune?: string;
    distance?: number;
}

interface Props {
    initialCenter: [number, number];
    mapConfig: MapConfig;
}

export default function PublicMap({ initialCenter, mapConfig }: Props) {
    const [center, setCenter] = useState<[number, number]>(initialCenter);
    const [selectedAddress, setSelectedAddress] = useState<Address | null>(null);
    const [markers, setMarkers] = useState<Array<{ id: number; position: [number, number]; popup?: string }>>([]);

    const handleSearch = useCallback(async (query: string): Promise<Address[]> => {
        const response = await fetch(`/map/search?q=${encodeURIComponent(query)}`);
        const data = await response.json();
        return data.data || [];
    }, []);

    const handleSelectResult = useCallback((result: Address) => {
        setCenter([result.latitude, result.longitude]);
        setSelectedAddress(result);
        setMarkers([
            {
                id: result.id,
                position: [result.latitude, result.longitude],
                popup: `<strong>${result.swAddress}</strong><br/>${result.quarter || ''} ${result.commune || ''}`,
            },
        ]);
    }, []);

    return (
        <>
            <Head title="Carte - SomeWhere" />

            <div className="relative h-screen w-full">
                {/* Header */}
                <div className="absolute left-0 right-0 top-0 z-[1000] bg-background/95 p-4 backdrop-blur supports-[backdrop-filter]:bg-background/60">
                    <div className="mx-auto flex max-w-7xl items-center justify-between gap-4">
                        <Link href="/" className="text-xl font-bold">
                            SomeWhere
                        </Link>

                        <SearchBar
                            onSearch={handleSearch}
                            onSelectResult={handleSelectResult}
                            placeholder="Rechercher une adresse SW..."
                        />

                        <div className="flex items-center gap-2">
                            <Link href="/login">
                                <Button variant="ghost" size="sm">
                                    Connexion
                                </Button>
                            </Link>
                            <Link href="/register">
                                <Button size="sm">Inscription</Button>
                            </Link>
                        </div>
                    </div>
                </div>

                {/* Map */}
                <LeafletMap center={center} config={mapConfig} markers={markers} className="h-full w-full" />

                {/* Selected Address Panel */}
                {selectedAddress && (
                    <div className="absolute bottom-4 left-4 z-[1000] w-80 rounded-lg border bg-background p-4 shadow-lg">
                        <div className="mb-2 flex items-start justify-between">
                            <div className="flex items-center gap-2">
                                <MapPin className="h-5 w-5 text-primary" />
                                <span className="font-semibold">{selectedAddress.swAddress}</span>
                            </div>
                            <button onClick={() => setSelectedAddress(null)} className="text-muted-foreground hover:text-foreground">
                                &times;
                            </button>
                        </div>

                        <div className="space-y-1 text-sm text-muted-foreground">
                            {selectedAddress.streetName && <p>{selectedAddress.streetName}</p>}
                            {selectedAddress.lieuDit && <p>Lieu-dit: {selectedAddress.lieuDit}</p>}
                            {selectedAddress.quarter && <p>Quartier: {selectedAddress.quarter}</p>}
                            {selectedAddress.commune && <p>Commune: {selectedAddress.commune}</p>}
                        </div>

                        <div className="mt-4">
                            <Link href={`/address/${selectedAddress.id}`}>
                                <Button size="sm" className="w-full">
                                    <ExternalLink className="mr-2 h-4 w-4" />
                                    Voir les détails
                                </Button>
                            </Link>
                        </div>
                    </div>
                )}
            </div>
        </>
    );
}
