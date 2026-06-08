import { Head, Link } from '@inertiajs/react';
import { LeafletMap } from '@/components/map/leaflet-map';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ArrowLeft, MapPin, Home, User, CheckCircle, Clock, XCircle } from 'lucide-react';

interface Address {
    id: number;
    swAddress: string;
    latitude: number;
    longitude: number;
    streetNumber?: string;
    streetName?: string;
    lieuDit?: string;
    quarter?: string;
    subQuarter?: string;
    commune?: string;
    houseType?: string;
    homeStatus?: string;
    verificationStatus: string;
    owner?: {
        name: string;
    };
}

interface MapConfig {
    center: [number, number];
    zoom: number;
}

interface Props {
    address: Address;
    mapConfig: MapConfig;
}

export default function PublicAddress({ address, mapConfig }: Props) {
    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'approved':
                return (
                    <Badge className="bg-green-500">
                        <CheckCircle className="mr-1 h-3 w-3" />
                        Vérifiée
                    </Badge>
                );
            case 'pending':
                return (
                    <Badge variant="secondary">
                        <Clock className="mr-1 h-3 w-3" />
                        En attente
                    </Badge>
                );
            default:
                return (
                    <Badge variant="destructive">
                        <XCircle className="mr-1 h-3 w-3" />
                        Non vérifiée
                    </Badge>
                );
        }
    };

    return (
        <>
            <Head title={`${address.swAddress} - SomeWhere`} />

            <div className="min-h-screen bg-muted/30">
                {/* Header */}
                <header className="border-b bg-background">
                    <div className="mx-auto flex max-w-7xl items-center justify-between px-4 py-4">
                        <div className="flex items-center gap-4">
                            <Link href="/map">
                                <Button variant="ghost" size="icon">
                                    <ArrowLeft className="h-5 w-5" />
                                </Button>
                            </Link>
                            <Link href="/" className="text-xl font-bold">
                                SomeWhere
                            </Link>
                        </div>
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
                </header>

                <div className="mx-auto max-w-7xl px-4 py-8">
                    <div className="grid gap-6 lg:grid-cols-2">
                        {/* Address Details */}
                        <div className="space-y-6">
                            <Card>
                                <CardHeader>
                                    <div className="flex items-start justify-between">
                                        <div className="flex items-center gap-3">
                                            <div className="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                                                <MapPin className="h-6 w-6 text-primary" />
                                            </div>
                                            <div>
                                                <CardTitle className="text-2xl">{address.swAddress}</CardTitle>
                                                <CardDescription>{address.streetName || 'Adresse SomeWhere'}</CardDescription>
                                            </div>
                                        </div>
                                        {getStatusBadge(address.verificationStatus)}
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <dl className="grid gap-4 sm:grid-cols-2">
                                        {address.streetNumber && (
                                            <div>
                                                <dt className="text-sm font-medium text-muted-foreground">Numéro de rue</dt>
                                                <dd className="text-sm">{address.streetNumber}</dd>
                                            </div>
                                        )}
                                        {address.lieuDit && (
                                            <div>
                                                <dt className="text-sm font-medium text-muted-foreground">Lieu-dit</dt>
                                                <dd className="text-sm">{address.lieuDit}</dd>
                                            </div>
                                        )}
                                        {address.quarter && (
                                            <div>
                                                <dt className="text-sm font-medium text-muted-foreground">Quartier</dt>
                                                <dd className="text-sm">{address.quarter}</dd>
                                            </div>
                                        )}
                                        {address.subQuarter && (
                                            <div>
                                                <dt className="text-sm font-medium text-muted-foreground">Sous-quartier</dt>
                                                <dd className="text-sm">{address.subQuarter}</dd>
                                            </div>
                                        )}
                                        {address.commune && (
                                            <div>
                                                <dt className="text-sm font-medium text-muted-foreground">Commune</dt>
                                                <dd className="text-sm">{address.commune}</dd>
                                            </div>
                                        )}
                                    </dl>
                                </CardContent>
                            </Card>

                            {/* Property Info */}
                            <Card>
                                <CardHeader>
                                    <div className="flex items-center gap-2">
                                        <Home className="h-5 w-5" />
                                        <CardTitle className="text-lg">Informations sur la propriété</CardTitle>
                                    </div>
                                </CardHeader>
                                <CardContent>
                                    <dl className="grid gap-4 sm:grid-cols-2">
                                        {address.houseType && (
                                            <div>
                                                <dt className="text-sm font-medium text-muted-foreground">Type de logement</dt>
                                                <dd className="text-sm capitalize">{address.houseType}</dd>
                                            </div>
                                        )}
                                        {address.homeStatus && (
                                            <div>
                                                <dt className="text-sm font-medium text-muted-foreground">Statut</dt>
                                                <dd className="text-sm capitalize">{address.homeStatus}</dd>
                                            </div>
                                        )}
                                    </dl>
                                </CardContent>
                            </Card>

                            {/* Owner Info */}
                            {address.owner && (
                                <Card>
                                    <CardHeader>
                                        <div className="flex items-center gap-2">
                                            <User className="h-5 w-5" />
                                            <CardTitle className="text-lg">Propriétaire</CardTitle>
                                        </div>
                                    </CardHeader>
                                    <CardContent>
                                        <p className="text-sm">{address.owner.name}</p>
                                    </CardContent>
                                </Card>
                            )}

                            {/* Coordinates */}
                            <Card>
                                <CardContent className="pt-6">
                                    <div className="text-center text-sm text-muted-foreground">
                                        <p>
                                            Coordonnées GPS: {address.latitude.toFixed(6)}, {address.longitude.toFixed(6)}
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Map */}
                        <Card className="overflow-hidden">
                            <div className="h-[500px]">
                                <LeafletMap
                                    center={mapConfig.center}
                                    config={{
                                        zoom: mapConfig.zoom,
                                        maxZoom: 19,
                                        tileUrl: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                                        attribution: '&copy; OpenStreetMap',
                                    }}
                                    markers={[
                                        {
                                            id: address.id,
                                            position: [address.latitude, address.longitude],
                                            popup: `<strong>${address.swAddress}</strong>`,
                                        },
                                    ]}
                                />
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </>
    );
}
