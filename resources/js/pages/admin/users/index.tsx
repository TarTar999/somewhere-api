import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Search, Users, ChevronLeft, ChevronRight } from 'lucide-react';
import { useState } from 'react';

interface User {
    id: number;
    first_name: string;
    last_name: string;
    email: string;
    phone: string | null;
    is_admin: boolean;
    created_at: string;
    addresses_count: number;
    collections_count: number;
}

interface PaginatedUsers {
    data: User[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: Array<{ url: string | null; label: string; active: boolean }>;
}

interface Props {
    users: PaginatedUsers;
    filters: {
        search?: string;
        is_admin?: string;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Users', href: '/admin/users' },
];

export default function UsersIndex({ users, filters }: Props) {
    const [search, setSearch] = useState(filters.search || '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/users', { search }, { preserveState: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users Management" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Users</h1>
                        <p className="text-muted-foreground">Manage user accounts</p>
                    </div>
                </div>

                {/* Search */}
                <Card>
                    <CardContent className="pt-6">
                        <form onSubmit={handleSearch} className="flex gap-4">
                            <div className="relative flex-1">
                                <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input
                                    placeholder="Search by name, email or phone..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                            <Button type="submit">Search</Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Users Table */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Users className="h-5 w-5" />
                            {users.total} Users
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b">
                                        <th className="px-4 py-3 text-left text-sm font-medium">Name</th>
                                        <th className="px-4 py-3 text-left text-sm font-medium">Email</th>
                                        <th className="px-4 py-3 text-left text-sm font-medium">Phone</th>
                                        <th className="px-4 py-3 text-left text-sm font-medium">Role</th>
                                        <th className="px-4 py-3 text-left text-sm font-medium">Addresses</th>
                                        <th className="px-4 py-3 text-left text-sm font-medium">Collections</th>
                                        <th className="px-4 py-3 text-left text-sm font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {users.data.map((user) => (
                                        <tr key={user.id} className="border-b">
                                            <td className="px-4 py-3">
                                                <span className="font-medium">
                                                    {user.first_name} {user.last_name}
                                                </span>
                                            </td>
                                            <td className="px-4 py-3 text-sm">{user.email}</td>
                                            <td className="px-4 py-3 text-sm">{user.phone || '-'}</td>
                                            <td className="px-4 py-3">
                                                {user.is_admin ? (
                                                    <Badge variant="default">Admin</Badge>
                                                ) : (
                                                    <Badge variant="secondary">User</Badge>
                                                )}
                                            </td>
                                            <td className="px-4 py-3 text-sm">{user.addresses_count}</td>
                                            <td className="px-4 py-3 text-sm">{user.collections_count}</td>
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={`/admin/users/${user.id}`}
                                                    className="text-sm text-primary hover:underline"
                                                >
                                                    View
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {/* Pagination */}
                        {users.last_page > 1 && (
                            <div className="mt-4 flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Showing {(users.current_page - 1) * users.per_page + 1} to{' '}
                                    {Math.min(users.current_page * users.per_page, users.total)} of {users.total}
                                </p>
                                <div className="flex gap-2">
                                    {users.current_page > 1 && (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                router.get('/admin/users', {
                                                    ...filters,
                                                    page: users.current_page - 1,
                                                })
                                            }
                                        >
                                            <ChevronLeft className="h-4 w-4" />
                                        </Button>
                                    )}
                                    {users.current_page < users.last_page && (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                router.get('/admin/users', {
                                                    ...filters,
                                                    page: users.current_page + 1,
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
