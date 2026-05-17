import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, MapPin, CheckCircle, XCircle, Trash2, User, Video } from 'lucide-react';
import { useState } from 'react';

interface Collection {
    id: number;
    name: string;
    slug: string;
}

interface AddressUser {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone: string | null;
}

interface Address {
    id: number;
    sw_address: string;
    display_name: string;
    latitude: number;
    longitude: number;
    accuracy: number | null;
    house_type: string;
    home_status: string;
    quarter: string;
    sub_quarter: string | null;
    lieu_dit: string | null;
    description: string | null;
    honor_declaration: boolean;
    verification_status: string;
    video_path: string | null;
    created_at: string;
    updated_at: string;
    user: AddressUser;
    collections: Collection[];
}

interface Props {
    address: Address;
}

export default function AddressShow({ address }: Props) {
    const [rejectionReason, setRejectionReason] = useState('');

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Admin', href: '/admin' },
        { title: 'Addresses', href: '/admin/addresses' },
        { title: address.sw_address, href: `/admin/addresses/${address.id}` },
    ];

    const handleVerify = () => {
        if (confirm('Are you sure you want to verify this address?')) {
            router.post(`/admin/addresses/${address.id}/verify`);
        }
    };

    const handleReject = () => {
        if (confirm('Are you sure you want to reject this address?')) {
            router.post(`/admin/addresses/${address.id}/reject`, { reason: rejectionReason });
        }
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this address? This action cannot be undone.')) {
            router.delete(`/admin/addresses/${address.id}`);
        }
    };

    const statusColors: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={address.sw_address} />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href="/admin/addresses">
                            <Button variant="ghost" size="icon">
                                <ArrowLeft className="h-4 w-4" />
                            </Button>
                        </Link>
                        <div>
                            <h1 className="font-mono text-2xl font-bold">{address.sw_address}</h1>
                            <p className="text-muted-foreground">{address.display_name}</p>
                        </div>
                        <Badge className={statusColors[address.verification_status]}>
                            {address.verification_status}
                        </Badge>
                    </div>
                    <div className="flex gap-2">
                        {address.verification_status === 'pending' && (
                            <>
                                <Button variant="default" onClick={handleVerify}>
                                    <CheckCircle className="mr-2 h-4 w-4" />
                                    Verify
                                </Button>
                                <Button variant="destructive" onClick={handleReject}>
                                    <XCircle className="mr-2 h-4 w-4" />
                                    Reject
                                </Button>
                            </>
                        )}
                        <Button variant="outline" onClick={handleDelete}>
                            <Trash2 className="mr-2 h-4 w-4" />
                            Delete
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    {/* Location Info */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <MapPin className="h-5 w-5" />
                                Location Details
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm text-muted-foreground">Quarter</p>
                                    <p className="font-medium">{address.quarter}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Sub-quarter</p>
                                    <p className="font-medium">{address.sub_quarter || '-'}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Lieu-dit</p>
                                    <p className="font-medium">{address.lieu_dit || '-'}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">House Type</p>
                                    <p className="font-medium">{address.house_type}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Home Status</p>
                                    <p className="font-medium">{address.home_status}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">GPS Accuracy</p>
                                    <p className="font-medium">{address.accuracy ? `${address.accuracy}m` : '-'}</p>
                                </div>
                            </div>
                            <div>
                                <p className="text-sm text-muted-foreground">Coordinates</p>
                                <p className="font-mono text-sm">
                                    {address.latitude.toFixed(6)}, {address.longitude.toFixed(6)}
                                </p>
                            </div>
                            {address.description && (
                                <div>
                                    <p className="text-sm text-muted-foreground">Description</p>
                                    <p className="font-medium">{address.description}</p>
                                </div>
                            )}
                        </CardContent>
                    </Card>

                    {/* Owner Info */}
                    <Card>
                        <CardHeader>
                            <CardTitle className="flex items-center gap-2">
                                <User className="h-5 w-5" />
                                Owner
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm text-muted-foreground">Name</p>
                                    <p className="font-medium">
                                        {address.user.first_name} {address.user.last_name}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Email</p>
                                    <p className="font-medium">{address.user.email}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Phone</p>
                                    <p className="font-medium">{address.user.phone || '-'}</p>
                                </div>
                            </div>
                            <div>
                                <Link href={`/admin/users/${address.user.id}`}>
                                    <Button variant="outline" size="sm">
                                        View User Profile
                                    </Button>
                                </Link>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Verification Info */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Verification</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm text-muted-foreground">Honor Declaration</p>
                                    <p className="font-medium">
                                        {address.honor_declaration ? 'Yes' : 'No'}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Video Proof</p>
                                    <p className="font-medium">
                                        {address.video_path ? (
                                            <Badge variant="secondary">
                                                <Video className="mr-1 h-3 w-3" />
                                                Available
                                            </Badge>
                                        ) : (
                                            'Not provided'
                                        )}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Created</p>
                                    <p className="font-medium">
                                        {new Date(address.created_at).toLocaleString()}
                                    </p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Updated</p>
                                    <p className="font-medium">
                                        {new Date(address.updated_at).toLocaleString()}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Collections */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Collections ({address.collections.length})</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {address.collections.length > 0 ? (
                                <div className="space-y-2">
                                    {address.collections.map((collection) => (
                                        <div
                                            key={collection.id}
                                            className="flex items-center justify-between rounded-lg border p-2"
                                        >
                                            <span>{collection.name}</span>
                                            <Link href={`/admin/collections/${collection.id}`}>
                                                <Button variant="ghost" size="sm">
                                                    View
                                                </Button>
                                            </Link>
                                        </div>
                                    ))}
                                </div>
                            ) : (
                                <p className="text-muted-foreground">Not in any collection</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Map placeholder */}
                <Card>
                    <CardHeader>
                        <CardTitle>Location on Map</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="flex h-64 items-center justify-center rounded-lg bg-muted">
                            <div className="text-center">
                                <MapPin className="mx-auto h-12 w-12 text-muted-foreground" />
                                <p className="mt-2 text-muted-foreground">
                                    Map view - Coordinates: {address.latitude.toFixed(6)}, {address.longitude.toFixed(6)}
                                </p>
                                <a
                                    href={`https://www.google.com/maps?q=${address.latitude},${address.longitude}`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="mt-2 inline-block text-primary hover:underline"
                                >
                                    Open in Google Maps
                                </a>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
