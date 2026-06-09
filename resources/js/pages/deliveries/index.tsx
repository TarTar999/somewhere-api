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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import {
    Truck,
    Plus,
    Send,
    Inbox,
    Clock,
    CheckCircle,
    XCircle,
    ArrowRight,
    Copy,
    MapPin,
    Package,
} from 'lucide-react';
import { useState } from 'react';

interface Address {
    id: number;
    swAddress: string;
    displayName: string;
    quarter: string;
}

interface User {
    id: number;
    name: string;
    phone: string;
}

interface DeliveryAddress {
    id: number;
    swAddress: string;
    displayName: string;
    latitude: number;
    longitude: number;
}

interface DeliveryRequest {
    id: number;
    title: string;
    description: string | null;
    value: number | null;
    currency: string;
    status: string;
    role: 'sent' | 'received';
    initiatorConfirmed: boolean;
    recipientConfirmed: boolean;
    shareUrl: string;
    shareToken: string;
    initiator: User | null;
    recipient: User | null;
    pickupAddress: DeliveryAddress | null;
    deliveryAddress: DeliveryAddress | null;
    createdAt: string;
    acceptedAt: string | null;
    completedAt: string | null;
}

interface Stats {
    totalSent: number;
    totalReceived: number;
    pending: number;
    inProgress: number;
    completed: number;
}

interface Props {
    sent: DeliveryRequest[];
    received: DeliveryRequest[];
    stats: Stats;
    addresses: Address[];
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Tableau de bord', href: '/dashboard' },
    { title: 'Livraisons', href: '/deliveries' },
];

function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

function getStatusBadge(status: string) {
    switch (status) {
        case 'pending':
            return <Badge className="bg-yellow-100 text-yellow-800">En attente</Badge>;
        case 'accepted':
            return <Badge className="bg-blue-100 text-blue-800">Acceptée</Badge>;
        case 'in_progress':
            return <Badge className="bg-purple-100 text-purple-800">En cours</Badge>;
        case 'completed':
            return <Badge className="bg-green-100 text-green-800">Terminée</Badge>;
        case 'cancelled':
            return <Badge className="bg-red-100 text-red-800">Annulée</Badge>;
        default:
            return <Badge variant="secondary">{status}</Badge>;
    }
}

function DeliveryCard({ delivery }: { delivery: DeliveryRequest }) {
    const [copied, setCopied] = useState(false);

    const copyShareLink = () => {
        navigator.clipboard.writeText(delivery.shareUrl);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const otherParty = delivery.role === 'sent' ? delivery.recipient : delivery.initiator;

    return (
        <Card className="hover:shadow-md transition-shadow">
            <CardContent className="pt-6">
                <div className="flex items-start justify-between">
                    <div className="flex-1">
                        <Link href={`/deliveries/${delivery.id}`}>
                            <h3 className="font-medium hover:text-primary transition-colors">
                                {delivery.title}
                            </h3>
                        </Link>
                        {delivery.description && (
                            <p className="mt-1 text-sm text-muted-foreground line-clamp-2">
                                {delivery.description}
                            </p>
                        )}
                    </div>
                    {getStatusBadge(delivery.status)}
                </div>

                <div className="mt-4 grid gap-2 text-sm">
                    {delivery.pickupAddress && (
                        <div className="flex items-center gap-2 text-muted-foreground">
                            <Package className="h-4 w-4" />
                            <span>De: {delivery.pickupAddress.swAddress}</span>
                        </div>
                    )}
                    {delivery.deliveryAddress && (
                        <div className="flex items-center gap-2 text-muted-foreground">
                            <MapPin className="h-4 w-4" />
                            <span>Vers: {delivery.deliveryAddress.swAddress}</span>
                        </div>
                    )}
                    {otherParty && (
                        <div className="flex items-center gap-2 text-muted-foreground">
                            <span>
                                {delivery.role === 'sent' ? 'Destinataire' : 'Expéditeur'}:{' '}
                                {otherParty.name}
                            </span>
                        </div>
                    )}
                </div>

                <div className="mt-4 flex items-center justify-between">
                    <span className="text-xs text-muted-foreground">
                        {formatDate(delivery.createdAt)}
                    </span>
                    <div className="flex items-center gap-2">
                        {delivery.status === 'pending' && delivery.role === 'sent' && (
                            <Button variant="outline" size="sm" onClick={copyShareLink}>
                                <Copy className="mr-1 h-3 w-3" />
                                {copied ? 'Copié!' : 'Lien'}
                            </Button>
                        )}
                        <Button asChild variant="ghost" size="sm">
                            <Link href={`/deliveries/${delivery.id}`}>
                                Détails
                                <ArrowRight className="ml-1 h-3 w-3" />
                            </Link>
                        </Button>
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

export default function DeliveriesIndex({ sent, received, stats, addresses }: Props) {
    const [showCreateDialog, setShowCreateDialog] = useState(false);
    const [isSubmitting, setIsSubmitting] = useState(false);
    const [formData, setFormData] = useState({
        title: '',
        description: '',
        value: '',
        currency: 'XAF',
        pickup_address_id: '',
    });

    const handleCreate = async () => {
        if (!formData.title || !formData.pickup_address_id) return;

        setIsSubmitting(true);

        try {
            const response = await fetch('/api/v1/delivery-requests', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                },
                body: JSON.stringify({
                    title: formData.title,
                    description: formData.description || null,
                    value: formData.value ? parseFloat(formData.value) : null,
                    currency: formData.currency,
                    pickup_address_id: parseInt(formData.pickup_address_id),
                }),
            });

            if (response.ok) {
                setShowCreateDialog(false);
                setFormData({
                    title: '',
                    description: '',
                    value: '',
                    currency: 'XAF',
                    pickup_address_id: '',
                });
                router.reload();
            }
        } catch {
            // Handle error
        } finally {
            setIsSubmitting(false);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Livraisons" />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold flex items-center gap-2">
                            <Truck className="h-6 w-6" />
                            Livraisons
                        </h1>
                        <p className="text-muted-foreground">
                            Gérez vos demandes de livraison
                        </p>
                    </div>
                    <Button onClick={() => setShowCreateDialog(true)}>
                        <Plus className="mr-2 h-4 w-4" />
                        Nouvelle demande
                    </Button>
                </div>

                {/* Stats */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">En attente</CardTitle>
                            <Clock className="h-4 w-4 text-yellow-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.pending}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">En cours</CardTitle>
                            <Truck className="h-4 w-4 text-purple-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.inProgress}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Terminées</CardTitle>
                            <CheckCircle className="h-4 w-4 text-green-500" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.completed}</div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total</CardTitle>
                            <Package className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">
                                {stats.totalSent + stats.totalReceived}
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Tabs */}
                <Tabs defaultValue="sent" className="flex-1">
                    <TabsList>
                        <TabsTrigger value="sent" className="flex items-center gap-2">
                            <Send className="h-4 w-4" />
                            Envoyées ({sent.length})
                        </TabsTrigger>
                        <TabsTrigger value="received" className="flex items-center gap-2">
                            <Inbox className="h-4 w-4" />
                            Reçues ({received.length})
                        </TabsTrigger>
                    </TabsList>

                    <TabsContent value="sent" className="mt-4">
                        {sent.length === 0 ? (
                            <Card>
                                <CardContent className="flex flex-col items-center justify-center py-12">
                                    <Send className="h-12 w-12 text-muted-foreground/50" />
                                    <p className="mt-4 text-muted-foreground">
                                        Vous n'avez pas encore envoyé de demande
                                    </p>
                                    <Button
                                        className="mt-4"
                                        onClick={() => setShowCreateDialog(true)}
                                    >
                                        <Plus className="mr-2 h-4 w-4" />
                                        Créer une demande
                                    </Button>
                                </CardContent>
                            </Card>
                        ) : (
                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                {sent.map((delivery) => (
                                    <DeliveryCard key={delivery.id} delivery={delivery} />
                                ))}
                            </div>
                        )}
                    </TabsContent>

                    <TabsContent value="received" className="mt-4">
                        {received.length === 0 ? (
                            <Card>
                                <CardContent className="flex flex-col items-center justify-center py-12">
                                    <Inbox className="h-12 w-12 text-muted-foreground/50" />
                                    <p className="mt-4 text-muted-foreground">
                                        Vous n'avez pas reçu de demande de livraison
                                    </p>
                                </CardContent>
                            </Card>
                        ) : (
                            <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                                {received.map((delivery) => (
                                    <DeliveryCard key={delivery.id} delivery={delivery} />
                                ))}
                            </div>
                        )}
                    </TabsContent>
                </Tabs>
            </div>

            {/* Create Dialog */}
            <Dialog open={showCreateDialog} onOpenChange={setShowCreateDialog}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle>Nouvelle demande de livraison</DialogTitle>
                        <DialogDescription>
                            Créez une demande et partagez le lien avec le destinataire
                        </DialogDescription>
                    </DialogHeader>

                    <div className="space-y-4">
                        <div className="space-y-2">
                            <Label htmlFor="title">Titre *</Label>
                            <Input
                                id="title"
                                value={formData.title}
                                onChange={(e) =>
                                    setFormData({ ...formData, title: e.target.value })
                                }
                                placeholder="Ex: Colis pour Jean"
                            />
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="description">Description</Label>
                            <Textarea
                                id="description"
                                value={formData.description}
                                onChange={(e) =>
                                    setFormData({ ...formData, description: e.target.value })
                                }
                                placeholder="Description du colis..."
                                rows={2}
                            />
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="space-y-2">
                                <Label htmlFor="value">Valeur</Label>
                                <Input
                                    id="value"
                                    type="number"
                                    value={formData.value}
                                    onChange={(e) =>
                                        setFormData({ ...formData, value: e.target.value })
                                    }
                                    placeholder="0"
                                />
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="currency">Devise</Label>
                                <Select
                                    value={formData.currency}
                                    onValueChange={(v) =>
                                        setFormData({ ...formData, currency: v })
                                    }
                                >
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="XAF">XAF</SelectItem>
                                        <SelectItem value="EUR">EUR</SelectItem>
                                        <SelectItem value="USD">USD</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                        </div>

                        <div className="space-y-2">
                            <Label htmlFor="pickup">Adresse d'enlèvement *</Label>
                            <Select
                                value={formData.pickup_address_id}
                                onValueChange={(v) =>
                                    setFormData({ ...formData, pickup_address_id: v })
                                }
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Sélectionner une adresse" />
                                </SelectTrigger>
                                <SelectContent>
                                    {addresses.map((address) => (
                                        <SelectItem key={address.id} value={String(address.id)}>
                                            {address.swAddress}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            {addresses.length === 0 && (
                                <p className="text-xs text-muted-foreground">
                                    Créez d'abord une adresse via l'application mobile
                                </p>
                            )}
                        </div>
                    </div>

                    <DialogFooter>
                        <Button variant="outline" onClick={() => setShowCreateDialog(false)}>
                            Annuler
                        </Button>
                        <Button
                            onClick={handleCreate}
                            disabled={
                                isSubmitting || !formData.title || !formData.pickup_address_id
                            }
                        >
                            {isSubmitting ? 'Création...' : 'Créer'}
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
