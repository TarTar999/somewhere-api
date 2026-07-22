import { useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription } from '@/components/ui/alert';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import {
    Zap,
    RefreshCw,
    Trash2,
    Settings,
    Calendar,
    Clock,
    MapPin,
    AlertTriangle,
    CheckCircle,
    Loader2,
} from 'lucide-react';

interface Programme {
    id: number;
    external_id: string;
    region: string;
    ville: string;
    zone: string;
    prog_date: string;
    prog_heure_debut: string;
    prog_heure_fin: string;
    lib_traveaux: string;
}

interface Config {
    url: string;
    updated_at: string | null;
}

interface Stats {
    total: number;
    upcoming: number;
    today: number;
    past: number;
    lastSync: string | null;
}

interface Props {
    config: Config;
    stats: Stats;
    programmes: Programme[];
    defaultUrl: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Admin', href: '/admin' },
    { title: 'ENEO', href: '/admin/eneo' },
];

export default function EneoIndex({ config, stats, programmes, defaultUrl }: Props) {
    const [url, setUrl] = useState(config.url);
    const [isSyncing, setIsSyncing] = useState(false);
    const [isDeleting, setIsDeleting] = useState(false);
    const [isSavingConfig, setIsSavingConfig] = useState(false);
    const [message, setMessage] = useState<{ type: 'success' | 'error'; text: string } | null>(null);
    const [syncStats, setSyncStats] = useState<any>(null);

    const handleSaveConfig = async () => {
        setIsSavingConfig(true);
        setMessage(null);

        try {
            const response = await fetch('/admin/eneo/config', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ url }),
            });

            const data = await response.json();

            if (data.success) {
                setMessage({ type: 'success', text: 'Configuration sauvegardee' });
            } else {
                setMessage({ type: 'error', text: data.message || 'Erreur' });
            }
        } catch (error) {
            setMessage({ type: 'error', text: 'Erreur de connexion' });
        } finally {
            setIsSavingConfig(false);
        }
    };

    const handleSync = async () => {
        setIsSyncing(true);
        setMessage(null);
        setSyncStats(null);

        try {
            // Fetch from ENEO API directly from browser (no pagination)
            setMessage({ type: 'success', text: 'Recuperation des programmes depuis ENEO...' });

            const eneoResponse = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                },
            });

            if (!eneoResponse.ok) {
                throw new Error(`ENEO API error: ${eneoResponse.status}`);
            }

            const eneoData = await eneoResponse.json();

            if (!eneoData.status) {
                throw new Error(eneoData.message || 'ENEO API returned error');
            }

            const programmes = eneoData.data?.programmes || [];

            setMessage({ type: 'success', text: `${programmes.length} programmes recuperes. Sauvegarde en cours...` });

            // Send programmes to our backend to save
            const response = await fetch('/admin/eneo/sync', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify({ programmes }),
            });

            const data = await response.json();

            if (data.success) {
                setMessage({ type: 'success', text: data.message });
                setSyncStats(data.stats);
                router.reload({ only: ['stats', 'programmes'] });
            } else {
                setMessage({ type: 'error', text: data.message || 'Erreur de sauvegarde' });
            }
        } catch (error: any) {
            setMessage({ type: 'error', text: `Erreur: ${error.message}` });
        } finally {
            setIsSyncing(false);
        }
    };

    const handleDeletePast = async () => {
        if (!confirm('Supprimer tous les programmes de coupure passes ?')) {
            return;
        }

        setIsDeleting(true);
        setMessage(null);

        try {
            const response = await fetch('/admin/eneo/past', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (data.success) {
                setMessage({ type: 'success', text: data.message });
                router.reload({ only: ['stats', 'programmes'] });
            } else {
                setMessage({ type: 'error', text: data.message || 'Erreur' });
            }
        } catch (error) {
            setMessage({ type: 'error', text: 'Erreur de connexion' });
        } finally {
            setIsDeleting(false);
        }
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString('fr-FR', {
            weekday: 'short',
            day: 'numeric',
            month: 'short',
        });
    };

    const formatTime = (timeString: string) => {
        if (!timeString) return '--:--';
        return timeString.substring(0, 5);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="ENEO - Gestion des coupures" />

            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex h-12 w-12 items-center justify-center rounded-lg bg-yellow-500/10">
                            <Zap className="h-6 w-6 text-yellow-500" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold">ENEO - Programmes de coupure</h1>
                            <p className="text-muted-foreground">Gestion des coupures electriques</p>
                        </div>
                    </div>
                </div>

                {/* Message Alert */}
                {message && (
                    <Alert variant={message.type === 'error' ? 'destructive' : 'default'}>
                        {message.type === 'success' ? (
                            <CheckCircle className="h-4 w-4" />
                        ) : (
                            <AlertTriangle className="h-4 w-4" />
                        )}
                        <AlertDescription>{message.text}</AlertDescription>
                    </Alert>
                )}

                {/* Sync Stats */}
                {syncStats && (
                    <Card className="border-green-200 bg-green-50">
                        <CardContent className="pt-6">
                            <div className="grid grid-cols-5 gap-4 text-center">
                                <div>
                                    <p className="text-2xl font-bold">{syncStats.fetched}</p>
                                    <p className="text-sm text-muted-foreground">Recuperes</p>
                                </div>
                                <div>
                                    <p className="text-2xl font-bold text-green-600">{syncStats.created}</p>
                                    <p className="text-sm text-muted-foreground">Crees</p>
                                </div>
                                <div>
                                    <p className="text-2xl font-bold text-blue-600">{syncStats.updated}</p>
                                    <p className="text-sm text-muted-foreground">Mis a jour</p>
                                </div>
                                <div>
                                    <p className="text-2xl font-bold text-gray-600">{syncStats.skipped}</p>
                                    <p className="text-sm text-muted-foreground">Ignores</p>
                                </div>
                                <div>
                                    <p className="text-2xl font-bold text-red-600">{syncStats.errors}</p>
                                    <p className="text-sm text-muted-foreground">Erreurs</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                )}

                {/* Stats Cards */}
                <div className="grid gap-4 md:grid-cols-4">
                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-4">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-500/10">
                                    <Calendar className="h-5 w-5 text-blue-500" />
                                </div>
                                <div>
                                    <p className="text-2xl font-bold">{stats.total}</p>
                                    <p className="text-sm text-muted-foreground">Total programmes</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-4">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-yellow-500/10">
                                    <Zap className="h-5 w-5 text-yellow-500" />
                                </div>
                                <div>
                                    <p className="text-2xl font-bold">{stats.today}</p>
                                    <p className="text-sm text-muted-foreground">Aujourd'hui</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-4">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-green-500/10">
                                    <Clock className="h-5 w-5 text-green-500" />
                                </div>
                                <div>
                                    <p className="text-2xl font-bold">{stats.upcoming}</p>
                                    <p className="text-sm text-muted-foreground">A venir</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardContent className="pt-6">
                            <div className="flex items-center gap-4">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-gray-500/10">
                                    <Trash2 className="h-5 w-5 text-gray-500" />
                                </div>
                                <div>
                                    <p className="text-2xl font-bold">{stats.past}</p>
                                    <p className="text-sm text-muted-foreground">Passes</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                {/* Configuration */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Settings className="h-5 w-5" />
                            Configuration
                        </CardTitle>
                        <CardDescription>
                            URL de l'API ENEO pour la synchronisation des programmes de coupure
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="space-y-4">
                        <div className="flex gap-4">
                            <Input
                                value={url}
                                onChange={(e) => setUrl(e.target.value)}
                                placeholder="URL de l'API ENEO"
                                className="flex-1 font-mono text-sm"
                            />
                            <Button onClick={handleSaveConfig} disabled={isSavingConfig}>
                                {isSavingConfig ? (
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                ) : null}
                                Sauvegarder
                            </Button>
                        </div>
                        <p className="text-xs text-muted-foreground">
                            URL par defaut: <code className="rounded bg-muted px-1">{defaultUrl}</code>
                        </p>
                        {config.updated_at && (
                            <p className="text-xs text-muted-foreground">
                                Derniere modification: {new Date(config.updated_at).toLocaleString('fr-FR')}
                            </p>
                        )}
                    </CardContent>
                </Card>

                {/* Actions */}
                <Card>
                    <CardHeader>
                        <CardTitle>Actions</CardTitle>
                        <CardDescription>
                            Synchroniser les programmes depuis l'API ENEO ou nettoyer les anciens programmes
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="flex gap-4">
                            <Button onClick={handleSync} disabled={isSyncing} className="flex-1">
                                {isSyncing ? (
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                ) : (
                                    <RefreshCw className="mr-2 h-4 w-4" />
                                )}
                                {isSyncing ? 'Synchronisation...' : 'Synchroniser maintenant'}
                            </Button>
                            <Button
                                variant="destructive"
                                onClick={handleDeletePast}
                                disabled={isDeleting || stats.past === 0}
                            >
                                {isDeleting ? (
                                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                                ) : (
                                    <Trash2 className="mr-2 h-4 w-4" />
                                )}
                                Supprimer passes ({stats.past})
                            </Button>
                        </div>
                        {stats.lastSync && (
                            <p className="mt-4 text-sm text-muted-foreground">
                                Derniere synchronisation: {new Date(stats.lastSync).toLocaleString('fr-FR')}
                            </p>
                        )}
                    </CardContent>
                </Card>

                {/* Programmes List */}
                <Card>
                    <CardHeader>
                        <CardTitle className="flex items-center gap-2">
                            <Zap className="h-5 w-5" />
                            Programmes a venir
                        </CardTitle>
                        <CardDescription>
                            Les 50 prochains programmes de coupure
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {programmes.length === 0 ? (
                            <div className="py-12 text-center">
                                <Zap className="mx-auto h-12 w-12 text-muted-foreground/50" />
                                <p className="mt-4 text-muted-foreground">Aucun programme de coupure</p>
                                <p className="text-sm text-muted-foreground">
                                    Lancez une synchronisation pour recuperer les programmes
                                </p>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full">
                                    <thead>
                                        <tr className="border-b">
                                            <th className="px-4 py-3 text-left text-sm font-medium">Date</th>
                                            <th className="px-4 py-3 text-left text-sm font-medium">Horaires</th>
                                            <th className="px-4 py-3 text-left text-sm font-medium">Ville</th>
                                            <th className="px-4 py-3 text-left text-sm font-medium">Zone</th>
                                            <th className="px-4 py-3 text-left text-sm font-medium">Travaux</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {programmes.map((prog) => (
                                            <tr key={prog.id} className="border-b hover:bg-muted/50">
                                                <td className="px-4 py-3">
                                                    <div className="flex items-center gap-2">
                                                        <Calendar className="h-4 w-4 text-muted-foreground" />
                                                        <span className="font-medium">
                                                            {formatDate(prog.prog_date)}
                                                        </span>
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <Badge variant="outline">
                                                        {formatTime(prog.prog_heure_debut)} - {formatTime(prog.prog_heure_fin)}
                                                    </Badge>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <div className="flex items-center gap-2">
                                                        <MapPin className="h-4 w-4 text-muted-foreground" />
                                                        {prog.ville}
                                                    </div>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <span className="text-sm">{prog.zone || '-'}</span>
                                                </td>
                                                <td className="px-4 py-3">
                                                    <span className="text-sm text-muted-foreground line-clamp-1">
                                                        {prog.lib_traveaux || '-'}
                                                    </span>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
