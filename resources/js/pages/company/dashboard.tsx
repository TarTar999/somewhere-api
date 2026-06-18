import * as React from 'react';
import { Head, Link } from '@inertiajs/react';
import {
    Users,
    MapPin,
    FileText,
    Calendar,
    AlertCircle,
    Plus,
    Map,
    Settings,
    TrendingUp,
    ChevronRight,
    Download,
    Eye,
    EyeOff,
    Layers,
    Building2,
} from 'lucide-react';
import { MapDashboardLayout } from '@/layouts/map-dashboard-layout';
import { EnhancedMap, type Address as MapAddress, type Zone as MapZone } from '@/components/map/enhanced-map';
import { FloatingPanel, FloatingPanelHeader, FloatingPanelContent, FloatingPanelFooter } from '@/components/ui/floating-panel';
import { StatsCard, StatsCardGrid } from '@/components/ui/stats-card';
import { StatusBadge, SubscriptionStatusBadge, DocumentStatusBadge } from '@/components/ui/status-badge';
import { SideDrawer, SideDrawerContent, SideDrawerSection } from '@/components/ui/side-drawer';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Progress } from '@/components/ui/progress';
import type { SearchResult } from '@/components/ui/map-search-bar';
import type { CompanyRole, CompanyStats, CompanySubscription, CompanyDocument, CompanyMember } from '@/types/company';

interface Zone {
    id: number;
    name: string;
    type: 'circle' | 'polygon';
    coordinates?: [number, number][];
    center?: { lat: number; lng: number };
    radius?: number;
    fill_color?: string;
    stroke_color?: string;
    status: 'active' | 'inactive' | 'archived';
    addressCount?: number;
}

interface Address {
    id: number;
    swAddress: string;
    quarter: string;
    latitude: number;
    longitude: number;
    verificationStatus: 'pending' | 'approved' | 'rejected';
    label?: {
        name: string;
        color: string;
    };
    createdBy?: string;
}

interface Props {
    company: {
        id: number;
        name: string;
        logo?: string;
        status: string;
    };
    userRole: CompanyRole;
    stats: CompanyStats;
    subscription: CompanySubscription | null;
    recentDocuments: CompanyDocument[];
    recentMembers: CompanyMember[];
    zones?: Zone[];
    addresses?: Address[];
}

export default function CompanyDashboard({
    company,
    userRole,
    stats,
    subscription,
    recentDocuments,
    recentMembers,
    zones = [],
    addresses = [],
}: Props) {
    const [searchValue, setSearchValue] = React.useState('');
    const [searchResults, setSearchResults] = React.useState<SearchResult[]>([]);
    const [selectedZone, setSelectedZone] = React.useState<Zone | null>(null);
    const [selectedAddress, setSelectedAddress] = React.useState<Address | null>(null);
    const [drawerOpen, setDrawerOpen] = React.useState(false);
    const [drawerType, setDrawerType] = React.useState<'zone' | 'address'>('zone');
    const [showZones, setShowZones] = React.useState(true);
    const [showHeatmap, setShowHeatmap] = React.useState(false);

    // Calculate progress
    const documentsProgress = stats.documentsLimit > 0 ? (stats.documentsThisMonth / stats.documentsLimit) * 100 : 0;
    const membersProgress = stats.maxMembers > 0 ? (stats.totalMembers / stats.maxMembers) * 100 : 0;

    // Convert addresses to map format
    const mapAddresses: MapAddress[] = React.useMemo(() => {
        return addresses.map((addr) => ({
            id: addr.id,
            latitude: addr.latitude,
            longitude: addr.longitude,
            sw_address: addr.swAddress,
            quarter: addr.quarter,
            verification_status: addr.verificationStatus,
            label: addr.label,
        }));
    }, [addresses]);

    // Convert zones to map format
    const mapZones: MapZone[] = React.useMemo(() => {
        return zones.map((zone) => ({
            id: zone.id,
            name: zone.name,
            type: zone.type,
            coordinates: zone.coordinates,
            center: zone.center ? [zone.center.lat, zone.center.lng] as [number, number] : undefined,
            radius: zone.radius,
            fill_color: zone.fill_color,
            stroke_color: zone.stroke_color,
            status: zone.status,
        }));
    }, [zones]);

    // Handle search
    const handleSearch = React.useCallback((query: string) => {
        setSearchValue(query);
        if (!query) {
            setSearchResults([]);
            return;
        }

        const results: SearchResult[] = [];

        // Search zones
        zones
            .filter((z) => z.name.toLowerCase().includes(query.toLowerCase()))
            .slice(0, 3)
            .forEach((zone) => {
                results.push({
                    id: `zone_${zone.id}`,
                    title: zone.name,
                    subtitle: `Zone ${zone.type === 'circle' ? 'circulaire' : 'polygone'}`,
                    type: 'zone',
                });
            });

        // Search addresses
        addresses
            .filter((a) =>
                a.swAddress.toLowerCase().includes(query.toLowerCase()) ||
                a.quarter.toLowerCase().includes(query.toLowerCase())
            )
            .slice(0, 3)
            .forEach((addr) => {
                results.push({
                    id: String(addr.id),
                    title: addr.swAddress,
                    subtitle: addr.quarter,
                    coordinates: [addr.latitude, addr.longitude],
                    type: 'address',
                });
            });

        setSearchResults(results);
    }, [zones, addresses]);

    // Handle search result select
    const handleSearchResultSelect = React.useCallback((result: SearchResult) => {
        if (result.type === 'zone') {
            const zone = zones.find((z) => `zone_${z.id}` === result.id);
            if (zone) {
                setSelectedZone(zone);
                setDrawerType('zone');
                setDrawerOpen(true);
            }
        } else {
            const address = addresses.find((a) => String(a.id) === result.id);
            if (address) {
                setSelectedAddress(address);
                setDrawerType('address');
                setDrawerOpen(true);
            }
        }
        setSearchValue('');
        setSearchResults([]);
    }, [zones, addresses]);

    // Handle zone click
    const handleZoneClick = React.useCallback((zone: MapZone) => {
        const fullZone = zones.find((z) => z.id === zone.id);
        if (fullZone) {
            setSelectedZone(fullZone);
            setDrawerType('zone');
            setDrawerOpen(true);
        }
    }, [zones]);

    // Handle address click
    const handleAddressClick = React.useCallback((addr: MapAddress) => {
        const fullAddr = addresses.find((a) => a.id === addr.id);
        if (fullAddr) {
            setSelectedAddress(fullAddr);
            setDrawerType('address');
            setDrawerOpen(true);
        }
    }, [addresses]);

    return (
        <>
            <Head title={`${company.name} - Dashboard`} />

            <MapDashboardLayout
                type="company"
                showSearch
                searchValue={searchValue}
                onSearchChange={handleSearch}
                searchResults={searchResults}
                onSearchResultSelect={handleSearchResultSelect}
                headerExtra={
                    <div className="hidden md:flex items-center gap-2 ml-4">
                        <Badge variant="outline" className="font-medium">
                            <Building2 className="h-3 w-3 mr-1" />
                            {company.name}
                        </Badge>
                    </div>
                }
                mapComponent={
                    <EnhancedMap
                        addresses={mapAddresses}
                        zones={mapZones}
                        showZones={showZones}
                        showHeatmap={showHeatmap}
                        showClusters={true}
                        selectedZoneId={selectedZone?.id}
                        selectedAddressId={selectedAddress?.id}
                        onZoneClick={handleZoneClick}
                        onAddressClick={handleAddressClick}
                        tileStyle="default"
                    />
                }
                floatingActions={
                    <div className="flex flex-col gap-2">
                        {/* Map Controls */}
                        <div className="glass-panel rounded-lg p-2 flex flex-col gap-1">
                            <button
                                onClick={() => setShowZones(!showZones)}
                                className={`p-2 rounded-md transition-colors ${showZones ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}
                                title="Afficher les zones"
                            >
                                <Map className="h-5 w-5" />
                            </button>
                            <button
                                onClick={() => setShowHeatmap(!showHeatmap)}
                                className={`p-2 rounded-md transition-colors ${showHeatmap ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}
                                title="Carte thermique"
                            >
                                <Layers className="h-5 w-5" />
                            </button>
                        </div>

                        {/* Quick Actions */}
                        {(userRole === 'admin' || userRole === 'manager') && (
                            <Button asChild className="quick-action-btn shadow-lg">
                                <Link href="/company/zones/create">
                                    <Plus className="h-5 w-5" />
                                </Link>
                            </Button>
                        )}
                    </div>
                }
            >
                {/* Subscription Warning */}
                {subscription && subscription.daysUntilRenewal <= 7 && (
                    <FloatingPanel position="top-right" size="sm" className="hidden md:block">
                        <div className="flex items-center gap-3 text-warning">
                            <AlertCircle className="h-5 w-5 flex-shrink-0" />
                            <div>
                                <p className="font-medium text-sm">Renouvellement dans {subscription.daysUntilRenewal}j</p>
                                <p className="text-xs text-muted-foreground">
                                    Expire le {new Date(subscription.periodEnd).toLocaleDateString('fr-FR')}
                                </p>
                            </div>
                        </div>
                    </FloatingPanel>
                )}

                {/* Stats Panel - Top Left */}
                <FloatingPanel position="top-left" size="auto" className="hidden md:block">
                    <FloatingPanelContent>
                        <StatsCardGrid columns={2}>
                            <StatsCard
                                title="Membres"
                                value={`${stats.totalMembers}/${stats.maxMembers}`}
                                icon={<Users className="h-5 w-5" />}
                                size="sm"
                                variant={membersProgress > 90 ? 'warning' : 'default'}
                            />
                            <StatsCard
                                title="Adresses"
                                value={stats.totalAddresses}
                                icon={<MapPin className="h-5 w-5" />}
                                size="sm"
                            />
                            <StatsCard
                                title="Documents"
                                value={`${stats.documentsThisMonth}/${stats.documentsLimit}`}
                                subtitle={`${stats.documentsRemaining} restants`}
                                icon={<FileText className="h-5 w-5" />}
                                size="sm"
                                variant={documentsProgress > 90 ? 'warning' : 'default'}
                            />
                            <StatsCard
                                title="Zones"
                                value={zones.filter((z) => z.status === 'active').length}
                                subtitle={`${zones.length} total`}
                                icon={<Map className="h-5 w-5" />}
                                size="sm"
                            />
                        </StatsCardGrid>

                        {/* Subscription Info */}
                        {subscription && (
                            <div className="mt-4 p-3 rounded-lg bg-muted/50">
                                <div className="flex items-center justify-between">
                                    <span className="text-sm font-medium">{subscription.planName}</span>
                                    <SubscriptionStatusBadge status={subscription.status as any} size="sm" />
                                </div>
                            </div>
                        )}
                    </FloatingPanelContent>

                    <FloatingPanelFooter>
                        <Button asChild variant="outline" size="sm" className="w-full">
                            <Link href="/company/zones">
                                <Map className="mr-2 h-4 w-4" />
                                Gérer zones
                            </Link>
                        </Button>
                        <Button asChild variant="outline" size="sm" className="w-full">
                            <Link href="/company/members">
                                <Users className="mr-2 h-4 w-4" />
                                Membres
                            </Link>
                        </Button>
                    </FloatingPanelFooter>
                </FloatingPanel>

                {/* Zones List Panel - Bottom Left */}
                <FloatingPanel position="bottom-left" size="md" className="hidden md:block max-h-72">
                    <FloatingPanelHeader
                        title="Zones actives"
                        subtitle={`${zones.filter((z) => z.status === 'active').length} zones`}
                        icon={<Map className="h-5 w-5" />}
                    />
                    <FloatingPanelContent>
                        {zones.length === 0 ? (
                            <div className="text-center py-4">
                                <p className="text-sm text-muted-foreground mb-3">Aucune zone créée</p>
                                <Button asChild size="sm" variant="outline">
                                    <Link href="/company/zones/create">
                                        <Plus className="mr-2 h-4 w-4" />
                                        Créer une zone
                                    </Link>
                                </Button>
                            </div>
                        ) : (
                            <div className="space-y-2 max-h-40 overflow-y-auto">
                                {zones.filter((z) => z.status === 'active').slice(0, 5).map((zone) => (
                                    <div
                                        key={zone.id}
                                        className="flex items-center justify-between p-2 rounded-lg hover:bg-muted/50 transition-colors cursor-pointer"
                                        onClick={() => {
                                            setSelectedZone(zone);
                                            setDrawerType('zone');
                                            setDrawerOpen(true);
                                        }}
                                    >
                                        <div className="flex items-center gap-2">
                                            <div
                                                className="w-3 h-3 rounded-full"
                                                style={{ backgroundColor: zone.fill_color || 'var(--info)' }}
                                            />
                                            <span className="text-sm font-medium">{zone.name}</span>
                                        </div>
                                        <Badge variant="outline" className="text-xs">
                                            {zone.addressCount || 0} adr.
                                        </Badge>
                                    </div>
                                ))}
                            </div>
                        )}
                    </FloatingPanelContent>
                </FloatingPanel>

                {/* Mobile Content */}
                <div className="md:hidden space-y-4">
                    {/* Subscription Warning Mobile */}
                    {subscription && subscription.daysUntilRenewal <= 7 && (
                        <Card className="border-warning/20 bg-warning/5">
                            <CardContent className="flex items-center gap-3 pt-4 pb-4">
                                <AlertCircle className="h-5 w-5 text-warning" />
                                <div>
                                    <p className="font-medium text-sm">Renouvellement dans {subscription.daysUntilRenewal}j</p>
                                </div>
                            </CardContent>
                        </Card>
                    )}

                    {/* Mobile Stats */}
                    <StatsCardGrid columns={2}>
                        <StatsCard
                            title="Membres"
                            value={stats.totalMembers}
                            icon={<Users className="h-5 w-5" />}
                            size="sm"
                        />
                        <StatsCard
                            title="Zones"
                            value={zones.length}
                            icon={<Map className="h-5 w-5" />}
                            size="sm"
                        />
                    </StatsCardGrid>

                    {/* Quick Actions Mobile */}
                    <div className="grid grid-cols-2 gap-2">
                        <Button asChild variant="outline" size="sm">
                            <Link href="/company/zones">
                                <Map className="mr-2 h-4 w-4" />
                                Zones
                            </Link>
                        </Button>
                        <Button asChild variant="outline" size="sm">
                            <Link href="/company/members">
                                <Users className="mr-2 h-4 w-4" />
                                Membres
                            </Link>
                        </Button>
                        <Button asChild variant="outline" size="sm">
                            <Link href="/company/addresses">
                                <MapPin className="mr-2 h-4 w-4" />
                                Adresses
                            </Link>
                        </Button>
                        <Button asChild variant="outline" size="sm">
                            <Link href="/company/settings">
                                <Settings className="mr-2 h-4 w-4" />
                                Paramètres
                            </Link>
                        </Button>
                    </div>

                    {/* Recent Documents Mobile */}
                    <div className="space-y-2">
                        <h3 className="font-semibold text-sm">Documents récents</h3>
                        {recentDocuments.length === 0 ? (
                            <p className="text-sm text-muted-foreground text-center py-4">
                                Aucun document
                            </p>
                        ) : (
                            recentDocuments.slice(0, 3).map((doc) => (
                                <div
                                    key={doc.id}
                                    className="flex items-center justify-between p-3 rounded-lg border"
                                >
                                    <div className="flex-1 min-w-0">
                                        <p className="font-medium text-sm truncate">{doc.documentTypeLabel}</p>
                                        <p className="text-xs text-muted-foreground truncate">par {doc.createdBy}</p>
                                    </div>
                                    <DocumentStatusBadge
                                        status={doc.isExpired ? 'expired' : 'active'}
                                        size="sm"
                                    />
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </MapDashboardLayout>

            {/* Zone/Address Detail Drawer */}
            <SideDrawer
                open={drawerOpen}
                onOpenChange={setDrawerOpen}
                title={drawerType === 'zone' ? selectedZone?.name : selectedAddress?.swAddress || 'Détails'}
                description={drawerType === 'zone' ? `Zone ${selectedZone?.type}` : selectedAddress?.quarter}
            >
                {drawerType === 'zone' && selectedZone && (
                    <>
                        <SideDrawerContent>
                            <Card>
                                <CardHeader className="pb-3">
                                    <div className="flex items-center justify-between">
                                        <CardTitle className="text-base">Informations</CardTitle>
                                        <StatusBadge
                                            variant={selectedZone.status === 'active' ? 'success' : 'pending'}
                                            label={selectedZone.status === 'active' ? 'Active' : 'Inactive'}
                                        />
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    <div>
                                        <p className="text-sm text-muted-foreground">Nom</p>
                                        <p className="font-medium">{selectedZone.name}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-muted-foreground">Type</p>
                                        <p className="font-medium">
                                            {selectedZone.type === 'circle' ? 'Cercle' : 'Polygone'}
                                        </p>
                                    </div>
                                    {selectedZone.type === 'circle' && selectedZone.radius && (
                                        <div>
                                            <p className="text-sm text-muted-foreground">Rayon</p>
                                            <p className="font-medium">{selectedZone.radius} m</p>
                                        </div>
                                    )}
                                    <div>
                                        <p className="text-sm text-muted-foreground">Adresses dans la zone</p>
                                        <p className="font-medium">{selectedZone.addressCount || 0}</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </SideDrawerContent>

                        <SideDrawerSection title="Actions">
                            <div className="px-4 space-y-2">
                                <Button asChild variant="outline" className="w-full justify-start">
                                    <Link href={`/company/zones/${selectedZone.id}`}>
                                        <Eye className="mr-2 h-4 w-4" />
                                        Voir les détails
                                    </Link>
                                </Button>
                                {(userRole === 'admin' || userRole === 'manager') && (
                                    <Button asChild variant="outline" className="w-full justify-start">
                                        <Link href={`/company/zones/${selectedZone.id}/edit`}>
                                            <Settings className="mr-2 h-4 w-4" />
                                            Modifier
                                        </Link>
                                    </Button>
                                )}
                            </div>
                        </SideDrawerSection>
                    </>
                )}

                {drawerType === 'address' && selectedAddress && (
                    <SideDrawerContent>
                        <Card>
                            <CardHeader className="pb-3">
                                <div className="flex items-center justify-between">
                                    <CardTitle className="text-base">Adresse</CardTitle>
                                    <StatusBadge
                                        variant={
                                            selectedAddress.verificationStatus === 'approved'
                                                ? 'success'
                                                : selectedAddress.verificationStatus === 'rejected'
                                                ? 'error'
                                                : 'pending'
                                        }
                                        label={
                                            selectedAddress.verificationStatus === 'approved'
                                                ? 'Vérifié'
                                                : selectedAddress.verificationStatus === 'rejected'
                                                ? 'Rejeté'
                                                : 'En attente'
                                        }
                                    />
                                </div>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                <div>
                                    <p className="text-sm text-muted-foreground">Adresse SW</p>
                                    <p className="font-medium">{selectedAddress.swAddress}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Quartier</p>
                                    <p className="font-medium">{selectedAddress.quarter}</p>
                                </div>
                                {selectedAddress.label && (
                                    <div>
                                        <p className="text-sm text-muted-foreground">Étiquette</p>
                                        <Badge
                                            style={{ backgroundColor: selectedAddress.label.color }}
                                            className="text-white"
                                        >
                                            {selectedAddress.label.name}
                                        </Badge>
                                    </div>
                                )}
                                {selectedAddress.createdBy && (
                                    <div>
                                        <p className="text-sm text-muted-foreground">Créée par</p>
                                        <p className="font-medium">{selectedAddress.createdBy}</p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </SideDrawerContent>
                )}
            </SideDrawer>
        </>
    );
}
