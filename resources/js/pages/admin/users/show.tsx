import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeft, MapPin, FolderOpen, Shield, Trash2 } from 'lucide-react';

interface Address {
    id: number;
    sw_address: string;
    display_name: string;
    quarter: string;
    verification_status: string;
}

interface Collection {
    id: number;
    name: string;
    slug: string;
    type: string;
    addresses: Address[];
}

interface Settings {
    language: string;
    unit: string;
    notifications: string;
    map_type: string;
}

interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone: string | null;
    sex: string | null;
    nui_number: string | null;
    cni_number: string | null;
    cni_expiration_date: string | null;
    is_admin: boolean;
    created_at: string;
    settings: Settings | null;
    addresses: Address[];
    collections: Collection[];
}

interface Props {
    user: User;
}

export default function UserShow({ user }: Props) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Admin', href: '/admin' },
        { title: 'Users', href: '/admin/users' },
        { title: `${user.first_name} ${user.last_name}`, href: `/admin/users/${user.id}` },
    ];

    const handleToggleAdmin = () => {
        if (confirm(`Are you sure you want to ${user.is_admin ? 'revoke' : 'grant'} admin privileges?`)) {
            router.post(`/admin/users/${user.id}/toggle-admin`);
        }
    };

    const handleDelete = () => {
        if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
            router.delete(`/admin/users/${user.id}`);
        }
    };

    const statusColors: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${user.first_name} ${user.last_name}`} />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-4">
                        <Link href="/admin/users">
                            <Button variant="ghost" size="icon">
                                <ArrowLeft className="h-4 w-4" />
                            </Button>
                        </Link>
                        <div>
                            <h1 className="text-2xl font-bold">
                                {user.first_name} {user.last_name}
                            </h1>
                            <p className="text-muted-foreground">{user.email}</p>
                        </div>
                        {user.is_admin && <Badge>Admin</Badge>}
                    </div>
                    <div className="flex gap-2">
                        <Button variant="outline" onClick={handleToggleAdmin}>
                            <Shield className="mr-2 h-4 w-4" />
                            {user.is_admin ? 'Revoke Admin' : 'Make Admin'}
                        </Button>
                        <Link href={`/admin/users/${user.id}/edit`}>
                            <Button variant="outline">Edit</Button>
                        </Link>
                        <Button variant="destructive" onClick={handleDelete}>
                            <Trash2 className="mr-2 h-4 w-4" />
                            Delete
                        </Button>
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    {/* User Info */}
                    <Card>
                        <CardHeader>
                            <CardTitle>User Information</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <p className="text-sm text-muted-foreground">Phone</p>
                                    <p className="font-medium">{user.phone || '-'}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Sex</p>
                                    <p className="font-medium">{user.sex || '-'}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">CNI Number</p>
                                    <p className="font-medium">{user.cni_number || '-'}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">NUI Number</p>
                                    <p className="font-medium">{user.nui_number || '-'}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">CNI Expiration</p>
                                    <p className="font-medium">{user.cni_expiration_date || '-'}</p>
                                </div>
                                <div>
                                    <p className="text-sm text-muted-foreground">Registered</p>
                                    <p className="font-medium">
                                        {new Date(user.created_at).toLocaleDateString()}
                                    </p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    {/* Settings */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Settings</CardTitle>
                        </CardHeader>
                        <CardContent className="space-y-4">
                            {user.settings ? (
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <p className="text-sm text-muted-foreground">Language</p>
                                        <p className="font-medium">{user.settings.language}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-muted-foreground">Unit</p>
                                        <p className="font-medium">{user.settings.unit}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-muted-foreground">Notifications</p>
                                        <p className="font-medium">{user.settings.notifications}</p>
                                    </div>
                                    <div>
                                        <p className="text-sm text-muted-foreground">Map Type</p>
                                        <p className="font-medium">{user.settings.map_type}</p>
                                    </div>
                                </div>
                            ) : (
                                <p className="text-muted-foreground">No settings configured</p>
                            )}
                        </CardContent>
                    </Card>
                </div>

                {/* Addresses */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <MapPin className="h-5 w-5" />
                            Addresses ({user.addresses.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {user.addresses.length > 0 ? (
                            <div className="space-y-3">
                                {user.addresses.map((address) => (
                                    <div
                                        key={address.id}
                                        className="flex items-center justify-between rounded-lg border p-3"
                                    >
                                        <div>
                                            <p className="font-medium">{address.sw_address}</p>
                                            <p className="text-sm text-muted-foreground">
                                                {address.display_name} - {address.quarter}
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Badge className={statusColors[address.verification_status]}>
                                                {address.verification_status}
                                            </Badge>
                                            <Link href={`/admin/addresses/${address.id}`}>
                                                <Button variant="ghost" size="sm">
                                                    View
                                                </Button>
                                            </Link>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-muted-foreground">No addresses</p>
                        )}
                    </CardContent>
                </Card>

                {/* Collections */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <FolderOpen className="h-5 w-5" />
                            Collections ({user.collections.length})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        {user.collections.length > 0 ? (
                            <div className="space-y-3">
                                {user.collections.map((collection) => (
                                    <div
                                        key={collection.id}
                                        className="flex items-center justify-between rounded-lg border p-3"
                                    >
                                        <div>
                                            <p className="font-medium">{collection.name}</p>
                                            <p className="text-sm text-muted-foreground">
                                                {collection.addresses.length} addresses
                                            </p>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Badge variant="secondary">{collection.type}</Badge>
                                            <Link href={`/admin/collections/${collection.id}`}>
                                                <Button variant="ghost" size="sm">
                                                    View
                                                </Button>
                                            </Link>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-muted-foreground">No collections</p>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
