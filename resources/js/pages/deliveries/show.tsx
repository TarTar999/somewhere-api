import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import {
    Truck,
    ArrowLeft,
    MapPin,
    Package,
    User,
    Clock,
    CheckCircle,
    Copy,
    Check,
    ExternalLink,
    Phone,
    XCircle,
} from 'lucide-react';
import { useState } from 'react';

interface Address {
    id: number;
    swAddress: string;
    displayName: string;
    latitude: number;
    longitude: number;
}

interface UserInfo {
    id: number;
    name: string;
    phone: string;
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
    initiator: UserInfo | null;
    recipient: UserInfo | null;
    pickupAddress: Address | null;
    deliveryAddress: Address | null;
    deliveryCoordinates: { latitude: number; longitude: number } | null;
    createdAt: string;
    acceptedAt: string | null;
    completedAt: string | null;
}

interface Props {
    delivery: DeliveryRequest;
    isInitiator: boolean;
}

function formatDate(dateString: string) {
    return new Date(dateString).toLocaleDateString('fr-FR', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function getStatusInfo(status: string) {
    switch (status) {
        case 'pending':
            return {
                label: 'En attente',
                color: 'bg-yellow-100 text-yellow-800',
                description: 'En attente d\'acceptation par le destinataire',
            };
        case 'accepted':
            return {
                label: 'Acceptée',
                color: 'bg-blue-100 text-blue-800',
                description: 'Demande acceptée, prête à être expédiée',
            };
        case 'in_progress':
            return {
                label: 'En cours',
                color: 'bg-purple-100 text-purple-800',
                description: 'Livraison en cours',
            };
        case 'completed':
            return {
                label: 'Terminée',
                color: 'bg-green-100 text-green-800',
                description: 'Livraison effectuée',
            };
        case 'cancelled':
            return {
                label: 'Annulée',
                color: 'bg-red-100 text-red-800',
                description: 'Demande annulée',
            };
        default:
            return {
                label: status,
                color: 'bg-gray-100 text-gray-800',
                description: '',
            };
    }
}

export default function DeliveryShow({ delivery, isInitiator }: Props) {
    const [copied, setCopied] = useState(false);
    const [isProcessing, setIsProcessing] = useState(false);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tableau de bord', href: '/dashboard' },
        { title: 'Livraisons', href: '/deliveries' },
        { title: delivery.title, href: `/deliveries/${delivery.id}` },
    ];

    const statusInfo = getStatusInfo(delivery.status);

    const copyShareLink = () => {
        navigator.clipboard.writeText(delivery.shareUrl);
        setCopied(true);
        setTimeout(() => setCopied(false), 2000);
    };

    const handleAction = async (action: string) => {
        setIsProcessing(true);
        try {
            let endpoint = `/api/v1/delivery-requests/${delivery.id}`;
            let method = 'PUT';

            switch (action) {
                case 'start':
                    endpoint += '/status';
                    await fetch(endpoint, {
                        method: 'PATCH',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                        },
                        body: JSON.stringify({ status: 'in_progress' }),
                    });
                    break;
                case 'confirm':
                    endpoint += '/confirm';
                    await fetch(endpoint, {
                        method: 'POST',
                        headers: { Accept: 'application/json' },
                    });
                    break;
                case 'cancel':
                    await fetch(endpoint, {
                        method: 'DELETE',
                        headers: { Accept: 'application/json' },
                    });
                    break;
            }

            router.reload();
        } catch {
            // Handle error
        } finally {
            setIsProcessing(false);
        }
    };

    const canStart = delivery.status === 'accepted' && isInitiator;
    const canConfirm =
        delivery.status === 'in_progress' &&
        ((isInitiator && !delivery.initiatorConfirmed) ||
            (!isInitiator && !delivery.recipientConfirmed));
    const canCancel = ['pending', 'accepted'].includes(delivery.status);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={delivery.title} />
            <div className="flex h-full flex-1 flex-col gap-6 p-4">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div className="flex items-center gap-4">
                        <Button variant="ghost" size="icon" onClick={() => history.back()}>
                            <ArrowLeft className="h-4 w-4" />
                        </Button>
                        <div>
                            <h1 className="text-2xl font-bold flex items-center gap-2">
                                <Truck className="h-6 w-6" />
                                {delivery.title}
                            </h1>
                            <p className="text-muted-foreground">
                                {delivery.role === 'sent' ? 'Envoyée' : 'Reçue'} le{' '}
                                {formatDate(delivery.createdAt)}
                            </p>
                        </div>
                    </div>
                    <Badge className={statusInfo.color}>{statusInfo.label}</Badge>
                </div>

                <div className="grid gap-6 lg:grid-cols-3">
                    {/* Main Info */}
                    <div className="lg:col-span-2 space-y-6">
                        {/* Description */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Détails</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {delivery.description && (
                                    <div>
                                        <p className="text-sm text-muted-foreground">
                                            Description
                                        </p>
                                        <p>{delivery.description}</p>
                                    </div>
                                )}
                                {delivery.value && (
                                    <div>
                                        <p className="text-sm text-muted-foreground">Valeur</p>
                                        <p className="font-medium">
                                            {delivery.value.toLocaleString()} {delivery.currency}
                                        </p>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Addresses */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Adresses</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {delivery.pickupAddress && (
                                    <div className="flex items-start gap-3">
                                        <Package className="h-5 w-5 text-muted-foreground mt-0.5" />
                                        <div className="flex-1">
                                            <p className="text-sm text-muted-foreground">
                                                Point d'enlèvement
                                            </p>
                                            <p className="font-medium">
                                                {delivery.pickupAddress.swAddress}
                                            </p>
                                            <Button
                                                variant="link"
                                                size="sm"
                                                className="p-0 h-auto"
                                                onClick={() =>
                                                    window.open(
                                                        `https://maps.google.com/?q=${delivery.pickupAddress!.latitude},${delivery.pickupAddress!.longitude}`,
                                                        '_blank'
                                                    )
                                                }
                                            >
                                                <ExternalLink className="mr-1 h-3 w-3" />
                                                Voir sur la carte
                                            </Button>
                                        </div>
                                    </div>
                                )}
                                {(delivery.deliveryAddress || delivery.deliveryCoordinates) && (
                                    <div className="flex items-start gap-3">
                                        <MapPin className="h-5 w-5 text-muted-foreground mt-0.5" />
                                        <div className="flex-1">
                                            <p className="text-sm text-muted-foreground">
                                                Point de livraison
                                            </p>
                                            {delivery.deliveryAddress ? (
                                                <>
                                                    <p className="font-medium">
                                                        {delivery.deliveryAddress.swAddress}
                                                    </p>
                                                    <Button
                                                        variant="link"
                                                        size="sm"
                                                        className="p-0 h-auto"
                                                        onClick={() =>
                                                            window.open(
                                                                `https://maps.google.com/?q=${delivery.deliveryAddress!.latitude},${delivery.deliveryAddress!.longitude}`,
                                                                '_blank'
                                                            )
                                                        }
                                                    >
                                                        <ExternalLink className="mr-1 h-3 w-3" />
                                                        Voir sur la carte
                                                    </Button>
                                                </>
                                            ) : (
                                                <Button
                                                    variant="link"
                                                    size="sm"
                                                    className="p-0 h-auto"
                                                    onClick={() =>
                                                        window.open(
                                                            `https://maps.google.com/?q=${delivery.deliveryCoordinates!.latitude},${delivery.deliveryCoordinates!.longitude}`,
                                                            '_blank'
                                                        )
                                                    }
                                                >
                                                    <ExternalLink className="mr-1 h-3 w-3" />
                                                    Voir sur la carte
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Participants */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Participants</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-4">
                                {delivery.initiator && (
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <User className="h-5 w-5 text-muted-foreground" />
                                            <div>
                                                <p className="font-medium">
                                                    {delivery.initiator.name}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    Expéditeur
                                                </p>
                                            </div>
                                        </div>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() =>
                                                window.open(`tel:${delivery.initiator!.phone}`)
                                            }
                                        >
                                            <Phone className="h-4 w-4" />
                                        </Button>
                                    </div>
                                )}
                                {delivery.recipient && (
                                    <div className="flex items-center justify-between">
                                        <div className="flex items-center gap-3">
                                            <User className="h-5 w-5 text-muted-foreground" />
                                            <div>
                                                <p className="font-medium">
                                                    {delivery.recipient.name}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    Destinataire
                                                </p>
                                            </div>
                                        </div>
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            onClick={() =>
                                                window.open(`tel:${delivery.recipient!.phone}`)
                                            }
                                        >
                                            <Phone className="h-4 w-4" />
                                        </Button>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Sidebar */}
                    <div className="space-y-6">
                        {/* Status */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Statut</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <Badge className={`${statusInfo.color} mb-2`}>
                                    {statusInfo.label}
                                </Badge>
                                <p className="text-sm text-muted-foreground">
                                    {statusInfo.description}
                                </p>

                                {delivery.status === 'in_progress' && (
                                    <div className="mt-4 space-y-2">
                                        <div className="flex items-center gap-2">
                                            {delivery.initiatorConfirmed ? (
                                                <CheckCircle className="h-4 w-4 text-green-500" />
                                            ) : (
                                                <Clock className="h-4 w-4 text-yellow-500" />
                                            )}
                                            <span className="text-sm">
                                                Confirmation expéditeur
                                            </span>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            {delivery.recipientConfirmed ? (
                                                <CheckCircle className="h-4 w-4 text-green-500" />
                                            ) : (
                                                <Clock className="h-4 w-4 text-yellow-500" />
                                            )}
                                            <span className="text-sm">
                                                Confirmation destinataire
                                            </span>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {/* Actions */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Actions</CardTitle>
                            </CardHeader>
                            <CardContent className="space-y-3">
                                {delivery.status === 'pending' && isInitiator && (
                                    <Button
                                        variant="outline"
                                        className="w-full"
                                        onClick={copyShareLink}
                                    >
                                        {copied ? (
                                            <>
                                                <Check className="mr-2 h-4 w-4" />
                                                Copié!
                                            </>
                                        ) : (
                                            <>
                                                <Copy className="mr-2 h-4 w-4" />
                                                Copier le lien
                                            </>
                                        )}
                                    </Button>
                                )}

                                {canStart && (
                                    <Button
                                        className="w-full"
                                        onClick={() => handleAction('start')}
                                        disabled={isProcessing}
                                    >
                                        <Truck className="mr-2 h-4 w-4" />
                                        Démarrer la livraison
                                    </Button>
                                )}

                                {canConfirm && (
                                    <Button
                                        className="w-full"
                                        onClick={() => handleAction('confirm')}
                                        disabled={isProcessing}
                                    >
                                        <CheckCircle className="mr-2 h-4 w-4" />
                                        Confirmer la réception
                                    </Button>
                                )}

                                {canCancel && (
                                    <Button
                                        variant="destructive"
                                        className="w-full"
                                        onClick={() => handleAction('cancel')}
                                        disabled={isProcessing}
                                    >
                                        <XCircle className="mr-2 h-4 w-4" />
                                        Annuler
                                    </Button>
                                )}
                            </CardContent>
                        </Card>

                        {/* Timeline */}
                        <Card>
                            <CardHeader>
                                <CardTitle>Historique</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    <div className="flex items-center gap-3">
                                        <div className="h-2 w-2 rounded-full bg-primary" />
                                        <div>
                                            <p className="text-sm font-medium">Créée</p>
                                            <p className="text-xs text-muted-foreground">
                                                {formatDate(delivery.createdAt)}
                                            </p>
                                        </div>
                                    </div>
                                    {delivery.acceptedAt && (
                                        <div className="flex items-center gap-3">
                                            <div className="h-2 w-2 rounded-full bg-blue-500" />
                                            <div>
                                                <p className="text-sm font-medium">Acceptée</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {formatDate(delivery.acceptedAt)}
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                    {delivery.completedAt && (
                                        <div className="flex items-center gap-3">
                                            <div className="h-2 w-2 rounded-full bg-green-500" />
                                            <div>
                                                <p className="text-sm font-medium">Terminée</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {formatDate(delivery.completedAt)}
                                                </p>
                                            </div>
                                        </div>
                                    )}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
