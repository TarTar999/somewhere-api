import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { Users, MapPin, FolderOpen, Clock, CheckCircle, XCircle } from 'lucide-react';

interface Stats {
    totalUsers: number;
    newUsersThisWeek: number;
    totalAddresses: number;
    pendingAddresses: number;
    approvedAddresses: number;
    totalCollections: number;
}

interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    created_at: string;
}

interface Address {
    id: number;
    sw_address: string;
    display_name: string;
    quarter: string;
    verification_status: string;
    created_at: string;
    user: {
        id: number;
        first_name: string;
        last_name: string;
        email: string;
    };
}

interface Props {
    stats: Stats;
    recentUsers: User[];
    pendingVerifications: Address[];
    addressesByStatus: Record<string, number>;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Dashboard', href: '/admin' },
];

export default function AdminDashboard({ stats, recentUsers, pendingVerifications }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Admin Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold">Admin Dashboard</h1>
                    <p className="text-muted-foreground">Overview of your Somewhere application</p>
                </div>

                {/* Stats Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Users</CardTitle>
                            <Users className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.totalUsers}</div>
                            <p className="text-xs text-muted-foreground">
                                +{stats.newUsersThisWeek} this week
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Addresses</CardTitle>
                            <MapPin className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.totalAddresses}</div>
                            <p className="text-xs text-muted-foreground">
                                {stats.approvedAddresses} verified
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Pending Verification</CardTitle>
                            <Clock className="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{stats.pendingAddresses}</div>
                            <p className="text-xs text-muted-foreground">
                                Addresses awaiting review
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
                                Total collections created
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    {/* Recent Users */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Recent Users</CardTitle>
                            <CardDescription>Latest registered users</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {recentUsers.map((user) => (
                                    <div key={user.id} className="flex items-center justify-between">
                                        <div>
                                            <p className="font-medium">
                                                {user.first_name} {user.last_name}
                                            </p>
                                            <p className="text-sm text-muted-foreground">{user.email}</p>
                                        </div>
                                        <Link
                                            href={`/admin/users/${user.id}`}
                                            className="text-sm text-primary hover:underline"
                                        >
                                            View
                                        </Link>
                                    </div>
                                ))}
                                {recentUsers.length === 0 && (
                                    <p className="text-muted-foreground">No users yet</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Pending Verifications */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Pending Verifications</CardTitle>
                            <CardDescription>Addresses awaiting verification</CardDescription>
                        </CardHeader>
                        <CardContent>
                            <div className="space-y-4">
                                {pendingVerifications.map((address) => (
                                    <div key={address.id} className="flex items-center justify-between">
                                        <div>
                                            <p className="font-medium">{address.sw_address}</p>
                                            <p className="text-sm text-muted-foreground">
                                                {address.quarter} - {address.user?.first_name} {address.user?.last_name}
                                            </p>
                                        </div>
                                        <Link
                                            href={`/admin/addresses/${address.id}`}
                                            className="text-sm text-primary hover:underline"
                                        >
                                            Review
                                        </Link>
                                    </div>
                                ))}
                                {pendingVerifications.length === 0 && (
                                    <p className="text-muted-foreground">No pending verifications</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
