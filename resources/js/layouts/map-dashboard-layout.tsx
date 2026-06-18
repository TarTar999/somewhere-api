import * as React from 'react';
import { cn } from '@/lib/utils';
import { Link, usePage } from '@inertiajs/react';
import { motion, AnimatePresence } from 'framer-motion';
import {
    Bell,
    User,
    Menu,
    X,
    Home,
    MapPin,
    Folder,
    Truck,
    Settings,
    Building2,
    Users,
    FileText,
    CreditCard,
    Map,
    LogOut,
    ChevronDown,
} from 'lucide-react';
import { MapSearchBar, type SearchResult } from '@/components/ui/map-search-bar';
import { BottomSheet } from '@/components/ui/bottom-sheet';
import { PinSetupModal } from '@/components/auth/pin-setup-modal';

interface User {
    id: number;
    name: string;
    email: string;
    avatar_path?: string;
}

interface SharedData {
    auth: {
        user: User;
        needs_pin_setup?: boolean;
    };
    notifications_count?: number;
}

interface MapDashboardLayoutProps {
    children: React.ReactNode;
    mapComponent?: React.ReactNode;
    showSearch?: boolean;
    searchValue?: string;
    onSearchChange?: (value: string) => void;
    searchResults?: SearchResult[];
    onSearchResultSelect?: (result: SearchResult) => void;
    isSearching?: boolean;
    sidePanel?: React.ReactNode;
    sidePanelOpen?: boolean;
    onSidePanelClose?: () => void;
    sidePanelTitle?: string;
    floatingActions?: React.ReactNode;
    headerExtra?: React.ReactNode;
    type?: 'user' | 'company' | 'admin';
}

// Navigation items for different dashboard types
const navigationItems = {
    user: [
        { href: '/dashboard', label: 'Tableau de bord', icon: Home },
        { href: '/collections', label: 'Collections', icon: Folder },
        { href: '/deliveries', label: 'Livraisons', icon: Truck },
        { href: '/settings/profile', label: 'Paramètres', icon: Settings },
    ],
    company: [
        { href: '/company', label: 'Tableau de bord', icon: Home },
        { href: '/company/addresses', label: 'Adresses', icon: MapPin },
        { href: '/company/zones', label: 'Zones', icon: Map },
        { href: '/company/members', label: 'Membres', icon: Users },
        { href: '/company/labels', label: 'Étiquettes', icon: FileText },
        { href: '/company/subscription', label: 'Abonnement', icon: CreditCard },
        { href: '/company/settings', label: 'Paramètres', icon: Settings },
    ],
    admin: [
        { href: '/admin/dashboard', label: 'Tableau de bord', icon: Home },
        { href: '/admin/users', label: 'Utilisateurs', icon: Users },
        { href: '/admin/addresses', label: 'Adresses', icon: MapPin },
        { href: '/admin/collections', label: 'Collections', icon: Folder },
        { href: '/admin/companies', label: 'Entreprises', icon: Building2 },
    ],
};

export function MapDashboardLayout({
    children,
    mapComponent,
    showSearch = true,
    searchValue,
    onSearchChange,
    searchResults = [],
    onSearchResultSelect,
    isSearching = false,
    sidePanel,
    sidePanelOpen = false,
    onSidePanelClose,
    sidePanelTitle,
    floatingActions,
    headerExtra,
    type = 'user',
}: MapDashboardLayoutProps) {
    const { auth, notifications_count = 0 } = usePage<SharedData>().props;
    const [mobileMenuOpen, setMobileMenuOpen] = React.useState(false);
    const [mobileSheetOpen, setMobileSheetOpen] = React.useState(true);
    const [userMenuOpen, setUserMenuOpen] = React.useState(false);
    const [showPinSetup, setShowPinSetup] = React.useState(auth.needs_pin_setup ?? false);

    const navItems = navigationItems[type];

    // Detect if mobile
    const [isMobile, setIsMobile] = React.useState(false);
    React.useEffect(() => {
        const checkMobile = () => setIsMobile(window.innerWidth < 768);
        checkMobile();
        window.addEventListener('resize', checkMobile);
        return () => window.removeEventListener('resize', checkMobile);
    }, []);

    return (
        <div className="h-screen w-screen overflow-hidden relative bg-background">
            {/* Map Layer (Background) */}
            <div className="absolute inset-0 z-0">
                {mapComponent || (
                    <div className="w-full h-full bg-muted flex items-center justify-center">
                        <p className="text-muted-foreground">Carte non disponible</p>
                    </div>
                )}
            </div>

            {/* Header Bar - Prospekt Style */}
            <header className="fixed top-0 left-0 right-0 z-50 bg-white/95 backdrop-blur-xl border-b border-gray-100 shadow-sm px-4 py-3 flex items-center gap-4">
                {/* Logo */}
                <Link
                    href={type === 'admin' ? '/admin/dashboard' : type === 'company' ? '/company' : '/dashboard'}
                    className="flex-shrink-0 flex items-center gap-2.5 group"
                >
                    <img
                        src="/images/icon.png"
                        alt="SomeWhere"
                        className="h-9 w-9"
                        onError={(e) => {
                            e.currentTarget.style.display = 'none';
                        }}
                    />
                    <span className="font-display font-bold text-lg text-gray-900 hidden sm:inline">SomeWhere App</span>
                </Link>

                {/* Search Bar */}
                {showSearch && (
                    <div className="flex-1 max-w-xl hidden md:block">
                        <MapSearchBar
                            value={searchValue}
                            onChange={onSearchChange}
                            results={searchResults}
                            onResultSelect={onSearchResultSelect}
                            isLoading={isSearching}
                        />
                    </div>
                )}

                {/* Mobile Menu Button */}
                <button
                    onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
                    className="md:hidden p-2.5 rounded-xl hover:bg-gray-100 transition-all"
                >
                    {mobileMenuOpen ? (
                        <X className="h-5 w-5 text-gray-600" />
                    ) : (
                        <Menu className="h-5 w-5 text-gray-600" />
                    )}
                </button>

                {/* Desktop Navigation */}
                <nav className="hidden md:flex items-center gap-1 ml-auto">
                    {navItems.slice(0, 4).map((item) => (
                        <Link
                            key={item.href}
                            href={item.href}
                            className={cn(
                                'px-4 py-2 text-sm font-medium rounded-xl transition-all',
                                'text-gray-600 hover:text-gray-900 hover:bg-gray-100/80'
                            )}
                        >
                            {item.label}
                        </Link>
                    ))}
                </nav>

                {headerExtra}

                {/* Notifications */}
                <button
                    type="button"
                    className="relative p-2.5 rounded-xl hover:bg-gray-100 transition-all"
                    onClick={() => {
                        // TODO: Implémenter le panneau de notifications
                    }}
                >
                    <Bell className="h-5 w-5 text-gray-500" />
                    {notifications_count > 0 && (
                        <span className="absolute top-1 right-1 flex h-4 w-4 items-center justify-center rounded-full bg-gradient-to-r from-indigo-500 to-violet-500 text-[10px] font-semibold text-white shadow-sm">
                            {notifications_count > 9 ? '9+' : notifications_count}
                        </span>
                    )}
                </button>

                {/* User Menu */}
                <div className="relative">
                    <button
                        onClick={() => setUserMenuOpen(!userMenuOpen)}
                        className="flex items-center gap-2 p-1.5 rounded-xl hover:bg-gray-100 transition-all"
                    >
                        {auth.user.avatar_path ? (
                            <img
                                src={auth.user.avatar_path}
                                alt={auth.user.name}
                                className="h-9 w-9 rounded-xl object-cover ring-2 ring-gray-100"
                            />
                        ) : (
                            <div className="h-9 w-9 rounded-xl bg-gradient-to-br from-indigo-500 to-violet-500 flex items-center justify-center shadow-sm">
                                <span className="text-sm font-semibold text-white">
                                    {auth.user.name.charAt(0).toUpperCase()}
                                </span>
                            </div>
                        )}
                        <ChevronDown className="h-4 w-4 text-gray-400 hidden md:block" />
                    </button>

                    {/* User Dropdown */}
                    <AnimatePresence>
                        {userMenuOpen && (
                            <motion.div
                                initial={{ opacity: 0, y: -10, scale: 0.95 }}
                                animate={{ opacity: 1, y: 0, scale: 1 }}
                                exit={{ opacity: 0, y: -10, scale: 0.95 }}
                                transition={{ duration: 0.15 }}
                                className="absolute right-0 mt-2 w-64 bg-white rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-100 py-2 z-50"
                            >
                                <div className="px-4 py-3 border-b border-gray-100">
                                    <p className="font-semibold text-gray-900 truncate">
                                        {auth.user.name}
                                    </p>
                                    <p className="text-sm text-gray-500 truncate">
                                        {auth.user.email}
                                    </p>
                                </div>
                                <div className="py-2">
                                    <Link
                                        href="/settings/profile"
                                        className="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors"
                                    >
                                        <User className="h-4 w-4 text-gray-400" />
                                        Profil
                                    </Link>
                                    <Link
                                        href="/settings"
                                        className="flex items-center gap-3 px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-gray-900 transition-colors"
                                    >
                                        <Settings className="h-4 w-4 text-gray-400" />
                                        Paramètres
                                    </Link>
                                </div>
                                <div className="border-t border-gray-100 pt-2">
                                    <Link
                                        href="/logout"
                                        method="post"
                                        as="button"
                                        className="flex items-center gap-3 w-full px-4 py-2.5 text-sm text-red-600 hover:bg-red-50 transition-colors"
                                    >
                                        <LogOut className="h-4 w-4" />
                                        Se déconnecter
                                    </Link>
                                </div>
                            </motion.div>
                        )}
                    </AnimatePresence>
                </div>
            </header>

            {/* Mobile Navigation Menu */}
            <AnimatePresence>
                {mobileMenuOpen && (
                    <motion.div
                        initial={{ opacity: 0, y: -20 }}
                        animate={{ opacity: 1, y: 0 }}
                        exit={{ opacity: 0, y: -20 }}
                        className="md:hidden fixed top-[60px] left-4 right-4 z-40 bg-white rounded-2xl shadow-xl shadow-gray-200/50 border border-gray-100 overflow-hidden"
                    >
                        {/* Mobile Search */}
                        {showSearch && (
                            <div className="p-4 border-b border-gray-100 bg-gray-50/50">
                                <MapSearchBar
                                    value={searchValue}
                                    onChange={onSearchChange}
                                    results={searchResults}
                                    onResultSelect={onSearchResultSelect}
                                    isLoading={isSearching}
                                />
                            </div>
                        )}
                        <nav className="p-2">
                            {navItems.map((item) => (
                                <Link
                                    key={item.href}
                                    href={item.href}
                                    onClick={() => setMobileMenuOpen(false)}
                                    className="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-gray-50 transition-colors group"
                                >
                                    <div className="p-2 rounded-lg bg-gray-100 group-hover:bg-gradient-to-br group-hover:from-indigo-500 group-hover:to-violet-500 transition-all">
                                        <item.icon className="h-4 w-4 text-gray-600 group-hover:text-white transition-colors" />
                                    </div>
                                    <span className="font-medium text-gray-700 group-hover:text-gray-900">{item.label}</span>
                                </Link>
                            ))}
                        </nav>
                    </motion.div>
                )}
            </AnimatePresence>

            {/* Desktop: Floating Content Panels */}
            <div className="hidden md:block">
                {children}
            </div>

            {/* Desktop: Side Panel */}
            <AnimatePresence>
                {sidePanelOpen && !isMobile && (
                    <motion.aside
                        initial={{ x: '100%' }}
                        animate={{ x: 0 }}
                        exit={{ x: '100%' }}
                        transition={{ type: 'spring', damping: 30, stiffness: 300 }}
                        className="fixed top-16 right-0 bottom-0 w-full max-w-md z-30 glass-panel border-l overflow-hidden"
                    >
                        <div className="flex flex-col h-full">
                            {/* Panel Header */}
                            <div className="flex items-center justify-between px-4 py-4 border-b border-border">
                                <h2 className="font-semibold text-lg">{sidePanelTitle}</h2>
                                <button
                                    onClick={onSidePanelClose}
                                    className="p-1.5 rounded-full hover:bg-muted transition-colors"
                                >
                                    <X className="h-5 w-5 text-muted-foreground" />
                                </button>
                            </div>
                            {/* Panel Content */}
                            <div className="flex-1 overflow-y-auto p-4">
                                {sidePanel}
                            </div>
                        </div>
                    </motion.aside>
                )}
            </AnimatePresence>

            {/* Mobile: Bottom Sheet for Content */}
            {isMobile && (
                <BottomSheet
                    open={mobileSheetOpen}
                    onOpenChange={setMobileSheetOpen}
                    snapPoints={[0.15, 0.5, 0.9]}
                    defaultSnapPoint={1}
                    showHandle
                    closable={false}
                >
                    {children}
                    {sidePanel}
                </BottomSheet>
            )}

            {/* Floating Actions (Bottom Right) */}
            {floatingActions && (
                <div className="fixed bottom-4 right-4 z-30 flex flex-col gap-2 md:bottom-6 md:right-6">
                    {floatingActions}
                </div>
            )}

            {/* Click outside to close menus */}
            {(userMenuOpen || mobileMenuOpen) && (
                <div
                    className="fixed inset-0 z-30"
                    onClick={() => {
                        setUserMenuOpen(false);
                        setMobileMenuOpen(false);
                    }}
                />
            )}

            {/* PIN Setup Modal */}
            <PinSetupModal
                open={showPinSetup}
                onOpenChange={setShowPinSetup}
            />
        </div>
    );
}

export default MapDashboardLayout;
