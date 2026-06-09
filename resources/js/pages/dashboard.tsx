import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { FileText, MapPin, CheckCircle, Clock, AlertTriangle, Download, Plus, FolderOpen, Truck, Building2, ArrowRight, ExternalLink } from 'lucide-react';

interface Address {
    id: number;
    swAddress: string;
    displayName: string;
    quarter: string;
    subQuarter: string;
    houseType: string;
    verificationStatus: string;
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

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Tableau de bord',
        href: dashboard().url,
    },
];

function getStatusBadge(status: string) {
    switch (status) {
        case 'approved':
        case 'active':
            return <Badge className="bg-green-100 text-green-800">Actif</Badge>;
        case 'pending':
            return <Badge className="bg-yellow-100 text-yellow-800">En attente</Badge>;
        case 'expired':
            return <Badge className="bg-red-100 text-red-800">Expiré</Badge>;
        case 'rejected':
            return <Badge className="bg-red-100 text-red-800">Rejeté</Badge>;
        default:
            return <Badge variant="secondary">{status}</Badge>;
    }
}

function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

export default function Dashboard({ addresses, documents, collections, stats, hasCompany }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tableau de bord" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Quick Actions */}
                <div className="flex flex-wrap gap-3">
                    <Button asChild variant="outline">
                        <Link href="/collections">
                            <FolderOpen className="mr-2 h-4 w-4" />
                            Mes Collections
                        </Link>
                    </Button>
                    <Button asChild variant="outline">
                        <Link href="/deliveries">
                            <Truck className="mr-2 h-4 w-4" />
                            Livraisons
                        </Link>
                    </Button>
                    {hasCompany && (
                        <Button asChild>
                            <Link href="/company">
                                <Building2 className="mr-2 h-4 w-4" />
                                Espace Entreprise
                            </Link>
                        </Button>
                    )}
                </div>

                {/* Stats Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card className="cursor-pointer hover:shadow-md transition-shadow" onClick={() => document.getElementById('addresses')?.scrollIntoView({ behavior: 'smooth' })}>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Adresses</CardTitle>
                            <MapPin className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.totalAddresses}</div>
                            <p className="text-xs text-muted-foreground">
                                {stats.verifiedAddresses} vérifiée(s)
                            </p>
                        </CardContent>
                    </Card>
                    <Card className="cursor-pointer hover:shadow-md transition-shadow" onClick={() => document.getElementById('documents')?.scrollIntoView({ behavior: 'smooth' })}>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Documents</CardTitle>
                            <FileText className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.totalDocuments}</div>
                            <p className="text-xs text-muted-foreground">
                                {stats.activeDocuments} actif(s)
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Collections</CardTitle>
                            <FolderOpen className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.totalCollections}</div>
                            <p className="text-xs text-muted-foreground">
                                collection(s) créée(s)
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Livraisons</CardTitle>
                            <Truck className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.pendingDeliveries}</div>
                            <p className="text-xs text-muted-foreground">
                                en attente
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Addresses Section */}
                <Card id="addresses">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle className="flex items-center gap-2">
                                <MapPin className="h-5 w-5" />
                                Mes Adresses
                            </CardTitle>
                            <CardDescription>
                                Liste de vos adresses enregistrées
                            </CardDescription>
                        </div>
                        <Button asChild variant="outline" size="sm">
                            <Link href="/collections/create">
                                <Plus className="mr-2 h-4 w-4" />
                                Créer une collection
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {addresses.length === 0 ? (
                            <p className="text-center text-muted-foreground py-8">
                                Aucune adresse enregistrée. Utilisez l'application mobile pour ajouter une adresse.
                            </p>
                        ) : (
                            <div className="space-y-4">
                                {addresses.map((address) => (
                                    <div
                                        key={address.id}
                                        className="flex items-center justify-between rounded-lg border p-4"
                                    >
                                        <div className="space-y-1">
                                            <p className="font-medium">{address.swAddress}</p>
                                            <p className="text-sm text-muted-foreground">
                                                {address.quarter}
                                                {address.subQuarter && `, ${address.subQuarter}`}
                                            </p>
                                            {address.street && (
                                                <p className="text-xs text-muted-foreground">
                                                    {address.street.displayName}
                                                </p>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-4">
                                            {getStatusBadge(address.verificationStatus)}
                                            <span className="text-xs text-muted-foreground">
                                                {formatDate(address.createdAt)}
                                            </span>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Documents Section */}
                <Card id="documents">
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FileText className="h-5 w-5" />
                            Mes Documents
                        </CardTitle>
                        <CardDescription>
                            Plans de localisation et attestations de résidence
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {documents.length === 0 ? (
                            <p className="text-center text-muted-foreground py-8">
                                Aucun document généré. Utilisez l'application mobile pour créer un document.
                            </p>
                        ) : (
                            <div className="space-y-4">
                                {documents.map((doc) => (
                                    <div
                                        key={doc.id}
                                        className="flex items-center justify-between rounded-lg border p-4"
                                    >
                                        <div className="space-y-1">
                                            <div className="flex items-center gap-2">
                                                <p className="font-medium">{doc.documentTypeLabel}</p>
                                                {doc.isActive && (
                                                    <CheckCircle className="h-4 w-4 text-green-600" />
                                                )}
                                            </div>
                                            <p className="text-sm text-muted-foreground">
                                                {doc.documentNumber}
                                            </p>
                                            {doc.address && (
                                                <p className="text-xs text-muted-foreground">
                                                    {doc.address.swAddress}
                                                </p>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-4">
                                            {doc.isExpired ? (
                                                <Badge className="bg-red-100 text-red-800">Expiré</Badge>
                                            ) : doc.isActive ? (
                                                <Badge className="bg-green-100 text-green-800">Actif</Badge>
                                            ) : (
                                                <Badge variant="secondary">{doc.status}</Badge>
                                            )}
                                            <div className="text-right">
                                                <p className="text-xs text-muted-foreground">
                                                    Expire le {formatDate(doc.expiresAt)}
                                                </p>
                                                <p className="text-xs text-muted-foreground flex items-center gap-1">
                                                    <Download className="h-3 w-3" />
                                                    {doc.downloadCount} téléchargement(s)
                                                </p>
                                            </div>
                                            {doc.isActive && !doc.isExpired && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => window.open(`/api/v1/proof-of-location/${doc.id}/download`, '_blank')}
                                                >
                                                    <Download className="mr-1 h-3 w-3" />
                                                    PDF
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Collections Section */}
                <Card id="collections">
                    <CardHeader className="flex flex-row items-center justify-between">
                        <div>
                            <CardTitle className="flex items-center gap-2">
                                <FolderOpen className="h-5 w-5" />
                                Mes Collections
                            </CardTitle>
                            <CardDescription>
                                Groupez vos adresses et partagez-les
                            </CardDescription>
                        </div>
                        <Button asChild size="sm">
                            <Link href="/collections">
                                Voir tout
                                <ArrowRight className="ml-2 h-4 w-4" />
                            </Link>
                        </Button>
                    </CardHeader>
                    <CardContent>
                        {collections.length === 0 ? (
                            <div className="text-center py-8">
                                <FolderOpen className="mx-auto h-12 w-12 text-muted-foreground/50" />
                                <p className="mt-2 text-muted-foreground">
                                    Aucune collection créée
                                </p>
                                <Button asChild variant="outline" className="mt-4">
                                    <Link href="/collections/create">
                                        <Plus className="mr-2 h-4 w-4" />
                                        Créer ma première collection
                                    </Link>
                                </Button>
                            </div>
                        ) : (
                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                {collections.slice(0, 6).map((collection) => (
                                    <Link
                                        key={collection.id}
                                        href={`/collections/${collection.id}`}
                                        className="block rounded-lg border p-4 hover:bg-muted/50 transition-colors"
                                    >
                                        <div className="flex items-center justify-between">
                                            <p className="font-medium">{collection.name}</p>
                                            {collection.isPublic && (
                                                <Badge variant="outline" className="text-xs">
                                                    <ExternalLink className="mr-1 h-3 w-3" />
                                                    Public
                                                </Badge>
                                            )}
                                        </div>
                                        {collection.description && (
                                            <p className="mt-1 text-sm text-muted-foreground line-clamp-2">
                                                {collection.description}
                                            </p>
                                        )}
                                        <p className="mt-2 text-xs text-muted-foreground">
                                            {collection.addressCount} adresse(s)
                                        </p>
                                    </Link>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>

                {/* Enterprise CTA */}
                {!hasCompany && (
                    <Card className="border-primary/20 bg-primary/5">
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <Building2 className="h-5 w-5" />
                                Espace Entreprise
                            </CardTitle>
                            <CardDescription>
                                Créez des zones géographiques, gérez vos points d'intérêt, lancez des campagnes terrain et plus encore.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button asChild>
                                <Link href="/company/create">
                                    <Plus className="mr-2 h-4 w-4" />
                                    Créer mon entreprise
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                )}
            </div>
        </AppLayout>
    );
}
