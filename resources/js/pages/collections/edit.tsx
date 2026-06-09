import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { FolderOpen, MapPin, Check, ArrowLeft, Trash2 } from 'lucide-react';
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
    addresses: Address[];
}

interface Props {
    collection: Collection;
    addresses: Address[];
}

const ICONS = ['📍', '🏠', '🏢', '🏪', '🏭', '🏥', '🏫', '⭐', '❤️', '📦'];
const COLORS = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16'];

export default function CollectionEdit({ collection, addresses }: Props) {
    const [name, setName] = useState(collection.name);
    const [description, setDescription] = useState(collection.description || '');
    const [icon, setIcon] = useState<string | null>(collection.icon);
    const [color, setColor] = useState<string | null>(collection.color);
    const [selectedAddresses, setSelectedAddresses] = useState<number[]>(
        collection.addresses.map((a) => a.id)
    );
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tableau de bord', href: '/dashboard' },
        { title: 'Collections', href: '/collections' },
        { title: collection.name, href: `/collections/${collection.id}` },
        { title: 'Modifier', href: `/collections/${collection.id}/edit` },
    ];

    const toggleAddress = (id: number) => {
        setSelectedAddresses((prev) =>
            prev.includes(id) ? prev.filter((a) => a !== id) : [...prev, id]
        );
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        setErrors({});

        try {
            // Update collection details
            const response = await fetch(`/api/v1/collections/${collection.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    name,
                    description: description || null,
                    icon,
                    color,
                }),
            });

            if (!response.ok) {
                const data = await response.json();
                setErrors(data.errors || { general: data.message });
                return;
            }

            // Update addresses
            await fetch(`/api/v1/collections/${collection.id}/addresses`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    address_ids: selectedAddresses,
                }),
            });

            router.visit(`/collections/${collection.id}`);
        } catch {
            setErrors({ general: 'Erreur de connexion' });
        } finally {
            setIsSubmitting(false);
        }
    };

    const handleDelete = () => {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette collection ?')) return;

        router.delete(`/api/v1/collections/${collection.id}`, {
            onSuccess: () => {
                router.visit('/collections');
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Modifier ${collection.name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" onClick={() => history.back()}>
                            <ArrowLeft className="h-4 w-4" />
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold flex items-center gap-2">
                                <FolderOpen className="h-6 w-6" />
                                Modifier la collection
                            </h1>
                            <p className="text-muted-foreground">{collection.name}</p>
                        </div>
                    </div>
                    <Button variant="destructive" onClick={handleDelete}>
                        <Trash2 className="mr-2 h-4 w-4" />
                        Supprimer
                    </Button>
                </div>

                {errors.general && (
                    <div className="rounded-lg border border-destructive bg-destructive/10 p-4 text-destructive">
                        {errors.general}
                    </div>
                )}

                <form onSubmit={handleSubmit} className="grid gap-6 lg:grid-cols-2">
                    {/* Collection Details */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Détails</CardTitle>
                            <CardDescription>
                                Informations sur votre collection
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="name">Nom *</Label>
                                <Input
                                    id="name"
                                    value={name}
                                    onChange={(e) => setName(e.target.value)}
                                    placeholder="Ma collection"
                                    required
                                />
                                {errors.name && (
                                    <p className="text-sm text-destructive">{errors.name}</p>
                                )}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Description</Label>
                                <Textarea
                                    id="description"
                                    value={description}
                                    onChange={(e) => setDescription(e.target.value)}
                                    placeholder="Description de la collection..."
                                    rows={3}
                                />
                            </div>

                            <div className="space-y-2">
                                <Label>Icône</Label>
                                <div className="flex flex-wrap gap-2">
                                    {ICONS.map((i) => (
                                        <button
                                            key={i}
                                            type="button"
                                            onClick={() => setIcon(icon === i ? null : i)}
                                            className={`flex h-10 w-10 items-center justify-center rounded-lg border text-xl transition-colors ${
                                                icon === i
                                                    ? 'border-primary bg-primary/10'
                                                    : 'hover:bg-muted'
                                            }`}
                                        >
                                            {i}
                                        </button>
                                    ))}
                                </div>
                            </div>

                            <div className="space-y-2">
                                <Label>Couleur</Label>
                                <div className="flex flex-wrap gap-2">
                                    {COLORS.map((c) => (
                                        <button
                                            key={c}
                                            type="button"
                                            onClick={() => setColor(color === c ? null : c)}
                                            className={`flex h-8 w-8 items-center justify-center rounded-full border-2 transition-transform ${
                                                color === c
                                                    ? 'scale-110 border-primary'
                                                    : 'border-transparent hover:scale-105'
                                            }`}
                                            style={{ backgroundColor: c }}
                                        >
                                            {color === c && (
                                                <Check className="h-4 w-4 text-white" />
                                            )}
                                        </button>
                                    ))}
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Address Selection */}
                    <Card>
                        <CardHeader>
                            <CardTitle>
                                Adresses ({selectedAddresses.length} sélectionnée(s))
                            </CardTitle>
                            <CardDescription>
                                Sélectionnez les adresses à inclure
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {addresses.length === 0 ? (
                                <div className="text-center py-8 text-muted-foreground">
                                    <MapPin className="mx-auto h-12 w-12 text-muted-foreground/50" />
                                    <p className="mt-2">Aucune adresse disponible</p>
                                </div>
                            ) : (
                                <div className="space-y-2 max-h-[400px] overflow-y-auto">
                                    {addresses.map((address) => (
                                        <label
                                            key={address.id}
                                            className={`flex items-center gap-3 rounded-lg border p-3 cursor-pointer transition-colors ${
                                                selectedAddresses.includes(address.id)
                                                    ? 'border-primary bg-primary/5'
                                                    : 'hover:bg-muted/50'
                                            }`}
                                        >
                                            <Checkbox
                                                checked={selectedAddresses.includes(address.id)}
                                                onCheckedChange={() => toggleAddress(address.id)}
                                            />
                                            <div className="flex-1 min-w-0">
                                                <p className="font-medium truncate">
                                                    {address.swAddress}
                                                </p>
                                                <p className="text-sm text-muted-foreground truncate">
                                                    {address.quarter}
                                                </p>
                                            </div>
                                            <MapPin className="h-4 w-4 text-muted-foreground flex-shrink-0" />
                                        </label>
                                    ))}
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Submit */}
                    <div className="lg:col-span-2 flex justify-end gap-4">
                        <Button type="button" variant="outline" onClick={() => history.back()}>
                            Annuler
                        </Button>
                        <Button type="submit" disabled={isSubmitting || !name.trim()}>
                            {isSubmitting ? 'Enregistrement...' : 'Enregistrer'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
