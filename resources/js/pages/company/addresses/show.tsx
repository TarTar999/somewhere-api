import { useState } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import CompanyLayout from '@/layouts/company-layout';
import { LeafletMap } from '@/components/map/leaflet-map';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
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
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Label } from '@/components/ui/label';
import { ArrowLeft, MapPin, Home, User, FileText, Plus, CheckCircle, Clock, XCircle } from 'lucide-react';
import type { CompanyRole, CompanyAddress, CompanyDocument } from '@/types/company';

interface Props {
    address: CompanyAddress;
    documents: CompanyDocument[];
    canCreateDocument: boolean;
    remainingDocuments: number;
}

export default function AddressShow({ address, documents, canCreateDocument, remainingDocuments }: Props) {
    const { props, errors } = usePage();
    const company = (props as { company?: { name: string; logo?: string } }).company || { name: 'Entreprise' };
    const userRole = ((props as { userRole?: CompanyRole }).userRole || 'member') as CompanyRole;

    const [documentType, setDocumentType] = useState<string>('location_plan');
    const [isCreating, setIsCreating] = useState(false);
    const [dialogOpen, setDialogOpen] = useState(false);

    const handleCreateDocument = () => {
        setIsCreating(true);
        router.post(
            `/company/addresses/${address.id}/document`,
            { documentType },
            {
                onFinish: () => {
                    setIsCreating(false);
                    setDialogOpen(false);
                },
            }
        );
    };

    const getStatusBadge = (status: string) => {
        switch (status) {
            case 'approved':
                return (
                    <Badge className="bg-green-500">
                        <CheckCircle className="mr-1 h-3 w-3" />
                        Vérifiée
                    </Badge>
                );
            case 'pending':
                return (
                    <Badge variant="secondary">
                        <Clock className="mr-1 h-3 w-3" />
                        En attente
                    </Badge>
                );
            default:
                return (
                    <Badge variant="destructive">
                        <XCircle className="mr-1 h-3 w-3" />
                        Rejetée
                    </Badge>
                );
        }
    };

    return (
        <CompanyLayout title={address.swAddress} company={company} userRole={userRole}>
            <div className="space-y-6">
                {/* Back Button */}
                <Link href="/company/addresses">
                    <Button variant="ghost" size="sm">
                        <ArrowLeft className="mr-2 h-4 w-4" />
                        Retour aux adresses
                    </Button>
                </Link>

                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Address Details */}
                    <div className="space-y-6">
                        <Card>
                            <CardHeader>
                                <div className="flex items-start justify-between">
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                                            <MapPin className="h-6 w-6 text-primary" />
                                        </div>
                                        <div>
                                            <CardTitle>{address.swAddress}</CardTitle>
                                            <CardDescription>{address.streetName || 'Adresse SomeWhere'}</CardDescription>
                                        </div>
                                    </div>
                                    {getStatusBadge(address.verificationStatus)}
                                </div>
                            </CardHeader>
                            <CardContent>
                                <dl className="grid gap-4 sm:grid-cols-2">
                                    {address.streetNumber && (
                                        <div>
                                            <dt className="text-sm font-medium text-muted-foreground">Numéro de rue</dt>
                                            <dd className="text-sm">{address.streetNumber}</dd>
                                        </div>
                                    )}
                                    {address.lieuDit && (
                                        <div>
                                            <dt className="text-sm font-medium text-muted-foreground">Lieu-dit</dt>
                                            <dd className="text-sm">{address.lieuDit}</dd>
                                        </div>
                                    )}
                                    {address.quarter && (
                                        <div>
                                            <dt className="text-sm font-medium text-muted-foreground">Quartier</dt>
                                            <dd className="text-sm">{address.quarter}</dd>
                                        </div>
                                    )}
                                    {address.commune && (
                                        <div>
                                            <dt className="text-sm font-medium text-muted-foreground">Commune</dt>
                                            <dd className="text-sm">{address.commune}</dd>
                                        </div>
                                    )}
                                    {address.houseType && (
                                        <div>
                                            <dt className="text-sm font-medium text-muted-foreground">Type</dt>
                                            <dd className="text-sm capitalize">{address.houseType}</dd>
                                        </div>
                                    )}
                                </dl>
                            </CardContent>
                        </Card>

                        {/* Owner */}
                        <Card>
                            <CardHeader>
                                <CardTitle className="flex items-center gap-2 text-lg">
                                    <User className="h-5 w-5" />
                                    Propriétaire
                                </CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p className="font-medium">{address.owner.name}</p>
                                {address.owner.email && <p className="text-sm text-muted-foreground">{address.owner.email}</p>}
                            </CardContent>
                        </Card>

                        {/* Documents */}
                        <Card>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <CardTitle className="flex items-center gap-2 text-lg">
                                        <FileText className="h-5 w-5" />
                                        Documents
                                    </CardTitle>
                                    <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
                                        <DialogTrigger asChild>
                                            <Button size="sm" disabled={!canCreateDocument}>
                                                <Plus className="mr-2 h-4 w-4" />
                                                Créer un document
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>Créer un document</DialogTitle>
                                                <DialogDescription>
                                                    Sélectionnez le type de document à créer pour cette adresse.
                                                    <br />
                                                    <span className="font-medium">{remainingDocuments} documents restants ce mois</span>
                                                </DialogDescription>
                                            </DialogHeader>
                                            <RadioGroup value={documentType} onValueChange={setDocumentType} className="space-y-3">
                                                <div>
                                                    <label
                                                        className={`flex cursor-pointer items-start gap-4 rounded-lg border p-4 transition-colors ${
                                                            documentType === 'location_plan' ? 'border-primary bg-primary/5' : 'hover:bg-muted'
                                                        }`}
                                                    >
                                                        <RadioGroupItem value="location_plan" className="mt-1" />
                                                        <div>
                                                            <span className="font-medium">Plan de Localisation</span>
                                                            <p className="text-sm text-muted-foreground">
                                                                Document avec coordonnées GPS et carte
                                                            </p>
                                                        </div>
                                                    </label>
                                                </div>
                                                <div>
                                                    <label
                                                        className={`flex cursor-pointer items-start gap-4 rounded-lg border p-4 transition-colors ${
                                                            documentType === 'proof_of_residence'
                                                                ? 'border-primary bg-primary/5'
                                                                : address.verificationStatus !== 'approved'
                                                                  ? 'cursor-not-allowed opacity-50'
                                                                  : 'hover:bg-muted'
                                                        }`}
                                                    >
                                                        <RadioGroupItem
                                                            value="proof_of_residence"
                                                            disabled={address.verificationStatus !== 'approved'}
                                                            className="mt-1"
                                                        />
                                                        <div>
                                                            <span className="font-medium">Attestation de Résidence</span>
                                                            <p className="text-sm text-muted-foreground">
                                                                Document officiel de domiciliation
                                                                {address.verificationStatus !== 'approved' && (
                                                                    <span className="block text-destructive">
                                                                        Requiert une adresse vérifiée
                                                                    </span>
                                                                )}
                                                            </p>
                                                        </div>
                                                    </label>
                                                </div>
                                            </RadioGroup>
                                            {(errors as { document?: string }).document && (
                                                <p className="text-sm text-destructive">{(errors as { document?: string }).document}</p>
                                            )}
                                            <DialogFooter>
                                                <Button variant="outline" onClick={() => setDialogOpen(false)}>
                                                    Annuler
                                                </Button>
                                                <Button onClick={handleCreateDocument} disabled={isCreating}>
                                                    {isCreating ? 'Création...' : 'Créer le document'}
                                                </Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                </div>
                            </CardHeader>
                            <CardContent>
                                {documents.length > 0 ? (
                                    <div className="space-y-3">
                                        {documents.map((doc) => (
                                            <div key={doc.id} className="flex items-center justify-between rounded-lg border p-3">
                                                <div>
                                                    <p className="font-medium">{doc.documentTypeLabel}</p>
                                                    <p className="text-xs text-muted-foreground">
                                                        {doc.documentNumber} - Créé {doc.createdAt}
                                                    </p>
                                                </div>
                                                <Badge variant={doc.isExpired ? 'destructive' : 'outline'}>
                                                    {doc.isExpired ? 'Expiré' : 'Actif'}
                                                </Badge>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <p className="text-center text-muted-foreground">Aucun document pour cette adresse</p>
                                )}
                            </CardContent>
                        </Card>
                    </div>

                    {/* Map */}
                    <Card className="overflow-hidden">
                        <div className="h-[600px]">
                            <LeafletMap
                                center={[address.latitude, address.longitude]}
                                config={{
                                    zoom: 17,
                                    maxZoom: 19,
                                    tileUrl: 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
                                    attribution: '&copy; OpenStreetMap',
                                }}
                                markers={[
                                    {
                                        id: address.id,
                                        position: [address.latitude, address.longitude],
                                        popup: `<strong>${address.swAddress}</strong>`,
                                    },
                                ]}
                            />
                        </div>
                    </Card>
                </div>
            </div>
        </CompanyLayout>
    );
}
