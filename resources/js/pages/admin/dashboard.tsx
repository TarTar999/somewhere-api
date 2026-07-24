import { useState, useMemo, useCallback } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import {
    Users,
    MapPin,
    Building2,
    Clock,
    CheckCircle,
    XCircle,
    Search,
    Filter,
    Layers,
    Eye,
    TrendingUp,
    AlertTriangle,
    FileText,
    Activity,
    ChevronRight,
    X,
    RefreshCw,
    BarChart3,
    Globe,
    Shield,
} from 'lucide-react';
import MapDashboardLayout from '@/layouts/map-dashboard-layout';
import EnhancedMap from '@/components/map/enhanced-map';
import { FloatingPanel } from '@/components/ui/floating-panel';
import { StatsCard, StatsCardGrid } from '@/components/ui/stats-card';
import { SideDrawer } from '@/components/ui/side-drawer';
import { BottomSheet } from '@/components/ui/bottom-sheet';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { VerificationStatusBadge } from '@/components/ui/status-badge';
import { cn } from '@/lib/utils';

interface Stats {
    totalUsers: number;
    newUsersThisWeek: number;
    totalAddresses: number;
    pendingAddresses: number;
    approvedAddresses: number;
    rejectedAddresses: number;
    totalCollections: number;
    totalCompanies: number;
    activeCompanies: number;
    documentsGenerated: number;
    revenueThisMonth: number;
}

interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    created_at: string;
    addresses_count?: number;
}

interface Address {
    id: number;
    sw_address: string;
    display_name: string;
    quarter: string;
    city: string;
    verification_status: string;
    latitude: number;
    longitude: number;
    created_at: string;
    user: {
        id: number;
        first_name: string;
        last_name: string;
        email: string;
    };
    company?: {
        id: number;
        name: string;
    };
}

interface Company {
    id: number;
    name: string;
    subscription_plan: string;
    addresses_count: number;
    employees_count: number;
    created_at: string;
}

interface Props {
    stats: Stats;
    recentUsers: User[];
    pendingVerifications: Address[];
    allAddresses: Address[];
    recentCompanies: Company[];
    addressesByStatus: Record<string, number>;
    addressesByCity: Record<string, number>;
}

export default function AdminDashboard({
    stats,
    recentUsers = [],
    pendingVerifications = [],
    allAddresses = [],
    recentCompanies = [],
    addressesByStatus = {},
    addressesByCity = {},
}: Props) {
    // State
    const [selectedAddress, setSelectedAddress] = useState<Address | null>(null);
    const [showDrawer, setShowDrawer] = useState(false);
    const [activePanel, setActivePanel] = useState<'stats' | 'pending' | 'users' | 'companies'>('stats');
    const [showHeatmap, setShowHeatmap] = useState(true);
    const [showClusters, setShowClusters] = useState(true);
    const [filterStatus, setFilterStatus] = useState<string | null>(null);
    const [isRefreshing, setIsRefreshing] = useState(false);

    // Mobile bottom sheet
    const [mobileSheetOpen, setMobileSheetOpen] = useState(false);

    // Convert addresses for map
    const mapAddresses = useMemo(() => {
        let filtered = allAddresses;
        if (filterStatus) {
            filtered = allAddresses.filter(a => a.verification_status === filterStatus);
        }
        return filtered.map(addr => ({
            id: addr.id,
            latitude: addr.latitude,
            longitude: addr.longitude,
            sw_address: addr.sw_address,
            display_name: addr.display_name,
            quarter: addr.quarter,
            verification_status: addr.verification_status,
        }));
    }, [allAddresses, filterStatus]);

    // Heatmap points
    const heatmapPoints = useMemo(() => {
        return allAddresses.map(addr => ({
            lat: addr.latitude,
            lng: addr.longitude,
            intensity: addr.verification_status === 'approved' ? 1 : 0.5,
        }));
    }, [allAddresses]);

    // Handle address selection
    const handleAddressClick = useCallback((address: typeof mapAddresses[0]) => {
        const fullAddress = allAddresses.find(a => a.id === address.id);
        if (fullAddress) {
            setSelectedAddress(fullAddress);
            setShowDrawer(true);
        }
    }, [allAddresses]);

    // Refresh data
    const handleRefresh = useCallback(() => {
        setIsRefreshing(true);
        router.reload({
            only: ['stats', 'pendingVerifications', 'allAddresses', 'recentUsers', 'recentCompanies'],
            onFinish: () => setIsRefreshing(false),
        });
    }, []);

    // Approve/Reject address
    const handleVerificationAction = useCallback((addressId: number, action: 'approve' | 'reject') => {
        router.post(`/admin/addresses/${addressId}/${action}`, {}, {
            preserveScroll: true,
            onSuccess: () => {
                setShowDrawer(false);
                setSelectedAddress(null);
            },
        });
    }, []);

    // Status filter options
    const statusFilters = [
        { value: null, label: 'Toutes', count: allAddresses.length },
        { value: 'pending', label: 'En attente', count: addressesByStatus.pending || 0 },
        { value: 'approved', label: 'Vérifiées', count: addressesByStatus.approved || 0 },
        { value: 'rejected', label: 'Rejetées', count: addressesByStatus.rejected || 0 },
    ];

    // Map center (Cameroon default)
    const mapCenter: [number, number] = useMemo(() => {
        if (allAddresses.length > 0) {
            const avgLat = allAddresses.reduce((sum, a) => sum + a.latitude, 0) / allAddresses.length;
            const avgLng = allAddresses.reduce((sum, a) => sum + a.longitude, 0) / allAddresses.length;
            return [avgLat, avgLng];
        }
        return [5.95, 10.15]; // Cameroon center
    }, [allAddresses]);

    return (
        <MapDashboardLayout
            dashboardType="admin"
            showSearch={true}
            onSearch={(query) => console.log('Search:', query)}
        >
            <Head title="Admin Dashboard" />

            {/* Main Map */}
            <EnhancedMap
                addresses={mapAddresses}
                zones={[]}
                showHeatmap={showHeatmap}
                showClusters={showClusters}
                heatmapData={heatmapPoints}
                center={mapCenter}
                zoom={7}
                onAddressClick={handleAddressClick}
                className="h-full w-full"
            />

            {/* Stats Panel - Top Left */}
            <FloatingPanel
                position="top-left"
                className="hidden md:block max-w-xs"
            >
                <div className="flex items-center justify-between mb-4">
                    <h3 className="font-semibold text-sm">Vue d'ensemble</h3>
                    <Button
                        variant="ghost"
                        size="sm"
                        onClick={handleRefresh}
                        disabled={isRefreshing}
                    >
                        <RefreshCw className={cn("h-4 w-4", isRefreshing && "animate-spin")} />
                    </Button>
                </div>

                <StatsCardGrid columns={2} gap="sm">
                    <StatsCard
                        title="Utilisateurs"
                        value={stats.totalUsers}
                        icon={Users}
                        trend={{ value: stats.newUsersThisWeek, label: 'cette semaine', isPositive: true }}
                        variant="default"
                        size="sm"
                    />
                    <StatsCard
                        title="Adresses"
                        value={stats.totalAddresses}
                        icon={MapPin}
                        trend={{ value: stats.approvedAddresses, label: 'vérifiées' }}
                        variant="default"
                        size="sm"
                    />
                    <StatsCard
                        title="Entreprises"
                        value={stats.totalCompanies || 0}
                        icon={Building2}
                        trend={{ value: stats.activeCompanies || 0, label: 'actives' }}
                        variant="default"
                        size="sm"
                    />
                    <StatsCard
                        title="En attente"
                        value={stats.pendingAddresses}
                        icon={Clock}
                        variant={stats.pendingAddresses > 10 ? 'warning' : 'default'}
                        size="sm"
                    />
                </StatsCardGrid>

                {/* Revenue if available */}
                {stats.revenueThisMonth !== undefined && (
                    <div className="mt-4 p-3 bg-gradient-to-r from-sw-black to-sw-charcoal rounded-lg text-white">
                        <div className="flex items-center gap-2 mb-1">
                            <TrendingUp className="h-4 w-4" />
                            <span className="text-xs opacity-80">Revenus ce mois</span>
                        </div>
                        <p className="text-xl font-bold">
                            {new Intl.NumberFormat('fr-FR').format(stats.revenueThisMonth)} FCFA
                        </p>
                    </div>
                )}
            </FloatingPanel>

            {/* Pending Verifications - Top Right */}
            <FloatingPanel
                position="top-right"
                className="hidden lg:block max-w-sm max-h-[60vh] overflow-hidden"
            >
                <div className="flex items-center justify-between mb-4">
                    <div className="flex items-center gap-2">
                        <AlertTriangle className="h-4 w-4 text-sw-warning" />
                        <h3 className="font-semibold text-sm">Vérifications en attente</h3>
                    </div>
                    <Badge variant="secondary">{pendingVerifications.length}</Badge>
                </div>

                <div className="space-y-2 max-h-[calc(60vh-80px)] overflow-y-auto">
                    {pendingVerifications.slice(0, 10).map((address) => (
                        <motion.div
                            key={address.id}
                            initial={{ opacity: 0, x: 20 }}
                            animate={{ opacity: 1, x: 0 }}
                            className="p-3 bg-sw-slate-100/50 rounded-lg hover:bg-sw-slate-100 cursor-pointer transition-colors"
                            onClick={() => {
                                setSelectedAddress(address);
                                setShowDrawer(true);
                            }}
                        >
                            <div className="flex items-start justify-between gap-2">
                                <div className="min-w-0 flex-1">
                                    <p className="font-mono text-sm font-medium truncate">
                                        {address.sw_address}
                                    </p>
                                    <p className="text-xs text-muted-foreground truncate">
                                        {address.quarter}, {address.city}
                                    </p>
                                    <p className="text-xs text-muted-foreground mt-1">
                                        {address.user?.first_name} {address.user?.last_name}
                                    </p>
                                </div>
                                <ChevronRight className="h-4 w-4 text-muted-foreground flex-shrink-0" />
                            </div>
                        </motion.div>
                    ))}

                    {pendingVerifications.length === 0 && (
                        <div className="text-center py-8 text-muted-foreground">
                            <CheckCircle className="h-8 w-8 mx-auto mb-2 text-sw-success" />
                            <p className="text-sm">Aucune vérification en attente</p>
                        </div>
                    )}

                    {pendingVerifications.length > 10 && (
                        <Link
                            href="/admin/addresses?status=pending"
                            className="block text-center py-2 text-sm text-primary hover:underline"
                        >
                            Voir les {pendingVerifications.length - 10} autres →
                        </Link>
                    )}
                </div>
            </FloatingPanel>

            {/* Map Controls - Bottom Left */}
            <FloatingPanel
                position="bottom-left"
                className="hidden md:block"
            >
                <div className="space-y-3">
                    {/* Layer toggles */}
                    <div className="flex items-center gap-2">
                        <Layers className="h-4 w-4 text-muted-foreground" />
                        <span className="text-sm font-medium">Couches</span>
                    </div>
                    <div className="flex flex-wrap gap-2">
                        <Button
                            variant={showHeatmap ? "default" : "outline"}
                            size="sm"
                            onClick={() => setShowHeatmap(!showHeatmap)}
                        >
                            Heatmap
                        </Button>
                        <Button
                            variant={showClusters ? "default" : "outline"}
                            size="sm"
                            onClick={() => setShowClusters(!showClusters)}
                        >
                            Clusters
                        </Button>
                    </div>

                    {/* Status filters */}
                    <div className="pt-2 border-t">
                        <div className="flex items-center gap-2 mb-2">
                            <Filter className="h-4 w-4 text-muted-foreground" />
                            <span className="text-sm font-medium">Filtrer par statut</span>
                        </div>
                        <div className="flex flex-wrap gap-1">
                            {statusFilters.map(filter => (
                                <Button
                                    key={filter.value || 'all'}
                                    variant={filterStatus === filter.value ? "default" : "ghost"}
                                    size="sm"
                                    onClick={() => setFilterStatus(filter.value)}
                                    className="text-xs"
                                >
                                    {filter.label} ({filter.count})
                                </Button>
                            ))}
                        </div>
                    </div>
                </div>
            </FloatingPanel>

            {/* Quick Actions - Bottom Right */}
            <FloatingPanel
                position="bottom-right"
                className="hidden md:block"
            >
                <div className="space-y-2">
                    <h3 className="text-sm font-medium mb-3">Actions rapides</h3>
                    <div className="grid grid-cols-2 gap-2">
                        <Link href="/admin/users">
                            <Button variant="outline" size="sm" className="w-full justify-start">
                                <Users className="h-4 w-4 mr-2" />
                                Utilisateurs
                            </Button>
                        </Link>
                        <Link href="/admin/companies">
                            <Button variant="outline" size="sm" className="w-full justify-start">
                                <Building2 className="h-4 w-4 mr-2" />
                                Entreprises
                            </Button>
                        </Link>
                        <Link href="/admin/addresses">
                            <Button variant="outline" size="sm" className="w-full justify-start">
                                <MapPin className="h-4 w-4 mr-2" />
                                Adresses
                            </Button>
                        </Link>
                        <Link href="/admin/documents">
                            <Button variant="outline" size="sm" className="w-full justify-start">
                                <FileText className="h-4 w-4 mr-2" />
                                Documents
                            </Button>
                        </Link>
                        <Link href="/admin/analytics">
                            <Button variant="outline" size="sm" className="w-full justify-start">
                                <BarChart3 className="h-4 w-4 mr-2" />
                                Analytics
                            </Button>
                        </Link>
                        <Link href="/admin/settings">
                            <Button variant="outline" size="sm" className="w-full justify-start">
                                <Shield className="h-4 w-4 mr-2" />
                                Paramètres
                            </Button>
                        </Link>
                        <Link href="/admin/eneo">
                            <Button variant="outline" size="sm" className="w-full justify-start text-yellow-600 border-yellow-300 hover:bg-yellow-50">
                                <Zap className="h-4 w-4 mr-2" />
                                ENEO
                            </Button>
                        </Link>
                    </div>
                </div>
            </FloatingPanel>

            {/* Address Detail Drawer */}
            <SideDrawer
                isOpen={showDrawer}
                onClose={() => {
                    setShowDrawer(false);
                    setSelectedAddress(null);
                }}
                title="Détails de l'adresse"
                position="right"
            >
                {selectedAddress && (
                    <div className="space-y-6">
                        {/* Address Info */}
                        <div>
                            <div className="flex items-center gap-2 mb-2">
                                <VerificationStatusBadge status={selectedAddress.verification_status as any} />
                            </div>
                            <h3 className="font-mono text-lg font-bold">
                                {selectedAddress.sw_address}
                            </h3>
                            <p className="text-muted-foreground">
                                {selectedAddress.display_name}
                            </p>
                        </div>

                        {/* Location Details */}
                        <div className="space-y-3">
                            <h4 className="font-medium text-sm text-muted-foreground uppercase tracking-wider">
                                Localisation
                            </h4>
                            <div className="grid grid-cols-2 gap-3">
                                <div className="p-3 bg-sw-slate-100/50 rounded-lg">
                                    <p className="text-xs text-muted-foreground">Quartier</p>
                                    <p className="font-medium">{selectedAddress.quarter}</p>
                                </div>
                                <div className="p-3 bg-sw-slate-100/50 rounded-lg">
                                    <p className="text-xs text-muted-foreground">Ville</p>
                                    <p className="font-medium">{selectedAddress.city}</p>
                                </div>
                                <div className="p-3 bg-sw-slate-100/50 rounded-lg">
                                    <p className="text-xs text-muted-foreground">Latitude</p>
                                    <p className="font-mono text-sm">{selectedAddress.latitude.toFixed(6)}</p>
                                </div>
                                <div className="p-3 bg-sw-slate-100/50 rounded-lg">
                                    <p className="text-xs text-muted-foreground">Longitude</p>
                                    <p className="font-mono text-sm">{selectedAddress.longitude.toFixed(6)}</p>
                                </div>
                            </div>
                        </div>

                        {/* User Info */}
                        <div className="space-y-3">
                            <h4 className="font-medium text-sm text-muted-foreground uppercase tracking-wider">
                                Propriétaire
                            </h4>
                            <div className="p-4 bg-sw-slate-100/50 rounded-lg">
                                <p className="font-medium">
                                    {selectedAddress.user?.first_name} {selectedAddress.user?.last_name}
                                </p>
                                <p className="text-sm text-muted-foreground">
                                    {selectedAddress.user?.email}
                                </p>
                                <Link
                                    href={`/admin/users/${selectedAddress.user?.id}`}
                                    className="text-sm text-primary hover:underline mt-2 inline-block"
                                >
                                    Voir le profil →
                                </Link>
                            </div>
                        </div>

                        {/* Company if exists */}
                        {selectedAddress.company && (
                            <div className="space-y-3">
                                <h4 className="font-medium text-sm text-muted-foreground uppercase tracking-wider">
                                    Entreprise
                                </h4>
                                <div className="p-4 bg-sw-slate-100/50 rounded-lg">
                                    <div className="flex items-center gap-2">
                                        <Building2 className="h-4 w-4 text-muted-foreground" />
                                        <p className="font-medium">{selectedAddress.company.name}</p>
                                    </div>
                                    <Link
                                        href={`/admin/companies/${selectedAddress.company.id}`}
                                        className="text-sm text-primary hover:underline mt-2 inline-block"
                                    >
                                        Voir l'entreprise →
                                    </Link>
                                </div>
                            </div>
                        )}

                        {/* Actions */}
                        {selectedAddress.verification_status === 'pending' && (
                            <div className="space-y-3 pt-4 border-t">
                                <h4 className="font-medium text-sm text-muted-foreground uppercase tracking-wider">
                                    Actions de vérification
                                </h4>
                                <div className="flex gap-3">
                                    <Button
                                        onClick={() => handleVerificationAction(selectedAddress.id, 'approve')}
                                        className="flex-1 bg-sw-success hover:bg-sw-success/90"
                                    >
                                        <CheckCircle className="h-4 w-4 mr-2" />
                                        Approuver
                                    </Button>
                                    <Button
                                        variant="destructive"
                                        onClick={() => handleVerificationAction(selectedAddress.id, 'reject')}
                                        className="flex-1"
                                    >
                                        <XCircle className="h-4 w-4 mr-2" />
                                        Rejeter
                                    </Button>
                                </div>
                            </div>
                        )}

                        {/* View Full Details */}
                        <div className="pt-4 border-t">
                            <Link href={`/admin/addresses/${selectedAddress.id}`}>
                                <Button variant="outline" className="w-full">
                                    <Eye className="h-4 w-4 mr-2" />
                                    Voir tous les détails
                                </Button>
                            </Link>
                        </div>
                    </div>
                )}
            </SideDrawer>

            {/* Mobile Bottom Sheet */}
            <BottomSheet
                isOpen={mobileSheetOpen}
                onClose={() => setMobileSheetOpen(false)}
                snapPoints={[0.3, 0.6, 0.9]}
                className="md:hidden"
            >
                <div className="p-4 space-y-4">
                    {/* Mobile Stats */}
                    <div className="grid grid-cols-2 gap-3">
                        <div className="p-3 bg-sw-slate-100/50 rounded-lg">
                            <div className="flex items-center gap-2 mb-1">
                                <Users className="h-4 w-4 text-muted-foreground" />
                                <span className="text-xs text-muted-foreground">Utilisateurs</span>
                            </div>
                            <p className="text-xl font-bold">{stats.totalUsers}</p>
                        </div>
                        <div className="p-3 bg-sw-slate-100/50 rounded-lg">
                            <div className="flex items-center gap-2 mb-1">
                                <MapPin className="h-4 w-4 text-muted-foreground" />
                                <span className="text-xs text-muted-foreground">Adresses</span>
                            </div>
                            <p className="text-xl font-bold">{stats.totalAddresses}</p>
                        </div>
                        <div className="p-3 bg-sw-slate-100/50 rounded-lg">
                            <div className="flex items-center gap-2 mb-1">
                                <Clock className="h-4 w-4 text-sw-warning" />
                                <span className="text-xs text-muted-foreground">En attente</span>
                            </div>
                            <p className="text-xl font-bold">{stats.pendingAddresses}</p>
                        </div>
                        <div className="p-3 bg-sw-slate-100/50 rounded-lg">
                            <div className="flex items-center gap-2 mb-1">
                                <Building2 className="h-4 w-4 text-muted-foreground" />
                                <span className="text-xs text-muted-foreground">Entreprises</span>
                            </div>
                            <p className="text-xl font-bold">{stats.totalCompanies || 0}</p>
                        </div>
                    </div>

                    {/* Pending list for mobile */}
                    {pendingVerifications.length > 0 && (
                        <div>
                            <h3 className="font-medium mb-2 flex items-center gap-2">
                                <AlertTriangle className="h-4 w-4 text-sw-warning" />
                                Vérifications en attente
                            </h3>
                            <div className="space-y-2 max-h-48 overflow-y-auto">
                                {pendingVerifications.slice(0, 5).map((address) => (
                                    <div
                                        key={address.id}
                                        className="p-3 bg-white rounded-lg border cursor-pointer"
                                        onClick={() => {
                                            setSelectedAddress(address);
                                            setShowDrawer(true);
                                            setMobileSheetOpen(false);
                                        }}
                                    >
                                        <p className="font-mono text-sm font-medium">{address.sw_address}</p>
                                        <p className="text-xs text-muted-foreground">{address.quarter}</p>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </BottomSheet>

            {/* Mobile FAB to open sheet */}
            <button
                onClick={() => setMobileSheetOpen(true)}
                className="md:hidden fixed bottom-6 right-6 w-14 h-14 bg-sw-black text-white rounded-full shadow-lg flex items-center justify-center z-40"
            >
                <Activity className="h-6 w-6" />
            </button>
        </MapDashboardLayout>
    );
}
