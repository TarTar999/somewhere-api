import { useState } from 'react';
import CompanyLayout from '@/layouts/company-layout';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Plus, Search, Edit2, Trash2, Tags, MapPin, Shapes } from 'lucide-react';
import { useForm, router } from '@inertiajs/react';
import type { CompanyRole } from '@/types/company';

interface LabelItem {
    id: number;
    name: string;
    slug: string;
    color: string;
    icon: string | null;
    description: string | null;
    zonesCount: number;
    addressesCount: number;
    createdAt: string;
}

interface Props {
    company: { id: number; name: string; logo?: string; status: string };
    userRole: CompanyRole;
    labels: LabelItem[];
    filters: { search?: string };
    availableIcons: string[];
    defaultColors: string[];
}

export default function LabelsIndex({ company, userRole, labels, filters, availableIcons, defaultColors }: Props) {
    const [search, setSearch] = useState(filters.search || '');
    const [createDialogOpen, setCreateDialogOpen] = useState(false);
    const [editingLabel, setEditingLabel] = useState<LabelItem | null>(null);
    const [deletingLabel, setDeletingLabel] = useState<LabelItem | null>(null);

    const createForm = useForm({
        name: '',
        color: defaultColors[0] || '#3B82F6',
        icon: '',
        description: '',
    });

    const editForm = useForm({
        name: '',
        color: '',
        icon: '',
        description: '',
    });

    const handleSearch = (e: React.FormEvent) => {
        e.preventDefault();
        router.get('/company/labels', { search }, { preserveState: true });
    };

    const handleCreate = (e: React.FormEvent) => {
        e.preventDefault();
        createForm.post('/company/labels', {
            onSuccess: () => {
                setCreateDialogOpen(false);
                createForm.reset();
            },
        });
    };

    const handleEdit = (label: LabelItem) => {
        setEditingLabel(label);
        editForm.setData({
            name: label.name,
            color: label.color,
            icon: label.icon || '',
            description: label.description || '',
        });
    };

    const handleUpdate = (e: React.FormEvent) => {
        e.preventDefault();
        if (!editingLabel) return;
        editForm.put(`/company/labels/${editingLabel.id}`, {
            onSuccess: () => setEditingLabel(null),
        });
    };

    const handleDelete = () => {
        if (!deletingLabel) return;
        router.delete(`/company/labels/${deletingLabel.id}`, {
            onSuccess: () => setDeletingLabel(null),
        });
    };

    const filteredLabels = labels.filter(
        (label) =>
            label.name.toLowerCase().includes(search.toLowerCase()) ||
            (label.description?.toLowerCase() || '').includes(search.toLowerCase())
    );

    return (
        <CompanyLayout title="Labels" company={company} userRole={userRole}>
            <div className="space-y-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div>
                        <h2 className="text-2xl font-bold tracking-tight">Labels</h2>
                        <p className="text-muted-foreground">Organisez vos zones et adresses avec des labels personnalisés</p>
                    </div>
                    <Dialog open={createDialogOpen} onOpenChange={setCreateDialogOpen}>
                        <DialogTrigger asChild>
                            <Button>
                                <Plus className="mr-2 h-4 w-4" />
                                Nouveau label
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Créer un label</DialogTitle>
                                <DialogDescription>Ajoutez un nouveau label pour organiser vos données</DialogDescription>
                            </DialogHeader>
                            <form onSubmit={handleCreate} className="space-y-4">
                                <div className="space-y-2">
                                    <Label htmlFor="name">Nom</Label>
                                    <Input
                                        id="name"
                                        value={createForm.data.name}
                                        onChange={(e) => createForm.setData('name', e.target.value)}
                                        placeholder="Ex: Prioritaire, Client VIP..."
                                    />
                                    {createForm.errors.name && <p className="text-sm text-destructive">{createForm.errors.name}</p>}
                                </div>
                                <div className="space-y-2">
                                    <Label>Couleur</Label>
                                    <div className="flex flex-wrap gap-2">
                                        {defaultColors.map((color) => (
                                            <button
                                                key={color}
                                                type="button"
                                                onClick={() => createForm.setData('color', color)}
                                                className={`h-8 w-8 rounded-full border-2 ${createForm.data.color === color ? 'border-foreground' : 'border-transparent'}`}
                                                style={{ backgroundColor: color }}
                                            />
                                        ))}
                                    </div>
                                </div>
                                <div className="space-y-2">
                                    <Label htmlFor="description">Description (optionnel)</Label>
                                    <Textarea
                                        id="description"
                                        value={createForm.data.description}
                                        onChange={(e) => createForm.setData('description', e.target.value)}
                                        placeholder="Description du label..."
                                        rows={2}
                                    />
                                </div>
                                <DialogFooter>
                                    <Button type="button" variant="outline" onClick={() => setCreateDialogOpen(false)}>
                                        Annuler
                                    </Button>
                                    <Button type="submit" disabled={createForm.processing}>
                                        Créer
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                {/* Search */}
                <form onSubmit={handleSearch} className="flex gap-2">
                    <div className="relative flex-1">
                        <Search className="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            placeholder="Rechercher un label..."
                            className="pl-9"
                        />
                    </div>
                    <Button type="submit" variant="secondary">
                        Rechercher
                    </Button>
                </form>

                {/* Labels Grid */}
                {filteredLabels.length > 0 ? (
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {filteredLabels.map((label) => (
                            <Card key={label.id} className="relative">
                                <CardHeader className="pb-2">
                                    <div className="flex items-start justify-between">
                                        <div className="flex items-center gap-2">
                                            <div className="h-4 w-4 rounded-full" style={{ backgroundColor: label.color }} />
                                            <CardTitle className="text-base">{label.name}</CardTitle>
                                        </div>
                                        <div className="flex gap-1">
                                            <Button variant="ghost" size="icon" className="h-8 w-8" onClick={() => handleEdit(label)}>
                                                <Edit2 className="h-4 w-4" />
                                            </Button>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                className="h-8 w-8 text-destructive hover:text-destructive"
                                                onClick={() => setDeletingLabel(label)}
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </Button>
                                        </div>
                                    </div>
                                    {label.description && <CardDescription className="mt-1">{label.description}</CardDescription>}
                                </CardHeader>
                                <CardContent>
                                    <div className="flex gap-4 text-sm text-muted-foreground">
                                        <div className="flex items-center gap-1">
                                            <Shapes className="h-4 w-4" />
                                            {label.zonesCount} zone(s)
                                        </div>
                                        <div className="flex items-center gap-1">
                                            <MapPin className="h-4 w-4" />
                                            {label.addressesCount} adresse(s)
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                ) : (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <Tags className="h-12 w-12 text-muted-foreground/50" />
                            <p className="mt-4 text-center text-muted-foreground">
                                {search ? 'Aucun label trouvé' : 'Aucun label créé'}
                            </p>
                            {!search && (
                                <Button variant="outline" className="mt-4" onClick={() => setCreateDialogOpen(true)}>
                                    <Plus className="mr-2 h-4 w-4" />
                                    Créer votre premier label
                                </Button>
                            )}
                        </CardContent>
                    </Card>
                )}

                {/* Edit Dialog */}
                <Dialog open={!!editingLabel} onOpenChange={(open) => !open && setEditingLabel(null)}>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Modifier le label</DialogTitle>
                            <DialogDescription>Modifiez les informations du label</DialogDescription>
                        </DialogHeader>
                        <form onSubmit={handleUpdate} className="space-y-4">
                            <div className="space-y-2">
                                <Label htmlFor="edit-name">Nom</Label>
                                <Input
                                    id="edit-name"
                                    value={editForm.data.name}
                                    onChange={(e) => editForm.setData('name', e.target.value)}
                                />
                            </div>
                            <div className="space-y-2">
                                <Label>Couleur</Label>
                                <div className="flex flex-wrap gap-2">
                                    {defaultColors.map((color) => (
                                        <button
                                            key={color}
                                            type="button"
                                            onClick={() => editForm.setData('color', color)}
                                            className={`h-8 w-8 rounded-full border-2 ${editForm.data.color === color ? 'border-foreground' : 'border-transparent'}`}
                                            style={{ backgroundColor: color }}
                                        />
                                    ))}
                                </div>
                            </div>
                            <div className="space-y-2">
                                <Label htmlFor="edit-description">Description</Label>
                                <Textarea
                                    id="edit-description"
                                    value={editForm.data.description}
                                    onChange={(e) => editForm.setData('description', e.target.value)}
                                    rows={2}
                                />
                            </div>
                            <DialogFooter>
                                <Button type="button" variant="outline" onClick={() => setEditingLabel(null)}>
                                    Annuler
                                </Button>
                                <Button type="submit" disabled={editForm.processing}>
                                    Enregistrer
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>

                {/* Delete Confirmation */}
                <AlertDialog open={!!deletingLabel} onOpenChange={(open) => !open && setDeletingLabel(null)}>
                    <AlertDialogContent>
                        <AlertDialogHeader>
                            <AlertDialogTitle>Supprimer le label ?</AlertDialogTitle>
                            <AlertDialogDescription>
                                Cette action est irréversible. Le label "{deletingLabel?.name}" sera définitivement supprimé.
                                Les zones et adresses associées ne seront pas supprimées.
                            </AlertDialogDescription>
                        </AlertDialogHeader>
                        <AlertDialogFooter>
                            <AlertDialogCancel>Annuler</AlertDialogCancel>
                            <AlertDialogAction onClick={handleDelete} className="bg-destructive text-destructive-foreground">
                                Supprimer
                            </AlertDialogAction>
                        </AlertDialogFooter>
                    </AlertDialogContent>
                </AlertDialog>
            </div>
        </CompanyLayout>
    );
}
