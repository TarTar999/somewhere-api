import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { FolderOpen, Plus, Search, MapPin, Share2, Trash2, Edit, ExternalLink, User } from 'lucide-react';
import { useState } from 'react';

interface Address {
    id: number;
    swAddress: string;
    displayName: string;
    quarter: string;
    latitude: number;
    longitude: number;
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
    permissions?: string;
    sharedBy?: {
        id: number;
        name: string;
    };
}

interface Props {
    ownCollections: Collection[];
    sharedCollections: Collection[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Collections', href: '/collections' },
];

function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function CollectionCard({ collection, onDelete }: { collection: Collection; onDelete?: (id: number) => void }) {
    const isOwner = collection.role === 'owner';

    return (
        <Card className="hover:shadow-md transition-shadow">
            <CardHeader className="pb-3">
                <div className="flex items-start justify-between">
                    <div className="flex-1">
                        <Link href={`/collections/${collection.id}`}>
                            <CardTitle className="text-lg hover:text-primary transition-colors flex items-center gap-2">
                                {collection.icon && <span>{collection.icon}</span>}
                                {collection.name}
                            </CardTitle>
                        </Link>
                        {collection.description && (
                            <CardDescription className="mt-1 line-clamp-2">
                                {collection.description}
                            </CardDescription>
                        )}
                    </div>
                    {collection.type !== 'custom' && (
                        <Badge variant="outline" className="ml-2">
                            {collection.type === 'system' ? 'Système' : 'Livraison'}
                        </Badge>
                    )}
                </div>
            </CardHeader>
            <CardContent>
                <div className="flex items-center justify-between text-sm text-muted-foreground">
                    <div className="flex items-center gap-4">
                        <span className="flex items-center gap-1">
                            <MapPin className="h-4 w-4" />
                            {collection.addressCount} adresse(s)
                        </span>
                        {!isOwner && collection.sharedBy && (
                            <span className="flex items-center gap-1">
                                <User className="h-4 w-4" />
                                {collection.sharedBy.name}
                            </span>
                        )}
                    </div>
                    <span>{formatDate(collection.createdAt)}</span>
                </div>

                {isOwner && (
                    <div className="mt-4 flex items-center gap-2">
                        <Button asChild variant="outline" size="sm">
                            <Link href={`/collections/${collection.id}/edit`}>
                                <Edit className="mr-1 h-3 w-3" />
                                Modifier
                            </Link>
                        </Button>
                        <Button asChild variant="outline" size="sm">
                            <Link href={`/collections/${collection.id}?share=1`}>
                                <Share2 className="mr-1 h-3 w-3" />
                                Partager
                            </Link>
                        </Button>
                        {onDelete && (
                            <Button
                                variant="ghost"
                                size="sm"
                                className="text-destructive hover:text-destructive"
                                onClick={() => onDelete(collection.id)}
                            >
                                <Trash2 className="h-3 w-3" />
                            </Button>
                        )}
                    </div>
                )}

                {!isOwner && (
                    <div className="mt-4">
                        <Badge variant={collection.permissions === 'edit' ? 'default' : 'secondary'}>
                            {collection.permissions === 'edit' ? 'Modification' : 'Lecture seule'}
                        </Badge>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}

export default function CollectionsIndex({ ownCollections, sharedCollections }: Props) {
    const [search, setSearch] = useState('');

    const filteredOwn = ownCollections.filter(
        (c) =>
            c.name.toLowerCase().includes(search.toLowerCase()) ||
            c.description?.toLowerCase().includes(search.toLowerCase())
    );

    const filteredShared = sharedCollections.filter(
        (c) =>
            c.name.toLowerCase().includes(search.toLowerCase()) ||
            c.description?.toLowerCase().includes(search.toLowerCase())
    );

    const handleDelete = (id: number) => {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette collection ?')) {
            router.delete(`/api/v1/collections/${id}`, {
                preserveScroll: true,
                onSuccess: () => {
                    router.reload();
                },
            });
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Mes Collections" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold flex items-center gap-2">
                            <FolderOpen className="h-6 w-6" />
                            Mes Collections
                        </h1>
                        <p className="text-muted-foreground">
                            Organisez vos adresses en collections et partagez-les
                        </p>
                    </div>
                    <Button asChild>
                        <Link href="/collections/create">
                            <Plus className="mr-2 h-4 w-4" />
                            Nouvelle collection
                        </Link>
                    </Button>
                </div>

                {/* Search */}
                <div className="relative max-w-md">
                    <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        placeholder="Rechercher une collection..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        className="pl-10"
                    />
                </div>

                {/* Own Collections */}
                <div>
                    <h2 className="text-lg font-semibold mb-4">
                        Mes collections ({filteredOwn.length})
                    </h2>
                    {filteredOwn.length === 0 ? (
                        <Card>
                            <CardContent className="flex flex-col items-center justify-center py-12">
                                <FolderOpen className="h-12 w-12 text-muted-foreground/50" />
                                <p className="mt-4 text-muted-foreground">
                                    {search
                                        ? 'Aucune collection ne correspond à votre recherche'
                                        : 'Vous n\'avez pas encore de collection'}
                                </p>
                                {!search && (
                                    <Button asChild className="mt-4">
                                        <Link href="/collections/create">
                                            <Plus className="mr-2 h-4 w-4" />
                                            Créer ma première collection
                                        </Link>
                                    </Button>
                                )}
                            </CardContent>
                        </Card>
                    ) : (
                        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                            {filteredOwn.map((collection) => (
                                <CollectionCard
                                    key={collection.id}
                                    collection={collection}
                                    onDelete={handleDelete}
                                />
                            ))}
                        </div>
                    )}
                </div>

                {/* Shared Collections */}
                {sharedCollections.length > 0 && (
                    <div>
                        <h2 className="text-lg font-semibold mb-4 flex items-center gap-2">
                            <Share2 className="h-5 w-5" />
                            Partagées avec moi ({filteredShared.length})
                        </h2>
                        {filteredShared.length === 0 ? (
                            <Card>
                                <CardContent className="py-8 text-center text-muted-foreground">
                                    Aucune collection partagée ne correspond à votre recherche
                                </CardContent>
                            </Card>
                        ) : (
                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                {filteredShared.map((collection) => (
                                    <CollectionCard key={collection.id} collection={collection} />
                                ))}
                            </div>
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
