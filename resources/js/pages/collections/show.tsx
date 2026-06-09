import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import {
    FolderOpen,
    MapPin,
    Share2,
    Edit,
    ArrowLeft,
    Download,
    User,
    Trash2,
    Copy,
    Check,
    ExternalLink,
} from 'lucide-react';
import { useEffect, useState } from 'react';

interface Address {
    id: number;
    swAddress: string;
    displayName: string;
    quarter: string;
    latitude: number;
    longitude: number;
    verificationStatus: string;
}

interface SharedUser {
    id: number;
    user: {
        id: number;
        name: string;
        phone: string;
    };
    permissions: string;
    sharedAt: string;
}

interface Collection {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    icon: string | null;
    color: string | null;
    type: string;
    role: 'owner' | 'shared';
    addressCount: number;
    addresses: Address[];
    createdAt: string;
}

interface Props {
    collection: Collection;
    isOwner: boolean;
    sharedWith: SharedUser[];
}

const breadcrumbs = (collection: Collection): BreadcrumbItem[] => [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Collections', href: '/collections' },
    { title: collection.name, href: `/collections/${collection.id}` },
];

function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

export default function CollectionShow({ collection, isOwner, sharedWith }: Props) {
    const { url } = usePage();
    const [showShareDialog, setShowShareDialog] = useState(false);
    const [sharePhone, setSharePhone] = useState('');
    const [sharePermissions, setSharePermissions] = useState<'view' | 'edit'>('view');
    const [isSharing, setIsSharing] = useState(false);
    const [shareError, setShareError] = useState('');
    const [copied, setCopied] = useState(false);

    // Open share dialog if ?share=1 in URL
    useEffect(() => {
        if (url.includes('share=1')) {
            setShowShareDialog(true);
        }
    }, [url]);

    const handleShare = async () => {
        if (!sharePhone.trim()) return;

        setIsSharing(true);
        setShareError('');

        try {
            const response = await fetch(`/api/v1/collections/${collection.id}/share`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    recipientPhone: sharePhone,
                    permissions: sharePermissions,
                }),
            });

            const data = await response.json();

            if (!response.ok) {
                setShareError(data.message || 'Erreur lors du partage');
                return;
            }

            setShowShareDialog(false);
            setSharePhone('');
            router.reload();
        } catch {
            setShareError('Erreur de connexion');
        } finally {
            setIsSharing(false);
        }
    };

    const handleRevokeShare = async (userId: number) => {
        if (!confirm('Révoquer l\'accès pour cet utilisateur ?')) return;

        try {
            await fetch(`/api/v1/collections/${collection.id}/share/${userId}`, {
                method: 'DELETE',
            });
            router.reload();
        } catch {
            // Handle error
        }
    };

    const copyShareLink = () => {
        const link = `${window.location.origin}/share/collection/${collection.slug}`;
        navigator.clipboard.writeText(link);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const handleExport = (format: 'csv' | 'kml' | 'geojson') => {
        window.location.href = `/api/v1/collections/${collection.id}/export?format=${format}`;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs(collection)}>
            <Head title={collection.name} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" onClick={() => history.back()}>
                            <ArrowLeft className="h-4 w-4" />
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold flex items-center gap-2">
                                {collection.icon && <span>{collection.icon}</span>}
                                {collection.name}
                                {collection.type !== 'custom' && (
                                    <Badge variant="outline" className="ml-2">
                                        {collection.type === 'system' ? 'Système' : 'Livraison'}
                                    </Badge>
                                )}
                            </h1>
                            {collection.description && (
                                <p className="text-muted-foreground mt-1">
                                    {collection.description}
                                </p>
                            )}
                        </div>
                    </div>
                    {isOwner && (
                        <div className="flex items-center gap-2">
                            <Button variant="outline" onClick={() => setShowShareDialog(true)}>
                                <Share2 className="mr-2 h-4 w-4" />
                                Partager
                            </Button>
                            <Button asChild variant="outline">
                                <Link href={`/collections/${collection.id}/edit`}>
                                    <Edit className="mr-2 h-4 w-4" />
                                    Modifier
                                </Link>
                            </Button>
                        </div>
                    )}
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Addresses List */}
                    <div className="lg:col-span-2">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between">
                                <div>
                                    <CardTitle className="flex items-center gap-2">
                                        <MapPin className="h-5 w-5" />
                                        Adresses ({collection.addresses.length})
                                    </CardTitle>
                                    <CardDescription>
                                        Adresses dans cette collection
                                    </CardDescription>
                                </div>
                                <div className="flex gap-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handleExport('csv')}
                                    >
                                        <Download className="mr-1 h-3 w-3" />
                                        CSV
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handleExport('kml')}
                                    >
                                        KML
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handleExport('geojson')}
                                    >
                                        GeoJSON
                                    </Button>
                                </div>
                            </CardHeader>
                            <CardContent>
                                {collection.addresses.length === 0 ? (
                                    <div className="text-center py-8 text-muted-foreground">
                                        <MapPin className="mx-auto h-12 w-12 text-muted-foreground/50" />
                                        <p className="mt-2">Aucune adresse dans cette collection</p>
                                        {isOwner && (
                                            <Button asChild className="mt-4" variant="outline">
                                                <Link href={`/collections/${collection.id}/edit`}>
                                                    Ajouter des adresses
                                                </Link>
                                            </Button>
                                        )}
                                    </div>
                                ) : (
                                    <div className="space-y-3">
                                        {collection.addresses.map((address) => (
                                            <div
                                                key={address.id}
                                                className="flex items-center justify-between rounded-lg border p-4"
                                            >
                                                <div className="flex-1 min-w-0">
                                                    <p className="font-medium">{address.swAddress}</p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {address.quarter}
                                                    </p>
                                                </div>
                                                <div className="flex items-center gap-2">
                                                    <Badge
                                                        variant={
                                                            address.verificationStatus === 'approved'
                                                                ? 'default'
                                                                : 'secondary'
                                                        }
                                                    >
                                                        {address.verificationStatus === 'approved'
                                                            ? 'Vérifié'
                                                            : 'En attente'}
                                                    </Badge>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                        onClick={() => {
                                                            window.open(
                                                                `https://maps.google.com/?q=${address.latitude},${address.longitude}`,
                                                                '_blank'
                                                            );
                                                        }}
                                                    >
                                                        <ExternalLink className="h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Info Card */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Informations</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Créée le</span>
                                    <span>{formatDate(collection.createdAt)}</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Type</span>
                                    <span className="capitalize">{collection.type}</span>
                                </div>
                                <div className="flex justify-between text-sm">
                                    <span className="text-muted-foreground">Adresses</span>
                                    <span>{collection.addressCount}</span>
                                </div>
                                {collection.color && (
                                    <div className="flex justify-between text-sm items-center">
                                        <span className="text-muted-foreground">Couleur</span>
                                        <div
                                            className="h-5 w-5 rounded-full"
                                            style={{ backgroundColor: collection.color }}
                                        />
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Shared With */}
                        {isOwner && sharedWith.length > 0 && (
                            <Card>
                                <CardHeader>
                                    <CardTitle className="flex items-center gap-2">
                                        <Share2 className="h-5 w-5" />
                                        Partagée avec
                                    </CardTitle>
                                </CardHeader>
                                <CardContent className="space-y-3">
                                    {sharedWith.map((share) => (
                                        <div
                                            key={share.id}
                                            className="flex items-center justify-between"
                                        >
                                            <div className="flex items-center gap-2">
                                                <User className="h-4 w-4 text-muted-foreground" />
                                                <div>
                                                    <p className="text-sm font-medium">
                                                        {share.user.name}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {share.permissions === 'edit'
                                                            ? 'Modification'
                                                            : 'Lecture'}
                                                    </p>
                                                </div>
                                            </div>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8 text-destructive"
                                                onClick={() => handleRevokeShare(share.user.id)}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    ))}
                                </CardContent>
                            </Card>
                        )}
                    </div>
                </div>
            </div>

            {/* Share Dialog */}
            <Dialog open={showShareDialog} onOpenChange={setShowShareDialog}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Partager la collection</DialogTitle>
                        <DialogDescription>
                            Partagez cette collection avec d'autres utilisateurs
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="phone">Numéro de téléphone</Label>
                            <Input
                                id="phone"
                                type="tel"
                                placeholder="+237..."
                                value={sharePhone}
                                onChange={(e) => setSharePhone(e.target.value)}
                            />
                        </div>

                        <div className="space-y-2">
                            <Label>Permissions</Label>
                            <Select
                                value={sharePermissions}
                                onValueChange={(v) => setSharePermissions(v as 'view' | 'edit')}
                            >
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="view">Lecture seule</SelectItem>
                                    <SelectItem value="edit">Modification</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        {shareError && (
                            <p className="text-sm text-destructive">{shareError}</p>
                        )}

                        <div className="pt-4 border-t">
                            <Label className="text-muted-foreground">
                                Ou copier le lien de partage
                            </Label>
                            <Button
                                variant="outline"
                                className="w-full mt-2"
                                onClick={copyShareLink}
                            >
                                {copied ? (
                                    <>
                                        <Check className="mr-2 h-4 w-4" />
                                        Copié !
                                    </>
                                ) : (
                                    <>
                                        <Copy className="mr-2 h-4 w-4" />
                                        Copier le lien
                                    </>
                                )}
                            </Button>
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowShareDialog(false)}>
                            Annuler
                        </Button>
                        <Button onClick={handleShare} disabled={isSharing || !sharePhone.trim()}>
                            {isSharing ? 'Partage...' : 'Partager'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
