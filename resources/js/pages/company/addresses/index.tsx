import { Link, router, usePage } from '@inertiajs/react';
import CompanyLayout from '@/layouts/company-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { MapPin, Search, CheckCircle, Clock, XCircle, ChevronLeft, ChevronRight } from 'lucide-react';
import type { CompanyRole, CompanyAddress } from '@/types/company';
import { useState } from 'react';

interface PaginatedAddresses {
    data: CompanyAddress[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Props {
    addresses: PaginatedAddresses;
    filters: {
        search?: string;
    };
}

export default function AddressesIndex({ addresses, filters }: Props) {
    const { props } = usePage();
    const company = (props as { company?: { name: string; logo?: string } }).company || { name: 'Entreprise' };
    const userRole = ((props as { userRole?: CompanyRole }).userRole || 'member') as CompanyRole;

    const [search, setSearch] = useState(filters.search || '');

    const handleSearch = () => {
        router.get('/company/addresses', { search }, { preserveState: true });
    };

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
                        Rejetée
                    </Badge>
                );
        }
    };

    return (
        <CompanyLayout title="Adresses" company={company} userRole={userRole}>
            <div className="space-y-6">
                {/* Search */}
                <div className="flex gap-4">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            type="text"
                            placeholder="Rechercher une adresse..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                            className="pl-10"
                        />
                    </div>
                    <Button onClick={handleSearch}>Rechercher</Button>
                </div>

                {/* Addresses List */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <MapPin className="h-5 w-5" />
                            Adresses de l'équipe
                        </CardTitle>
                        <CardDescription>
                            {addresses.total} adresse(s) trouvée(s)
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {addresses.data.length > 0 ? (
                            <div className="space-y-4">
                                {addresses.data.map((address) => (
                                    <Link key={address.id} href={`/company/addresses/${address.id}`}>
                                        <div className="flex items-center justify-between rounded-lg border p-4 transition-colors hover:bg-muted">
                                            <div className="flex items-center gap-4">
                                                <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10">
                                                    <MapPin className="h-6 w-6 text-primary" />
                                                </div>
                                                <div>
                                                    <p className="font-medium">{address.swAddress}</p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {[address.quarter, address.commune].filter(Boolean).join(', ')}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        Créée par {address.owner.name} - {address.createdAt}
                                                    </p>
                                                </div>
                                            </div>
                                            {getStatusBadge(address.verificationStatus)}
                                        </div>
                                    </Link>
                                ))}
                            </div>
                        ) : (
                            <div className="py-8 text-center text-muted-foreground">
                                <MapPin className="mx-auto mb-4 h-12 w-12 opacity-50" />
                                <p>Aucune adresse trouvée</p>
                            </div>
                        )}

                        {/* Pagination */}
                        {addresses.last_page > 1 && (
                            <div className="mt-6 flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Page {addresses.current_page} sur {addresses.last_page}
                                </p>
                                <div className="flex gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={addresses.current_page === 1}
                                        onClick={() =>
                                            router.get('/company/addresses', { page: addresses.current_page - 1, search })
                                        }
                                    >
                                        <ChevronLeft className="h-4 w-4" />
                                        Précédent
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        disabled={addresses.current_page === addresses.last_page}
                                        onClick={() =>
                                            router.get('/company/addresses', { page: addresses.current_page + 1, search })
                                        }
                                    >
                                        Suivant
                                        <ChevronRight className="h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </CompanyLayout>
    );
}
