import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Search, MapPin, ChevronLeft, ChevronRight, CheckCircle, XCircle, Clock } from 'lucide-react';
import { useState } from 'react';

interface Address {
    id: number;
    sw_address: string;
    display_name: string;
    quarter: string;
    sub_quarter: string | null;
    house_type: string;
    verification_status: string;
    created_at: string;
    user: {
        id: number;
        first_name: string;
        last_name: string;
        email: string;
    };
}

interface PaginatedAddresses {
    data: Address[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Stats {
    total: number;
    pending: number;
    approved: number;
    rejected: number;
}

interface Props {
    addresses: PaginatedAddresses;
    filters: {
        search?: string;
        status?: string;
        house_type?: string;
    };
    stats: Stats;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Addresses', href: '/admin/addresses' },
];

export default function AddressesIndex({ addresses, filters, stats }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/addresses', { search, status }, { preserveState: true });
    };

    const handleStatusChange = (value: string) => {
        setStatus(value);
        router.get('/admin/addresses', { search, status: value }, { preserveState: true });
    };

    const statusColors: Record<string, string> = {
        pending: 'bg-yellow-100 text-yellow-800',
        approved: 'bg-green-100 text-green-800',
        rejected: 'bg-red-100 text-red-800',
    };

    const statusIcons: Record<string, React.ReactNode> = {
        pending: <Clock className="h-4 w-4" />,
        approved: <CheckCircle className="h-4 w-4" />,
        rejected: <XCircle className="h-4 w-4" />,
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Addresses Management" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold">Addresses</h1>
                    <p className="text-muted-foreground">Manage and verify addresses</p>
                </div>

                {/* Stats */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-muted-foreground">Total</p>
                                    <p className="text-2xl font-bold">{stats.total}</p>
                                </div>
                                <MapPin className="h-8 w-8 text-muted-foreground" />
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-yellow-200 bg-yellow-50">
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-yellow-700">Pending</p>
                                    <p className="text-2xl font-bold text-yellow-700">{stats.pending}</p>
                                </div>
                                <Clock className="h-8 w-8 text-yellow-500" />
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-green-200 bg-green-50">
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-green-700">Approved</p>
                                    <p className="text-2xl font-bold text-green-700">{stats.approved}</p>
                                </div>
                                <CheckCircle className="h-8 w-8 text-green-500" />
                            </div>
                        </CardContent>
                    </Card>
                    <Card className="border-red-200 bg-red-50">
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-red-700">Rejected</p>
                                    <p className="text-2xl font-bold text-red-700">{stats.rejected}</p>
                                </div>
                                <XCircle className="h-8 w-8 text-red-500" />
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Filters */}
                <Card>
                    <CardContent className="pt-6">
                        <form onSubmit={handleSearch} className="flex gap-4">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    placeholder="Search by SW address, name or quarter..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                            <Select value={status} onValueChange={handleStatusChange}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="All statuses" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">All statuses</SelectItem>
                                    <SelectItem value="pending">Pending</SelectItem>
                                    <SelectItem value="approved">Approved</SelectItem>
                                    <SelectItem value="rejected">Rejected</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button type="submit">Search</Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Addresses Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Addresses</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b">
                                        <th className="px-4 py-3 text-left text-sm font-medium">SW Address</th>
                                        <th className="px-4 py-3 text-left text-sm font-medium">Location</th>
                                        <th className="px-4 py-3 text-left text-sm font-medium">Type</th>
                                        <th className="px-4 py-3 text-left text-sm font-medium">Owner</th>
                                        <th className="px-4 py-3 text-left text-sm font-medium">Status</th>
                                        <th className="px-4 py-3 text-left text-sm font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {addresses.data.map((address) => (
                                        <tr key={address.id} className="border-b">
                                            <td className="px-4 py-3">
                                                <span className="font-mono font-medium">{address.sw_address}</span>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div>
                                                    <p className="font-medium">{address.quarter}</p>
                                                    <p className="text-sm text-muted-foreground">
                                                        {address.sub_quarter || address.display_name}
                                                    </p>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-sm">{address.house_type}</td>
                                            <td className="px-4 py-3">
                                                <div>
                                                    <p className="text-sm">
                                                        {address.user.first_name} {address.user.last_name}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">{address.user.email}</p>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge className={statusColors[address.verification_status]}>
                                                    <span className="mr-1">{statusIcons[address.verification_status]}</span>
                                                    {address.verification_status}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={`/admin/addresses/${address.id}`}
                                                    className="text-sm text-primary hover:underline"
                                                >
                                                    {address.verification_status === 'pending' ? 'Review' : 'View'}
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {addresses.last_page > 1 && (
                            <div className="mt-4 flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Page {addresses.current_page} of {addresses.last_page}
                                </p>
                                <div className="flex gap-2">
                                    {addresses.current_page > 1 && (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                router.get('/admin/addresses', {
                                                    ...filters,
                                                    page: addresses.current_page - 1,
                                                })
                                            }
                                        >
                                            <ChevronLeft className="h-4 w-4" />
                                        </Button>
                                    )}
                                    {addresses.current_page < addresses.last_page && (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                router.get('/admin/addresses', {
                                                    ...filters,
                                                    page: addresses.current_page + 1,
                                                })
                                            }
                                        >
                                            <ChevronRight className="h-4 w-4" />
                                        </Button>
                                    )}
                                </div>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
