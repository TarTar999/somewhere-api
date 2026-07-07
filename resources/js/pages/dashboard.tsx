import * as React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { MapPin, FileText, FolderOpen, Truck, Building2, Plus, Download, CheckCircle, Clock, ChevronRight, Layers, Eye, EyeOff, List, Map as MapIcon, Search, Filter, Sparkles } from 'lucide-react';
import { MapDashboardLayout } from '@/layouts/map-dashboard-layout';
import { EnhancedMap, type Address as MapAddress, type HeatmapPoint } from '@/components/map/enhanced-map';
import { FloatingPanel, FloatingPanelHeader, FloatingPanelContent, FloatingPanelFooter } from '@/components/ui/floating-panel';
import { StatsCard, StatsCardGrid } from '@/components/ui/stats-card';
import { StatusBadge, DocumentStatusBadge } from '@/components/ui/status-badge';
import { SideDrawer, SideDrawerContent, SideDrawerSection } from '@/components/ui/side-drawer';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import type { SearchResult } from '@/components/ui/map-search-bar';

// Types
interface Address {
    id: number;
    swAddress: string;
    displayName: string;
    quarter: string;
    subQuarter: string;
    houseType: string;
    verificationStatus: 'pending' | 'approved' | 'rejected';
    latitude: number;
    longitude: number;
    street: {
        code: string;
        displayName: string;
    } | null;
    createdAt: string;
}

interface Document {
    id: number;
    documentType: string;
    documentTypeLabel: string;
    documentNumber: string;
    verificationCode: string;
    status: string;
    isActive: boolean;
    isExpired: boolean;
    issuedAt: string;
    expiresAt: string;
    downloadCount: number;
    address: {
        id: number;
        swAddress: string;
        displayName: string;
    } | null;
}

interface Collection {
    id: number;
    name: string;
    description: string | null;
    addressCount: number;
    isPublic: boolean;
    createdAt: string;
}

interface Stats {
    totalAddresses: number;
    verifiedAddresses: number;
    pendingAddresses: number;
    totalDocuments: number;
    activeDocuments: number;
    expiredDocuments: number;
    totalCollections: number;
    pendingDeliveries: number;
}

interface Props {
    addresses: Address[];
    documents: Document[];
    collections: Collection[];
    stats: Stats;
    hasCompany: boolean;
}

function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

export default function Dashboard({ addresses, documents, collections, stats, hasCompany }: Props) {
    const [searchValue, setSearchValue] = React.useState('');
    const [searchResults, setSearchResults] = React.useState<SearchResult[]>([]);
    const [selectedAddress, setSelectedAddress] = React.useState<Address | null>(null);
    const [drawerOpen, setDrawerOpen] = React.useState(false);
    const [showHeatmap, setShowHeatmap] = React.useState(false);
    const [showClusters, setShowClusters] = React.useState(true);
    const [viewMode, setViewMode] = React.useState<'map' | 'list'>('map');
    const [addressFilter, setAddressFilter] = React.useState('');

    // Filter addresses for list view
    const filteredAddresses = React.useMemo(() => {
        if (!addressFilter) return addresses;
        const query = addressFilter.toLowerCase();
        return addresses.filter(
            (addr) =>
                addr.swAddress.toLowerCase().includes(query) ||
                addr.quarter.toLowerCase().includes(query) ||
                addr.displayName.toLowerCase().includes(query)
        );
    }, [addresses, addressFilter]);

    // Convert addresses to map format
    const mapAddresses: MapAddress[] = React.useMemo(() => {
        return addresses.map((addr) => ({
            id: addr.id,
            latitude: addr.latitude,
            longitude: addr.longitude,
            sw_address: addr.swAddress,
            street_name: addr.street?.displayName,
            quarter: addr.quarter,
            verification_status: addr.verificationStatus,
        }));
    }, [addresses]);

    // Generate heatmap data
    const heatmapData: HeatmapPoint[] = React.useMemo(() => {
        return addresses.map((addr) => ({
            lat: addr.latitude,
            lng: addr.longitude,
            intensity: addr.verificationStatus === 'approved' ? 1 : 0.5,
        }));
    }, [addresses]);

    // Handle search
    const handleSearch = React.useCallback((query: string) => {
        setSearchValue(query);
        if (!query) {
            setSearchResults([]);
            return;
        }

        const results: SearchResult[] = addresses
            .filter((addr) =>
                addr.swAddress.toLowerCase().includes(query.toLowerCase()) ||
                addr.quarter.toLowerCase().includes(query.toLowerCase()) ||
                addr.street?.displayName.toLowerCase().includes(query.toLowerCase())
            )
            .slice(0, 5)
            .map((addr) => ({
                id: String(addr.id),
                title: addr.swAddress,
                subtitle: addr.quarter,
                coordinates: [addr.latitude, addr.longitude] as [number, number],
                type: 'address' as const,
            }));

        setSearchResults(results);
    }, [addresses]);

    // Handle search result select
    const handleSearchResultSelect = React.useCallback((result: SearchResult) => {
        const address = addresses.find((a) => String(a.id) === result.id);
        if (address) {
            setSelectedAddress(address);
            setDrawerOpen(true);
        }
        setSearchValue('');
        setSearchResults([]);
    }, [addresses]);

    // Handle address click on map
    const handleAddressClick = React.useCallback((addr: MapAddress) => {
        const fullAddress = addresses.find((a) => a.id === addr.id);
        if (fullAddress) {
            setSelectedAddress(fullAddress);
            setDrawerOpen(true);
        }
    }, [addresses]);

    // Get documents for selected address
    const selectedAddressDocuments = React.useMemo(() => {
        if (!selectedAddress) return [];
        return documents.filter((doc) => doc.address?.id === selectedAddress.id);
    }, [selectedAddress, documents]);

    return (
        <>
            <Head title="Tableau de bord" />

            <MapDashboardLayout
                type="user"
                showSearch
                searchValue={searchValue}
                onSearchChange={handleSearch}
                searchResults={searchResults}
                onSearchResultSelect={handleSearchResultSelect}
                mapComponent={
                    <EnhancedMap
                        addresses={mapAddresses}
                        heatmapData={heatmapData}
                        showHeatmap={showHeatmap}
                        showClusters={showClusters}
                        selectedAddressId={selectedAddress?.id}
                        onAddressClick={handleAddressClick}
                        tileStyle="default"
                    />
                }
                floatingActions={
                    <div className="flex flex-col gap-2">
                        {/* View Toggle */}
                        <div className="bg-white/90 backdrop-blur-md rounded-xl p-1.5 flex flex-col gap-1 shadow-lg border border-gray-200/50">
                            <button
                                onClick={() => setViewMode('map')}
                                className={`p-2.5 rounded-lg transition-all ${viewMode === 'map' ? 'bg-gradient-to-r from-indigo-500 to-violet-500 text-white shadow-md' : 'hover:bg-gray-100 text-gray-600'}`}
                                title="Vue carte"
                            >
                                <MapIcon className="h-5 w-5" />
                            </button>
                            <button
                                onClick={() => setViewMode('list')}
                                className={`p-2.5 rounded-lg transition-all ${viewMode === 'list' ? 'bg-gradient-to-r from-indigo-500 to-violet-500 text-white shadow-md' : 'hover:bg-gray-100 text-gray-600'}`}
                                title="Vue liste"
                            >
                                <List className="h-5 w-5" />
                            </button>
                        </div>

                        {/* Map Layer Controls - only show in map view */}
                        {viewMode === 'map' && (
                            <div className="bg-white/90 backdrop-blur-md rounded-xl p-1.5 flex flex-col gap-1 shadow-lg border border-gray-200/50">
                                <button
                                    onClick={() => setShowHeatmap(!showHeatmap)}
                                    className={`p-2.5 rounded-lg transition-all ${showHeatmap ? 'bg-gradient-to-r from-indigo-500 to-violet-500 text-white shadow-md' : 'hover:bg-gray-100 text-gray-600'}`}
                                    title="Carte thermique"
                                >
                                    <Layers className="h-5 w-5" />
                                </button>
                                <button
                                    onClick={() => setShowClusters(!showClusters)}
                                    className={`p-2.5 rounded-lg transition-all ${showClusters ? 'bg-gradient-to-r from-indigo-500 to-violet-500 text-white shadow-md' : 'hover:bg-gray-100 text-gray-600'}`}
                                    title="Clustering"
                                >
                                    {showClusters ? <Eye className="h-5 w-5" /> : <EyeOff className="h-5 w-5" />}
                                </button>
                            </div>
                        )}

                        {/* Quick Actions */}
                        <Button
                            onClick={() => setViewMode('list')}
                            className="bg-gradient-to-r from-indigo-500 to-violet-500 hover:from-indigo-600 hover:to-violet-600 text-white shadow-lg shadow-indigo-500/30 border-0 rounded-xl h-12 w-12"
                        >
                            <Plus className="h-5 w-5" />
                        </Button>
                    </div>
                }
            >
                {/* List View Overlay */}
                {viewMode === 'list' && (
                    <div className="absolute inset-0 z-10 bg-gradient-to-b from-gray-50 to-white overflow-auto">
                        <div className="max-w-4xl mx-auto pt-20 px-4 pb-4 md:pt-24 md:px-6 md:pb-6">
                            {/* Header */}
                            <div className="flex items-center justify-between mb-6">
                                <div>
                                    <h1 className="text-2xl font-bold bg-gradient-to-r from-indigo-600 to-violet-600 bg-clip-text text-transparent">Mes Adresses</h1>
                                    <p className="text-gray-500">{addresses.length} adresse(s) enregistrée(s)</p>
                                </div>
                                <Button asChild className="bg-gradient-to-r from-indigo-500 to-violet-500 hover:from-indigo-600 hover:to-violet-600 text-white shadow-lg shadow-indigo-500/25">
                                    <Link href="/collections/create">
                                        <Plus className="h-4 w-4 mr-2" />
                                        Nouvelle collection
                                    </Link>
                                </Button>
                            </div>

                            {/* Search/Filter */}
                            <div className="mb-6">
                                <div className="relative">
                                    <Search className="absolute left-4 top-1/2 -translate-y-1/2 h-5 w-5 text-gray-400" />
                                    <input
                                        type="text"
                                        placeholder="Rechercher une adresse..."
                                        value={addressFilter}
                                        onChange={(e) => setAddressFilter(e.target.value)}
                                        className="w-full pl-12 pr-4 py-3 border border-gray-200 rounded-xl bg-white focus:outline-none focus:ring-2 focus:ring-indigo-500/50 focus:border-indigo-500 transition-all shadow-sm"
                                    />
                                </div>
                            </div>

                            {/* Stats */}
                            <div className="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                                <div className="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                                    <div className="flex items-center gap-3">
                                        <div className="p-2.5 bg-gradient-to-br from-indigo-100 to-violet-100 rounded-xl">
                                            <MapPin className="h-5 w-5 text-indigo-600" />
                                        </div>
                                        <div>
                                            <p className="text-2xl font-bold text-gray-900">{stats.totalAddresses}</p>
                                            <p className="text-sm text-gray-500">Total</p>
                                        </div>
                                    </div>
                                </div>
                                <div className="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                                    <div className="flex items-center gap-3">
                                        <div className="p-2.5 bg-gradient-to-br from-green-100 to-emerald-100 rounded-xl">
                                            <CheckCircle className="h-5 w-5 text-green-600" />
                                        </div>
                                        <div>
                                            <p className="text-2xl font-bold text-gray-900">{stats.verifiedAddresses}</p>
                                            <p className="text-sm text-gray-500">Vérifiées</p>
                                        </div>
                                    </div>
                                </div>
                                <div className="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                                    <div className="flex items-center gap-3">
                                        <div className="p-2.5 bg-gradient-to-br from-amber-100 to-yellow-100 rounded-xl">
                                            <Clock className="h-5 w-5 text-amber-600" />
                                        </div>
                                        <div>
                                            <p className="text-2xl font-bold text-gray-900">{stats.pendingAddresses}</p>
                                            <p className="text-sm text-gray-500">En attente</p>
                                        </div>
                                    </div>
                                </div>
                                <div className="bg-white rounded-xl p-4 shadow-sm border border-gray-100 hover:shadow-md transition-shadow">
                                    <div className="flex items-center gap-3">
                                        <div className="p-2.5 bg-gradient-to-br from-violet-100 to-purple-100 rounded-xl">
                                            <FileText className="h-5 w-5 text-violet-600" />
                                        </div>
                                        <div>
                                            <p className="text-2xl font-bold text-gray-900">{stats.totalDocuments}</p>
                                            <p className="text-sm text-gray-500">Documents</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Address List */}
                            <div className="space-y-3">
                                {filteredAddresses.length === 0 ? (
                                    <div className="bg-white rounded-2xl p-8 text-center border border-gray-100 shadow-sm">
                                        <div className="w-16 h-16 mx-auto mb-4 bg-gradient-to-br from-indigo-100 to-violet-100 rounded-2xl flex items-center justify-center">
                                            <MapPin className="h-8 w-8 text-indigo-600" />
                                        </div>
                                        <h3 className="font-semibold text-lg mb-2">Aucune adresse trouvée</h3>
                                        <p className="text-gray-500 mb-4">
                                            {addressFilter
                                                ? 'Aucune adresse ne correspond à votre recherche'
                                                : 'Vous n\'avez pas encore enregistré d\'adresse'}
                                        </p>
                                        {!addressFilter && (
                                            <Button asChild className="bg-gradient-to-r from-indigo-500 to-violet-500 hover:from-indigo-600 hover:to-violet-600 text-white">
                                                <Link href="/collections/create">
                                                    <Plus className="h-4 w-4 mr-2" />
                                                    Créer une collection
                                                </Link>
                                            </Button>
                                        )}
                                    </div>
                                ) : (
                                    filteredAddresses.map((addr) => (
                                        <div
                                            key={addr.id}
                                            className="bg-white rounded-xl p-4 border border-gray-100 shadow-sm cursor-pointer hover:shadow-md hover:border-indigo-200 transition-all group"
                                            onClick={() => {
                                                setSelectedAddress(addr);
                                                setDrawerOpen(true);
                                            }}
                                        >
                                            <div className="flex items-start justify-between gap-4">
                                                <div className="flex-1 min-w-0">
                                                    <div className="flex items-center gap-2 mb-1">
                                                        <h3 className="font-mono font-semibold truncate text-gray-900 group-hover:text-indigo-600 transition-colors">
                                                            {addr.swAddress}
                                                        </h3>
                                                        <StatusBadge
                                                            variant={
                                                                addr.verificationStatus === 'approved'
                                                                    ? 'success'
                                                                    : addr.verificationStatus === 'rejected'
                                                                    ? 'error'
                                                                    : 'pending'
                                                            }
                                                            label={
                                                                addr.verificationStatus === 'approved'
                                                                    ? 'Vérifié'
                                                                    : addr.verificationStatus === 'rejected'
                                                                    ? 'Rejeté'
                                                                    : 'En attente'
                                                            }
                                                            size="sm"
                                                        />
                                                    </div>
                                                    <p className="text-sm text-gray-500 truncate">
                                                        {addr.displayName || addr.quarter}
                                                    </p>
                                                    {addr.street && (
                                                        <p className="text-sm text-gray-400 truncate">
                                                            {addr.street.displayName}
                                                        </p>
                                                    )}
                                                    <p className="text-xs text-gray-400 mt-2">
                                                        Créée le {formatDate(addr.createdAt)}
                                                    </p>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        className="text-gray-400 hover:text-indigo-600 hover:bg-indigo-50"
                                                        onClick={(e) => {
                                                            e.stopPropagation();
                                                            setViewMode('map');
                                                            setSelectedAddress(addr);
                                                        }}
                                                    >
                                                        <MapIcon className="h-4 w-4" />
                                                    </Button>
                                                    <ChevronRight className="h-5 w-5 text-gray-300 group-hover:text-indigo-400 transition-colors" />
                                                </div>
                                            </div>
                                        </div>
                                    ))
                                )}
                            </div>
                        </div>
                    </div>
                )}

                {/* Stats Panel - Top Left */}
                <FloatingPanel position="top-left" size="auto" className="hidden md:block">
                    <FloatingPanelContent>
                        <StatsCardGrid columns={2}>
                            <div className="p-3 rounded-xl bg-gradient-to-br from-indigo-50 to-violet-50 border border-indigo-100/50">
                                <div className="flex items-center gap-2 mb-1">
                                    <div className="p-1.5 bg-gradient-to-br from-indigo-500 to-violet-500 rounded-lg">
                                        <MapPin className="h-4 w-4 text-white" />
                                    </div>
                                    <span className="text-xs font-medium text-gray-500">Adresses</span>
                                </div>
                                <p className="text-2xl font-bold text-gray-900">{stats.totalAddresses}</p>
                                <p className="text-xs text-indigo-600">{stats.verifiedAddresses} vérifiée(s)</p>
                            </div>
                            <div className="p-3 rounded-xl bg-gradient-to-br from-violet-50 to-purple-50 border border-violet-100/50">
                                <div className="flex items-center gap-2 mb-1">
                                    <div className="p-1.5 bg-gradient-to-br from-violet-500 to-purple-500 rounded-lg">
                                        <FileText className="h-4 w-4 text-white" />
                                    </div>
                                    <span className="text-xs font-medium text-gray-500">Documents</span>
                                </div>
                                <p className="text-2xl font-bold text-gray-900">{stats.totalDocuments}</p>
                                <p className="text-xs text-violet-600">{stats.activeDocuments} actif(s)</p>
                            </div>
                            <div className="p-3 rounded-xl bg-gradient-to-br from-blue-50 to-indigo-50 border border-blue-100/50">
                                <div className="flex items-center gap-2 mb-1">
                                    <div className="p-1.5 bg-gradient-to-br from-blue-500 to-indigo-500 rounded-lg">
                                        <FolderOpen className="h-4 w-4 text-white" />
                                    </div>
                                    <span className="text-xs font-medium text-gray-500">Collections</span>
                                </div>
                                <p className="text-2xl font-bold text-gray-900">{stats.totalCollections}</p>
                            </div>
                            <div className={`p-3 rounded-xl ${stats.pendingDeliveries > 0 ? 'bg-gradient-to-br from-amber-50 to-orange-50 border border-amber-100/50' : 'bg-gradient-to-br from-gray-50 to-slate-50 border border-gray-100/50'}`}>
                                <div className="flex items-center gap-2 mb-1">
                                    <div className={`p-1.5 rounded-lg ${stats.pendingDeliveries > 0 ? 'bg-gradient-to-br from-amber-500 to-orange-500' : 'bg-gradient-to-br from-gray-400 to-slate-400'}`}>
                                        <Truck className="h-4 w-4 text-white" />
                                    </div>
                                    <span className="text-xs font-medium text-gray-500">Livraisons</span>
                                </div>
                                <p className="text-2xl font-bold text-gray-900">{stats.pendingDeliveries}</p>
                                <p className={`text-xs ${stats.pendingDeliveries > 0 ? 'text-amber-600' : 'text-gray-400'}`}>en attente</p>
                            </div>
                        </StatsCardGrid>
                    </FloatingPanelContent>

                    <FloatingPanelFooter>
                        <Button asChild variant="outline" size="sm" className="w-full border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600 hover:border-indigo-300">
                            <Link href="/collections">
                                <FolderOpen className="mr-2 h-4 w-4" />
                                Mes Collections
                            </Link>
                        </Button>
                        {hasCompany && (
                            <Button asChild size="sm" className="w-full bg-gradient-to-r from-indigo-500 to-violet-500 hover:from-indigo-600 hover:to-violet-600 text-white border-0">
                                <Link href="/company">
                                    <Building2 className="mr-2 h-4 w-4" />
                                    Entreprise
                                </Link>
                            </Button>
                        )}
                    </FloatingPanelFooter>
                </FloatingPanel>

                {/* Recent Documents Panel - Bottom Left (Desktop) */}
                <FloatingPanel position="bottom-left" size="md" className="hidden md:block max-h-64">
                    <FloatingPanelHeader
                        title="Documents récents"
                        icon={<FileText className="h-5 w-5 text-violet-500" />}
                    />
                    <FloatingPanelContent>
                        {documents.length === 0 ? (
                            <div className="text-center py-6">
                                <div className="w-12 h-12 mx-auto mb-3 bg-gradient-to-br from-violet-100 to-purple-100 rounded-xl flex items-center justify-center">
                                    <FileText className="h-6 w-6 text-violet-500" />
                                </div>
                                <p className="text-sm text-gray-500">Aucun document</p>
                            </div>
                        ) : (
                            <div className="space-y-2">
                                {documents.slice(0, 3).map((doc) => (
                                    <div
                                        key={doc.id}
                                        className="flex items-center justify-between p-3 rounded-xl hover:bg-gradient-to-r hover:from-violet-50 hover:to-purple-50 transition-all cursor-pointer group border border-transparent hover:border-violet-100"
                                        onClick={() => {
                                            if (doc.address) {
                                                const addr = addresses.find(a => a.id === doc.address?.id);
                                                if (addr) {
                                                    setSelectedAddress(addr);
                                                    setDrawerOpen(true);
                                                }
                                            }
                                        }}
                                    >
                                        <div className="flex-1 min-w-0">
                                            <p className="text-sm font-medium truncate text-gray-900 group-hover:text-violet-700 transition-colors">
                                                {doc.documentTypeLabel}
                                            </p>
                                            <p className="text-xs text-gray-500 truncate">
                                                {doc.address?.swAddress || doc.documentNumber}
                                            </p>
                                        </div>
                                        <DocumentStatusBadge
                                            status={doc.isExpired ? 'expired' : doc.isActive ? 'active' : 'pending'}
                                            size="sm"
                                        />
                                    </div>
                                ))}
                            </div>
                        )}
                    </FloatingPanelContent>
                </FloatingPanel>

                {/* Mobile Content */}
                <div className="md:hidden space-y-4">
                    {/* Mobile Stats */}
                    <div className="grid grid-cols-2 gap-3">
                        <div className="p-4 rounded-xl bg-gradient-to-br from-indigo-50 to-violet-50 border border-indigo-100/50">
                            <div className="flex items-center gap-2 mb-2">
                                <div className="p-1.5 bg-gradient-to-br from-indigo-500 to-violet-500 rounded-lg">
                                    <MapPin className="h-4 w-4 text-white" />
                                </div>
                                <span className="text-xs font-medium text-gray-500">Adresses</span>
                            </div>
                            <p className="text-2xl font-bold text-gray-900">{stats.totalAddresses}</p>
                        </div>
                        <div className="p-4 rounded-xl bg-gradient-to-br from-violet-50 to-purple-50 border border-violet-100/50">
                            <div className="flex items-center gap-2 mb-2">
                                <div className="p-1.5 bg-gradient-to-br from-violet-500 to-purple-500 rounded-lg">
                                    <FileText className="h-4 w-4 text-white" />
                                </div>
                                <span className="text-xs font-medium text-gray-500">Documents</span>
                            </div>
                            <p className="text-2xl font-bold text-gray-900">{stats.totalDocuments}</p>
                        </div>
                    </div>

                    {/* Mobile Quick Actions */}
                    <div className="flex gap-2">
                        <Button asChild variant="outline" size="sm" className="flex-1 border-indigo-200 hover:bg-indigo-50 hover:text-indigo-600">
                            <Link href="/collections">
                                <FolderOpen className="mr-2 h-4 w-4" />
                                Collections
                            </Link>
                        </Button>
                        <Button asChild variant="outline" size="sm" className="flex-1 border-violet-200 hover:bg-violet-50 hover:text-violet-600">
                            <Link href="/deliveries">
                                <Truck className="mr-2 h-4 w-4" />
                                Livraisons
                            </Link>
                        </Button>
                    </div>

                    {/* Mobile Addresses List */}
                    <div className="space-y-2">
                        <h3 className="font-semibold text-sm flex items-center gap-2">
                            <Sparkles className="h-4 w-4 text-violet-500" />
                            Mes adresses
                        </h3>
                        {addresses.length === 0 ? (
                            <div className="text-center py-6 bg-gradient-to-br from-gray-50 to-slate-50 rounded-xl border border-gray-100">
                                <p className="text-sm text-gray-500">Aucune adresse</p>
                            </div>
                        ) : (
                            addresses.slice(0, 5).map((addr) => (
                                <div
                                    key={addr.id}
                                    className="flex items-center justify-between p-4 rounded-xl border border-gray-100 hover:bg-gradient-to-r hover:from-indigo-50 hover:to-violet-50 hover:border-indigo-200 transition-all cursor-pointer group bg-white"
                                    onClick={() => {
                                        setSelectedAddress(addr);
                                        setDrawerOpen(true);
                                    }}
                                >
                                    <div className="flex-1 min-w-0">
                                        <p className="font-medium truncate text-gray-900 group-hover:text-indigo-600 transition-colors">{addr.swAddress}</p>
                                        <p className="text-sm text-gray-500 truncate">{addr.quarter}</p>
                                    </div>
                                    <ChevronRight className="h-5 w-5 text-gray-300 group-hover:text-indigo-400 flex-shrink-0 transition-colors" />
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </MapDashboardLayout>

            {/* Address Detail Drawer */}
            <SideDrawer
                open={drawerOpen}
                onOpenChange={setDrawerOpen}
                title={selectedAddress?.swAddress || 'Détails'}
                description={selectedAddress?.quarter}
            >
                {selectedAddress && (
                    <>
                        <SideDrawerContent>
                            {/* Address Info */}
                            <div className="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                                <div className="px-4 py-3 bg-gradient-to-r from-indigo-50 to-violet-50 border-b border-indigo-100/50">
                                    <div className="flex items-center justify-between">
                                        <h3 className="font-semibold text-gray-900">Informations</h3>
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
                                </div>
                                <div className="p-4 space-y-4">
                                    <div>
                                        <p className="text-sm text-gray-500 mb-1">Adresse SW</p>
                                        <p className="font-mono font-semibold text-indigo-600">{selectedAddress.swAddress}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-500 mb-1">Quartier</p>
                                        <p className="font-medium text-gray-900">
                                            {selectedAddress.quarter}
                                            {selectedAddress.subQuarter && ` - ${selectedAddress.subQuarter}`}
                                        </p>
                                    </div>
                                    {selectedAddress.street && (
                                        <div>
                                            <p className="text-sm text-gray-500 mb-1">Rue</p>
                                            <p className="font-medium text-gray-900">{selectedAddress.street.displayName}</p>
                                        </div>
                                    )}
                                    <div>
                                        <p className="text-sm text-gray-500 mb-1">Coordonnées</p>
                                        <p className="font-mono text-sm text-gray-700">
                                            {selectedAddress.latitude.toFixed(6)}, {selectedAddress.longitude.toFixed(6)}
                                        </p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-gray-500 mb-1">Créée le</p>
                                        <p className="font-medium text-gray-900">{formatDate(selectedAddress.createdAt)}</p>
                                    </div>
                                </div>
                            </div>
                        </SideDrawerContent>

                        {/* Documents for this address */}
                        <SideDrawerSection title="Documents">
                            <div className="px-4 space-y-2">
                                {selectedAddressDocuments.length === 0 ? (
                                    <div className="text-center py-6 bg-gradient-to-br from-gray-50 to-slate-50 rounded-xl border border-gray-100">
                                        <FileText className="h-8 w-8 mx-auto text-gray-300 mb-2" />
                                        <p className="text-sm text-gray-500">Aucun document pour cette adresse</p>
                                    </div>
                                ) : (
                                    selectedAddressDocuments.map((doc) => (
                                        <div
                                            key={doc.id}
                                            className="flex items-center justify-between p-4 rounded-xl border border-gray-100 bg-white hover:border-violet-200 hover:shadow-sm transition-all"
                                        >
                                            <div className="flex-1 min-w-0">
                                                <div className="flex items-center gap-2">
                                                    <p className="font-medium text-sm text-gray-900">{doc.documentTypeLabel}</p>
                                                    {doc.isActive && !doc.isExpired && (
                                                        <CheckCircle className="h-4 w-4 text-green-500" />
                                                    )}
                                                </div>
                                                <p className="text-xs text-gray-500">
                                                    Expire le {formatDate(doc.expiresAt)}
                                                </p>
                                            </div>
                                            {doc.isActive && !doc.isExpired && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="border-violet-200 text-violet-600 hover:bg-violet-50 hover:border-violet-300"
                                                    onClick={() => window.open(`/api/v1/proof-of-location/${doc.id}/download`, '_blank')}
                                                >
                                                    <Download className="h-4 w-4" />
                                                </Button>
                                            )}
                                        </div>
                                    ))
                                )}
                            </div>
                        </SideDrawerSection>
                    </>
                )}
            </SideDrawer>
        </>
    );
}
