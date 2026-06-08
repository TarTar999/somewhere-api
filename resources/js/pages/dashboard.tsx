import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { FileText, MapPin, CheckCircle, Clock, AlertTriangle, Download } from 'lucide-react';

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

interface Stats {
    totalAddresses: number;
    verifiedAddresses: number;
    pendingAddresses: number;
    totalDocuments: number;
    activeDocuments: number;
    expiredDocuments: number;
}

interface Props {
    addresses: Address[];
    documents: Document[];
    stats: Stats;
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

export default function Dashboard({ addresses, documents, stats }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tableau de bord" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Stats Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
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
                    <Card>
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
                            <CardTitle className="text-sm font-medium">En attente</CardTitle>
                            <Clock className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.pendingAddresses}</div>
                            <p className="text-xs text-muted-foreground">
                                adresse(s) en vérification
                            </p>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Expirés</CardTitle>
                            <AlertTriangle className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.expiredDocuments}</div>
                            <p className="text-xs text-muted-foreground">
                                document(s) à renouveler
                            </p>
                        </CardContent>
                    </Card>
                </div>

                {/* Addresses Section */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <MapPin className="h-5 w-5" />
                            Mes Adresses
                        </CardTitle>
                        <CardDescription>
                            Liste de vos adresses enregistrées
                        </CardDescription>
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
                <Card>
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
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
