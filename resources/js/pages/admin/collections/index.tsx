import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Search, FolderOpen, ChevronLeft, ChevronRight } from 'lucide-react';
import { useState } from 'react';

interface Collection {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    type: string;
    color: string | null;
    created_at: string;
    addresses_count: number;
    owner: {
        id: number;
        first_name: string;
        last_name: string;
        email: string;
    };
}

interface PaginatedCollections {
    data: Collection[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
}

interface Stats {
    total: number;
    system: number;
    custom: number;
    delivery: number;
}

interface Props {
    collections: PaginatedCollections;
    filters: {
        search?: string;
        type?: string;
    };
    stats: Stats;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'Collections', href: '/admin/collections' },
];

export default function CollectionsIndex({ collections, filters, stats }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [type, setType] = useState(filters.type || '');

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/admin/collections', { search, type }, { preserveState: true });
    };

    const handleTypeChange = (value: string) => {
        setType(value);
        router.get('/admin/collections', { search, type: value }, { preserveState: true });
    };

    const typeColors: Record<string, string> = {
        system: 'bg-blue-100 text-blue-800',
        custom: 'bg-green-100 text-green-800',
        delivery: 'bg-purple-100 text-purple-800',
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Collections Management" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                <div>
                    <h1 className="text-2xl font-bold">Collections</h1>
                    <p className="text-muted-foreground">Manage address collections</p>
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
                                <FolderOpen className="h-8 w-8 text-muted-foreground" />
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-muted-foreground">System</p>
                                    <p className="text-2xl font-bold">{stats.system}</p>
                                </div>
                                <Badge className={typeColors.system}>System</Badge>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-muted-foreground">Custom</p>
                                    <p className="text-2xl font-bold">{stats.custom}</p>
                                </div>
                                <Badge className={typeColors.custom}>Custom</Badge>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center justify-between">
                                <div>
                                    <p className="text-sm text-muted-foreground">Delivery</p>
                                    <p className="text-2xl font-bold">{stats.delivery}</p>
                                </div>
                                <Badge className={typeColors.delivery}>Delivery</Badge>
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
                                    placeholder="Search by name or slug..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                    className="pl-10"
                                />
                            </div>
                            <Select value={type} onValueChange={handleTypeChange}>
                                <SelectTrigger className="w-[180px]">
                                    <SelectValue placeholder="All types" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="">All types</SelectItem>
                                    <SelectItem value="system">System</SelectItem>
                                    <SelectItem value="custom">Custom</SelectItem>
                                    <SelectItem value="delivery">Delivery</SelectItem>
                                </SelectContent>
                            </Select>
                            <Button type="submit">Search</Button>
                        </form>
                    </CardContent>
                </Card>

                {/* Collections Table */}
                <Card>
                    <CardHeader>
                        <CardTitle>Collections</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div className="overflow-x-auto">
                            <table className="w-full">
                                <thead>
                                    <tr className="border-b">
                                        <th className="px-4 py-3 text-left text-sm font-medium">Name</th>
                                        <th className="px-4 py-3 text-left text-sm font-medium">Type</th>
                                        <th className="px-4 py-3 text-left text-sm font-medium">Owner</th>
                                        <th className="px-4 py-3 text-left text-sm font-medium">Addresses</th>
                                        <th className="px-4 py-3 text-left text-sm font-medium">Created</th>
                                        <th className="px-4 py-3 text-left text-sm font-medium">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {collections.data.map((collection) => (
                                        <tr key={collection.id} className="border-b">
                                            <td className="px-4 py-3">
                                                <div className="flex items-center gap-2">
                                                    {collection.color && (
                                                        <div
                                                            className="h-4 w-4 rounded"
                                                            style={{ backgroundColor: collection.color }}
                                                        />
                                                    )}
                                                    <div>
                                                        <p className="font-medium">{collection.name}</p>
                                                        <p className="text-xs text-muted-foreground">{collection.slug}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3">
                                                <Badge className={typeColors[collection.type]}>{collection.type}</Badge>
                                            </td>
                                            <td className="px-4 py-3">
                                                <div>
                                                    <p className="text-sm">
                                                        {collection.owner.first_name} {collection.owner.last_name}
                                                    </p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {collection.owner.email}
                                                    </p>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3 text-sm">{collection.addresses_count}</td>
                                            <td className="px-4 py-3 text-sm">
                                                {new Date(collection.created_at).toLocaleDateString()}
                                            </td>
                                            <td className="px-4 py-3">
                                                <Link
                                                    href={`/admin/collections/${collection.id}`}
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
                        {collections.last_page > 1 && (
                            <div className="mt-4 flex items-center justify-between">
                                <p className="text-sm text-muted-foreground">
                                    Page {collections.current_page} of {collections.last_page}
                                </p>
                                <div className="flex gap-2">
                                    {collections.current_page > 1 && (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                router.get('/admin/collections', {
                                                    ...filters,
                                                    page: collections.current_page - 1,
                                                })
                                            }
                                        >
                                            <ChevronLeft className="h-4 w-4" />
                                        </Button>
                                    )}
                                    {collections.current_page < collections.last_page && (
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() =>
                                                router.get('/admin/collections', {
                                                    ...filters,
                                                    page: collections.current_page + 1,
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
