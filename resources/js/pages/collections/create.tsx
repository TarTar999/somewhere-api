import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { FolderOpen, MapPin, Check, ArrowLeft } from 'lucide-react';
import { useState } from 'react';

interface Address {
    id: number;
    swAddress: string;
    displayName: string;
    quarter: string;
    latitude: number;
    longitude: number;
}

interface Props {
    addresses: Address[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Collections', href: '/collections' },
    { title: 'Nouvelle', href: '/collections/create' },
];

const ICONS = ['📍', '🏠', '🏢', '🏪', '🏭', '🏥', '🏫', '⭐', '❤️', '📦'];
const COLORS = ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6', '#EC4899', '#06B6D4', '#84CC16'];

export default function CollectionCreate({ addresses }: Props) {
    const [name, setName] = useState('');
    const [description, setDescription] = useState('');
    const [icon, setIcon] = useState<string | null>(null);
    const [color, setColor] = useState<string | null>(null);
    const [selectedAddresses, setSelectedAddresses] = useState<number[]>([]);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [errors, setErrors] = useState<Record<string, string>>({});

    const toggleAddress = (id: number) => {
        setSelectedAddresses((prev) =>
            prev.includes(id) ? prev.filter((a) => a !== id) : [...prev, id]
        );
    };

    const handleSubmit = (e: React.FormEvent) => {
        e.preventDefault();
        setIsSubmitting(true);
        setErrors({});

        router.post(
            '/api/v1/collections',
            {
                name,
                description: description || null,
                icon,
                color,
                type: 'custom',
                address_ids: selectedAddresses,
            },
            {
                onSuccess: () => {
                    router.visit('/collections');
                },
                onError: (errors) => {
                    setErrors(errors);
                    setIsSubmitting(false);
                },
                onFinish: () => {
                    setIsSubmitting(false);
                },
            }
        );
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Nouvelle Collection" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                <div className="flex items-center gap-4">
                    <Button variant="ghost" size="icon" onClick={() => history.back()}>
                        <ArrowLeft className="h-4 w-4" />
                    </Button>
                    <div>
                        <h1 className="text-2xl font-bold flex items-center gap-2">
                            <FolderOpen className="h-6 w-6" />
                            Nouvelle Collection
                        </h1>
                        <p className="text-muted-foreground">
                            Créez une collection pour organiser vos adresses
                        </p>
                    </div>
                </div>

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
                                Sélectionnez les adresses à ajouter
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            {addresses.length === 0 ? (
                                <div className="text-center py-8 text-muted-foreground">
                                    <MapPin className="mx-auto h-12 w-12 text-muted-foreground/50" />
                                    <p className="mt-2">Aucune adresse disponible</p>
                                    <p className="text-sm">
                                        Créez d'abord des adresses via l'application mobile
                                    </p>
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
                            {isSubmitting ? 'Création...' : 'Créer la collection'}
                        </Button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
