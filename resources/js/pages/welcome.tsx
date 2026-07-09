import { Head, Link, usePage } from '@inertiajs/react';
import { motion } from 'framer-motion';
import {
    Briefcase,
    Building2,
    Car,
    CheckCircle,
    ChevronDown,
    ChevronLeft,
    ChevronRight,
    FileText,
    Globe,
    GraduationCap,
    HeartPulse,
    Home,
    Landmark,
    Layers,
    MapPin,
    Menu,
    Navigation,
    Package,
    Pause,
    Plane,
    Play,
    QrCode,
    Scale,
    Shield,
    Star,
    Users,
    Vote,
    Wallet,
    X,
    Zap,
} from 'lucide-react';
import { useRef, useState } from 'react';
import { dashboard, login, register } from '@/routes';

import type { SharedData } from '@/types';

export default function Welcome({
    canRegister = true,
}: {
    canRegister?: boolean;
}) {
    const { auth } = usePage<SharedData>().props;
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [openFaq, setOpenFaq] = useState<number | null>(null);
    const [isVideoPlaying, setIsVideoPlaying] = useState(true);
    const [useCasePage, setUseCasePage] = useState(0);
    const videoRef = useRef<HTMLVideoElement>(null);

    const toggleVideo = () => {
        if (videoRef.current) {
            if (isVideoPlaying) {
                videoRef.current.pause();
            } else {
                videoRef.current.play();
            }
            setIsVideoPlaying(!isVideoPlaying);
        }
    };

    const features = [
        {
            icon: MapPin,
            title: 'Adresse unique SW',
            description:
                'Obtenez une adresse simplifiée et unique pour votre localisation, facile à partager et à mémoriser.',
            image: '/images/presentations/point_interets.webp',
        },
        {
            icon: FileText,
            title: 'Documents certifiés',
            description:
                'Générez des plans de localisation et attestations de résidence certifiés et vérifiables.',
        },
        {
            icon: QrCode,
            title: 'Vérification instantanée',
            description:
                'Chaque document possède un QR code unique permettant une vérification immédiate de son authenticité.',
        },
        {
            icon: Shield,
            title: 'Sécurisé et fiable',
            description:
                'Vos données sont protégées et vos documents sont infalsifiables grâce à notre technologie de hachage.',
        },
        {
            icon: Building2,
            title: 'Solution entreprise',
            description:
                'Gérez les adresses de vos employés, clients ou points de livraison avec notre dashboard entreprise.',
        },
        {
            icon: Globe,
            title: 'Couverture nationale',
            description:
                'Disponible dans toutes les villes du Cameroun avec une précision GPS optimale.',
        },
        {
            icon: Car,
            title: 'Navigation VTC',
            description:
                'Partagez votre adresse SW avec les chauffeurs Yango, Heetch ou taxi pour être trouvé facilement.',
        },
        {
            icon: Navigation,
            title: "Recherche d'adresse",
            description:
                "Trouvez n'importe quelle adresse SW instantanément grâce à notre moteur de recherche intelligent.",
        },
        {
            icon: Users,
            title: 'Campagnes de terrain',
            description:
                'Organisez et gérez vos équipes sur le terrain avec le suivi des collectes et livraisons en temps réel.',
        },
    ];

    const showcaseItems = [
        {
            title: "Création d'adresses",
            description:
                'Créez votre adresse unique SW en quelques clics avec localisation GPS précise.',
            media: '/images/presentations/presentation_carte.mp4',
            type: 'video' as const,
        },
        {
            title: "Points d'intérêt & itinéraires",
            description:
                "Créez, retrouvez vos points d'intérêt et générez des itinéraires personnalisés.",
            media: '/images/presentations/point_interets.webp',
            type: 'image' as const,
        },
        {
            title: 'Navigation simplifiée',
            description:
                'Partagez votre position et guidez vos visiteurs facilement.',
            media: '/images/presentations/navigation.webp',
            type: 'image' as const,
        },
        {
            title: 'Système de zones',
            description:
                'Créez des zones personnalisées pour organiser vos adresses par secteur.',
            media: '/images/presentations/systeme_zoning.mp4',
            type: 'video' as const,
        },
    ];

    const pricing = [
        {
            name: 'Particulier',
            price: '0',
            description: 'Toutes les fonctionnalités essentielles gratuitement',
            features: [
                "Inscription & création d'adresse",
                "Recherche & partage d'adresse",
                'Navigation VTC',
                'Collection & demande de livraison',
                'Domiciliation',
            ],
            extras: [{ name: 'Plan de localisation', price: 'Gratuit' }],
            popular: true,
            isEnterprise: false,
        },
        {
            name: 'Entreprise',
            price: '15 000',
            pricePrefix: 'À partir de',
            period: '/mois',
            description: 'Solution complète pour votre organisation',
            features: [
                'Toutes les fonctionnalités Particulier pour chaque membre',
                'Membres illimités',
                'Documents illimités',
                'Dashboard entreprise',
                'Gestion des zones',
                'API & intégrations',
            ],
            popular: false,
            isEnterprise: true,
        },
    ];

    const testimonials = [
        {
            name: 'Marie Kouam',
            role: 'Entrepreneure, Douala',
            content:
                "SomeWhere App m'a permis d'obtenir mon attestation de résidence en moins de 5 minutes. Fini les longues files d'attente !",
            rating: 5,
        },
        {
            name: 'Jean-Paul Mbarga',
            role: 'Responsable RH, Yaoundé',
            content:
                "Notre entreprise utilise SomeWhere pour vérifier les adresses de tous nos employés. C'est devenu indispensable.",
            rating: 5,
        },
        {
            name: 'Aminatou Sall',
            role: 'Commerçante, Bafoussam',
            content:
                'Mes clients me trouvent facilement grâce à mon adresse SW. Plus besoin de longues explications téléphoniques.',
            rating: 5,
        },
    ];

    const faqs = [
        {
            question: "Qu'est-ce qu'une adresse SW ?",
            answer: 'Une adresse SW est un code unique et simplifié qui identifie précisément votre localisation. Elle est composée de lettres et chiffres faciles à mémoriser et partager, remplaçant les descriptions complexes traditionnelles.',
        },
        {
            question: 'Les documents sont-ils reconnus officiellement ?',
            answer: 'Oui, nos documents sont certifiés et peuvent être vérifiés instantanément via le QR code. Ils sont acceptés par de nombreuses institutions et entreprises pour les démarches administratives.',
        },
        {
            question: 'Comment fonctionne la vérification ?',
            answer: "Chaque document possède un QR code unique. En le scannant, vous accédez à une page de vérification qui confirme l'authenticité du document et affiche les informations originales.",
        },
        {
            question: 'Quelle est la couverture géographique ?',
            answer: "SomeWhere App couvre l'ensemble du territoire camerounais. Notre système fonctionne partout où vous avez accès au GPS, même dans les zones rurales.",
        },
        {
            question: 'Combien de temps sont valides les documents ?',
            answer: "Les plans de localisation et attestations de résidence sont valides pendant 3 mois à partir de leur date d'émission. Vous pouvez les renouveler facilement depuis votre compte.",
        },
        {
            question: 'Comment contacter le support ?',
            answer: "Notre équipe support est disponible par email à support@somewhere-app.com ou via WhatsApp. Les abonnés entreprise bénéficient d'un support prioritaire.",
        },
    ];

    // Address use cases for Cameroonians
    const addressUseCases = [
        {
            category: 'Démarches administratives',
            icon: Landmark,
            color: 'from-blue-500 to-indigo-500',
            description: 'Simplifiez toutes vos procédures officielles',
            useCases: [
                {
                    title: 'Permis de construire',
                    description:
                        'Justificatif de localisation requis pour vos demandes de permis de construire.',
                },
                {
                    title: "Certificat d'urbanisme",
                    description:
                        "Document nécessaire pour connaître les règles d'urbanisme applicables à votre terrain.",
                },
                {
                    title: 'Certificat de résidence',
                    description:
                        'Attestation officielle de votre lieu de résidence pour vos démarches.',
                },
                {
                    title: "Création d'entreprise",
                    description:
                        "Justificatif d'adresse pour l'immatriculation et l'obtention d'agréments.",
                },
            ],
        },
        {
            category: 'Services financiers',
            icon: Wallet,
            color: 'from-emerald-500 to-teal-500',
            description: 'Accédez aux services bancaires et financiers',
            useCases: [
                {
                    title: 'Ouverture de compte bancaire',
                    description:
                        'Toutes les banques exigent un justificatif de domicile pour ouvrir un compte.',
                },
                {
                    title: 'Demande de crédit',
                    description:
                        "Les établissements de crédit vérifient votre adresse avant d'accorder un prêt.",
                },
                {
                    title: 'Mobile Money avancé',
                    description:
                        'Débloquez les limites de transaction avec une adresse vérifiée.',
                },
                {
                    title: 'Assurances',
                    description:
                        'Souscrivez à des assurances auto, habitation ou santé.',
                },
            ],
        },
        {
            category: 'E-commerce & livraison',
            icon: Package,
            color: 'from-orange-500 to-amber-500',
            description: 'Recevez vos colis sans complication',
            useCases: [
                {
                    title: 'Achats en ligne',
                    description:
                        'Commandez sur Jumia, Amazon et autres plateformes avec une adresse précise.',
                },
                {
                    title: 'Livraison de repas',
                    description:
                        'Glovo, Yango Food et autres services vous trouvent facilement.',
                },
                {
                    title: 'Courses à domicile',
                    description:
                        'Faites livrer vos courses de supermarchés directement chez vous.',
                },
                {
                    title: 'Colis internationaux',
                    description:
                        'Recevez vos colis DHL, FedEx ou EMS sans erreur de livraison.',
                },
            ],
        },
        {
            category: 'Immobilier & logement',
            icon: Home,
            color: 'from-violet-500 to-purple-500',
            description: 'Facilitez vos démarches immobilières',
            useCases: [
                {
                    title: 'Contrat de bail',
                    description:
                        'Document officiel pour la signature de votre contrat de location.',
                },
                {
                    title: 'Achat de propriété',
                    description:
                        'Preuve de résidence pour les transactions immobilières.',
                },
                {
                    title: 'Abonnements services',
                    description:
                        'Eau (CDE), électricité (ENEO), internet - tous nécessitent une adresse.',
                },
                {
                    title: 'Déménagement',
                    description:
                        "Facilitez votre changement d'adresse auprès de tous vos prestataires.",
                },
            ],
        },
        {
            category: 'Santé & urgences',
            icon: HeartPulse,
            color: 'from-red-500 to-rose-500',
            description: "Soyez localisable en cas d'urgence",
            useCases: [
                {
                    title: "Services d'urgence",
                    description:
                        'Ambulances et secours vous localisent rapidement grâce à votre adresse SW.',
                },
                {
                    title: 'Dossier médical',
                    description:
                        'Inscription dans les hôpitaux et cliniques avec adresse vérifiée.',
                },
                {
                    title: 'Assurance maladie',
                    description:
                        'CNPS et mutuelles de santé exigent un justificatif de domicile.',
                },
                {
                    title: 'Pharmacies de garde',
                    description:
                        'Livraison de médicaments à domicile facilitée.',
                },
            ],
        },
        {
            category: 'Éducation & formation',
            icon: GraduationCap,
            color: 'from-cyan-500 to-blue-500',
            description: 'Inscriptions scolaires et universitaires',
            useCases: [
                {
                    title: 'Inscriptions scolaires',
                    description:
                        'Écoles primaires, collèges et lycées demandent un justificatif de domicile.',
                },
                {
                    title: 'Universités',
                    description:
                        "Dossier d'inscription universitaire complet avec adresse officielle.",
                },
                {
                    title: "Bourses d'études",
                    description:
                        'Candidatures aux bourses nationales et internationales.',
                },
                {
                    title: 'Concours administratifs',
                    description:
                        'ENAM, ENS, et autres concours exigent une adresse vérifiable.',
                },
            ],
        },
        {
            category: 'Emploi & carrière',
            icon: Briefcase,
            color: 'from-slate-600 to-gray-700',
            description: 'Boostez vos candidatures professionnelles',
            useCases: [
                {
                    title: 'CV professionnel',
                    description:
                        'Une adresse claire et vérifiable renforce votre crédibilité.',
                },
                {
                    title: 'Contrat de travail',
                    description:
                        'Document requis pour la signature de votre contrat.',
                },
                {
                    title: 'Fonction publique',
                    description:
                        "Intégration et affectation dans l'administration publique.",
                },
                {
                    title: 'Freelance & auto-entrepreneur',
                    description:
                        'Adresse professionnelle pour vos activités indépendantes.',
                },
            ],
        },
        {
            category: 'Transport & mobilité',
            icon: Car,
            color: 'from-indigo-500 to-violet-500',
            description: 'Simplifiez vos déplacements',
            useCases: [
                {
                    title: 'Immatriculation véhicule',
                    description:
                        "Carte grise et plaques d'immatriculation avec adresse vérifiée.",
                },
                {
                    title: 'VTC & taxi',
                    description:
                        'Yango, Heetch et taxis vous trouvent facilement.',
                },
                {
                    title: 'Covoiturage',
                    description:
                        'Points de rendez-vous précis pour le covoiturage.',
                },
                {
                    title: 'Location de véhicule',
                    description:
                        'Agences de location exigent une adresse pour le contrat.',
                },
            ],
        },
        {
            category: 'Voyages & tourisme',
            icon: Plane,
            color: 'from-sky-500 to-blue-500',
            description: 'Préparez vos voyages sereinement',
            useCases: [
                {
                    title: 'Demande de visa',
                    description:
                        'Ambassades et consulats exigent un justificatif de domicile.',
                },
                {
                    title: 'Réservations hôtelières',
                    description:
                        'Booking, Airbnb et hôtels demandent votre adresse.',
                },
                {
                    title: 'Agences de voyage',
                    description:
                        'Constitution de dossiers pour voyages organisés.',
                },
                {
                    title: 'Retour au pays',
                    description:
                        'Facilitez vos formalités de retour avec une adresse locale.',
                },
            ],
        },
        {
            category: 'Famille & vie quotidienne',
            icon: Users,
            color: 'from-pink-500 to-rose-500',
            description: 'Gérez les démarches familiales',
            useCases: [
                {
                    title: 'Allocations familiales',
                    description:
                        'CNPS et aides sociales nécessitent une preuve de résidence.',
                },
                {
                    title: 'Inscription crèche',
                    description:
                        'Garderies et crèches demandent un justificatif de domicile.',
                },
                {
                    title: 'Mariage civil',
                    description:
                        'Publication des bans et cérémonie à la mairie.',
                },
                {
                    title: 'Tutelle & adoption',
                    description:
                        'Procédures légales familiales avec adresse vérifiée.',
                },
            ],
        },
        {
            category: 'Justice & légal',
            icon: Scale,
            color: 'from-amber-600 to-yellow-600',
            description: 'Procédures juridiques et légales',
            useCases: [
                {
                    title: 'Procédures judiciaires',
                    description:
                        'Convocations et correspondances du tribunal à votre adresse.',
                },
                {
                    title: 'Notaire',
                    description:
                        'Actes notariés et successions avec adresse officielle.',
                },
                {
                    title: 'Casier judiciaire',
                    description: "Demande d'extrait de casier judiciaire.",
                },
                {
                    title: 'Huissier',
                    description:
                        "Significations et actes d'huissier à domicile.",
                },
            ],
        },
        {
            category: 'Élections & citoyenneté',
            icon: Vote,
            color: 'from-green-600 to-emerald-600',
            description: 'Exercez vos droits civiques',
            useCases: [
                {
                    title: 'Inscription électorale',
                    description:
                        'Inscrivez-vous sur les listes électorales de votre commune.',
                },
                {
                    title: "Carte d'électeur",
                    description:
                        'Obtenez votre carte pour voter aux élections.',
                },
                {
                    title: 'Bureau de vote',
                    description:
                        'Identifiez votre bureau de vote selon votre adresse.',
                },
                {
                    title: 'Référendums',
                    description: 'Participez aux consultations citoyennes.',
                },
            ],
        },
    ];

    return (
        <>
            <Head title="Adressage intelligent au Cameroun">
                <meta
                    name="description"
                    content="SomeWhere App - La solution d'adressage intelligent au Cameroun. Obtenez votre plan de localisation et attestation de résidence en quelques minutes."
                />
                <link rel="preconnect" href="https://fonts.bunny.net" />
                <link
                    href="https://fonts.bunny.net/css?family=inter:400,500,600,700&family=space-grotesk:500,600,700"
                    rel="stylesheet"
                />
            </Head>

            <div className="min-h-screen bg-white text-gray-900">
                {/* Navigation */}
                <nav className="fixed top-0 right-0 left-0 z-50 border-b border-gray-100 bg-white/95 backdrop-blur-md">
                    <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                        <div className="flex h-16 items-center justify-between">
                            {/* Logo */}
                            <Link href="/" className="flex items-center gap-2">
                                <img
                                    src="/images/icon.png"
                                    alt="SomeWhere"
                                    className="h-8 w-8"
                                    onError={(e) => {
                                        e.currentTarget.style.display = 'none';
                                    }}
                                />
                                <span className="font-display text-xl font-bold">
                                    SomeWhere App
                                </span>
                            </Link>

                            {/* Desktop Navigation */}
                            <div className="hidden items-center gap-8 md:flex">
                                <a
                                    href="#usages"
                                    className="text-sm font-medium text-gray-600 transition-colors hover:text-gray-900"
                                >
                                    À quoi ça sert ?
                                </a>
                                <a
                                    href="#features"
                                    className="text-sm font-medium text-gray-600 transition-colors hover:text-gray-900"
                                >
                                    Fonctionnalités
                                </a>
                                <a
                                    href="#pricing"
                                    className="text-sm font-medium text-gray-600 transition-colors hover:text-gray-900"
                                >
                                    Tarifs
                                </a>
                                <a
                                    href="#faq"
                                    className="text-sm font-medium text-gray-600 transition-colors hover:text-gray-900"
                                >
                                    FAQ
                                </a>
                            </div>

                            {/* Auth Buttons */}
                            <div className="hidden items-center gap-3 md:flex">
                                {auth.user ? (
                                    <Link
                                        href={dashboard()}
                                        className="inline-flex items-center justify-center rounded-lg bg-gradient-to-r from-indigo-500 to-violet-500 px-4 py-2 text-sm font-medium text-white shadow-md shadow-indigo-500/15 transition-all hover:from-indigo-600 hover:to-violet-600"
                                    >
                                        Dashboard
                                    </Link>
                                ) : (
                                    <>
                                        <Link
                                            href={login()}
                                            className="text-sm font-medium text-gray-600 transition-colors hover:text-gray-900"
                                        >
                                            Connexion
                                        </Link>
                                        {canRegister && (
                                            <Link
                                                href={register()}
                                                className="inline-flex items-center justify-center rounded-lg bg-gradient-to-r from-indigo-500 to-violet-500 px-4 py-2 text-sm font-medium text-white shadow-md shadow-indigo-500/15 transition-all hover:from-indigo-600 hover:to-violet-600"
                                            >
                                                Commencer
                                            </Link>
                                        )}
                                    </>
                                )}
                            </div>

                            {/* Mobile menu button */}
                            <button
                                onClick={() =>
                                    setMobileMenuOpen(!mobileMenuOpen)
                                }
                                className="p-2 text-gray-600 md:hidden"
                            >
                                {mobileMenuOpen ? (
                                    <X className="h-6 w-6" />
                                ) : (
                                    <Menu className="h-6 w-6" />
                                )}
                            </button>
                        </div>
                    </div>

                    {/* Mobile menu */}
                    {mobileMenuOpen && (
                        <div className="border-t border-gray-100 bg-white px-4 py-4 md:hidden">
                            <div className="flex flex-col gap-4">
                                <a
                                    href="#usages"
                                    className="text-sm font-medium text-gray-600"
                                >
                                    À quoi ça sert ?
                                </a>
                                <a
                                    href="#features"
                                    className="text-sm font-medium text-gray-600"
                                >
                                    Fonctionnalités
                                </a>
                                <a
                                    href="#pricing"
                                    className="text-sm font-medium text-gray-600"
                                >
                                    Tarifs
                                </a>
                                <a
                                    href="#faq"
                                    className="text-sm font-medium text-gray-600"
                                >
                                    FAQ
                                </a>
                                <hr className="border-gray-100" />
                                {auth.user ? (
                                    <Link
                                        href={dashboard()}
                                        className="text-sm font-medium text-indigo-600"
                                    >
                                        Dashboard
                                    </Link>
                                ) : (
                                    <>
                                        <Link
                                            href={login()}
                                            className="text-sm font-medium text-gray-600"
                                        >
                                            Connexion
                                        </Link>
                                        {canRegister && (
                                            <Link
                                                href={register()}
                                                className="inline-flex items-center justify-center rounded-lg bg-gradient-to-r from-indigo-500 to-violet-500 px-4 py-2 text-sm font-medium text-white"
                                            >
                                                Commencer
                                            </Link>
                                        )}
                                    </>
                                )}
                            </div>
                        </div>
                    )}
                </nav>

                {/* Hero Section with Video */}
                <section className="overflow-hidden px-4 pt-24 pb-12 sm:px-6 lg:px-8 lg:pt-32 lg:pb-20">
                    <div className="mx-auto max-w-7xl">
                        <div className="grid items-center gap-8 lg:grid-cols-2 lg:gap-16">
                            <motion.div
                                initial={{ opacity: 0, y: 20 }}
                                animate={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.6 }}
                            >
                                <h1 className="mb-6 font-display text-4xl font-bold tracking-tight sm:text-5xl lg:text-6xl">
                                    Votre adresse,{' '}
                                    <span className="bg-gradient-to-r from-indigo-500 to-violet-500 bg-clip-text text-transparent">
                                        partout
                                    </span>
                                    , simplement.
                                </h1>
                                <p className="mb-8 max-w-lg text-lg text-gray-600 lg:text-xl">
                                    Obtenez une adresse unique et vérifiable
                                    pour votre domicile ou entreprise. Générez
                                    vos plans de localisation en quelques
                                    minutes.
                                </p>
                                <div className="mb-6 flex flex-col gap-4 sm:flex-row">
                                    <Link
                                        href={register()}
                                        className="inline-flex items-center justify-center rounded-xl bg-gradient-to-r from-indigo-500 to-violet-500 px-6 py-3.5 text-base font-medium text-white shadow-sm shadow-indigo-500/15 transition-all hover:from-indigo-600 hover:to-violet-600"
                                    >
                                        Créer mon adresse SW
                                        <ChevronRight className="ml-2 h-5 w-5" />
                                    </Link>
                                    <a
                                        href="#showcase"
                                        className="inline-flex items-center justify-center rounded-xl bg-gray-100 px-6 py-3.5 text-base font-medium text-gray-700 transition-colors hover:bg-gray-200"
                                    >
                                        <Play className="mr-2 h-5 w-5" />
                                        Voir la démo
                                    </a>
                                </div>

                                {/* App Store Buttons */}
                                <div className="mb-8 flex flex-wrap items-center gap-3">
                                    <a
                                        href="https://play.google.com/store/apps/details?id=com.somewhere.app"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-2 rounded-lg bg-black px-4 py-2.5 text-white transition-colors hover:bg-gray-800"
                                    >
                                        <svg
                                            className="h-6 w-6"
                                            viewBox="0 0 24 24"
                                            fill="currentColor"
                                        >
                                            <path d="M3.609 1.814L13.792 12 3.61 22.186a.996.996 0 0 1-.61-.92V2.734a1 1 0 0 1 .609-.92zm10.89 10.893l2.302 2.302-10.937 6.333 8.635-8.635zm3.199-3.198l2.807 1.626a1 1 0 0 1 0 1.73l-2.808 1.626L15.206 12l2.492-2.491zM5.864 2.658L16.8 8.99l-2.302 2.302-8.634-8.634z" />
                                        </svg>
                                        <div className="text-left">
                                            <div className="text-[10px] leading-tight opacity-80">
                                                Télécharger sur
                                            </div>
                                            <div className="text-sm leading-tight font-semibold">
                                                Google Play
                                            </div>
                                        </div>
                                    </a>
                                    <a
                                        href="https://apps.apple.com/app/somewhere-app"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="inline-flex items-center gap-2 rounded-lg bg-black px-4 py-2.5 text-white transition-colors hover:bg-gray-800"
                                    >
                                        <svg
                                            className="h-6 w-6"
                                            viewBox="0 0 24 24"
                                            fill="currentColor"
                                        >
                                            <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M13 3.5c.73-.83 1.94-1.46 2.94-1.5.13 1.17-.34 2.35-1.04 3.19-.69.85-1.83 1.51-2.95 1.42-.15-1.15.41-2.35 1.05-3.11z" />
                                        </svg>
                                        <div className="text-left">
                                            <div className="text-[10px] leading-tight opacity-80">
                                                Télécharger sur
                                            </div>
                                            <div className="text-sm leading-tight font-semibold">
                                                App Store
                                            </div>
                                        </div>
                                    </a>
                                </div>

                                <div className="flex flex-wrap items-center gap-6 text-sm text-gray-500">
                                    <div className="flex items-center gap-2">
                                        <CheckCircle className="h-5 w-5 text-green-500" />
                                        <span>Inscription gratuite</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <CheckCircle className="h-5 w-5 text-green-500" />
                                        <span>Document en 5 min</span>
                                    </div>
                                    <div className="flex items-center gap-2">
                                        <CheckCircle className="h-5 w-5 text-green-500" />
                                        <span>100% sécurisé</span>
                                    </div>
                                </div>
                            </motion.div>

                            {/* Hero Video/Image */}
                            <motion.div
                                initial={{ opacity: 0, scale: 0.95 }}
                                animate={{ opacity: 1, scale: 1 }}
                                transition={{ duration: 0.6, delay: 0.2 }}
                                className="relative"
                            >
                                <div className="relative overflow-hidden rounded-2xl border border-gray-200 shadow-2xl shadow-indigo-500/20">
                                    <video
                                        ref={videoRef}
                                        autoPlay
                                        loop
                                        muted
                                        playsInline
                                        className="h-auto w-full"
                                    >
                                        <source
                                            src="/images/presentations/presentation_carte.mp4"
                                            type="video/mp4"
                                        />
                                    </video>
                                    {/* Video overlay controls */}
                                    <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/30 to-transparent" />
                                    <button
                                        onClick={toggleVideo}
                                        className="absolute right-4 bottom-4 rounded-full bg-white/90 p-3 shadow-lg backdrop-blur-sm transition-colors hover:bg-white"
                                    >
                                        {isVideoPlaying ? (
                                            <Pause className="h-5 w-5 text-gray-800" />
                                        ) : (
                                            <Play className="h-5 w-5 text-gray-800" />
                                        )}
                                    </button>
                                    {/* Floating badge */}
                                    <div className="absolute top-4 left-4 inline-flex items-center gap-2 rounded-full bg-white/90 px-3 py-1.5 text-sm font-medium text-gray-800 shadow-lg backdrop-blur-sm">
                                        <span className="relative flex h-2 w-2">
                                            <span className="absolute inline-flex h-full w-full animate-ping rounded-full bg-green-400 opacity-75"></span>
                                            <span className="relative inline-flex h-2 w-2 rounded-full bg-green-500"></span>
                                        </span>
                                        Carte en direct
                                    </div>
                                </div>
                                {/* Decorative elements */}
                                <div className="absolute -top-4 -right-4 -z-10 h-72 w-72 rounded-full bg-gradient-to-br from-indigo-200 to-violet-200 opacity-40 blur-3xl" />
                                <div className="absolute -bottom-4 -left-4 -z-10 h-72 w-72 rounded-full bg-gradient-to-br from-violet-200 to-pink-200 opacity-40 blur-3xl" />
                            </motion.div>
                        </div>
                    </div>
                </section>

                {/* Address Uses Section - Why You Need an Address */}
                <section id="usages" className="px-4 py-20 sm:px-6 lg:px-8">
                    <div className="mx-auto max-w-7xl">
                        {/* Section Header */}
                        <div className="mb-16 text-center">
                            <motion.div
                                initial={{ opacity: 0, y: 20 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.5 }}
                                viewport={{ once: true }}
                            >
                                <h2 className="mb-4 font-display text-3xl font-bold sm:text-4xl lg:text-5xl">
                                    À quoi sert une{' '}
                                    <span className="bg-gradient-to-r from-indigo-500 to-violet-500 bg-clip-text text-transparent">
                                        adresse fonctionnelle
                                    </span>{' '}
                                    ?
                                </h2>
                                <p className="mx-auto max-w-3xl text-lg text-gray-600">
                                    Au Cameroun, une adresse vérifiable est
                                    indispensable pour de nombreuses démarches
                                    du quotidien. Découvrez tous les domaines où
                                    SomeWhere App vous simplifie la vie.
                                </p>
                            </motion.div>
                        </div>

                        {/* Stats Banner */}
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.5, delay: 0.1 }}
                            viewport={{ once: true }}
                            className="mb-16"
                        >
                            <div className="grid grid-cols-2 gap-4 md:grid-cols-4">
                                <div className="rounded-2xl border border-gray-100 bg-white p-6 text-center shadow-sm transition-shadow hover:shadow-md">
                                    <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-100 to-violet-100">
                                        <Layers className="h-6 w-6 text-indigo-600" />
                                    </div>
                                    <div className="font-display text-3xl font-bold text-gray-900">
                                        12+
                                    </div>
                                    <div className="mt-1 text-sm text-gray-500">
                                        Domaines d'utilisation
                                    </div>
                                </div>
                                <div className="rounded-2xl border border-gray-100 bg-white p-6 text-center shadow-sm transition-shadow hover:shadow-md">
                                    <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-100 to-violet-100">
                                        <CheckCircle className="h-6 w-6 text-indigo-600" />
                                    </div>
                                    <div className="font-display text-3xl font-bold text-gray-900">
                                        48+
                                    </div>
                                    <div className="mt-1 text-sm text-gray-500">
                                        Cas d'usage
                                    </div>
                                </div>
                                <div className="rounded-2xl border border-gray-100 bg-white p-6 text-center shadow-sm transition-shadow hover:shadow-md">
                                    <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-100 to-violet-100">
                                        <Shield className="h-6 w-6 text-indigo-600" />
                                    </div>
                                    <div className="font-display text-3xl font-bold text-gray-900">
                                        100%
                                    </div>
                                    <div className="mt-1 text-sm text-gray-500">
                                        Fonctionnelle & vérifiable
                                    </div>
                                </div>
                                <div className="rounded-2xl border border-gray-100 bg-white p-6 text-center shadow-sm transition-shadow hover:shadow-md">
                                    <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-100 to-violet-100">
                                        <Zap className="h-6 w-6 text-indigo-600" />
                                    </div>
                                    <div className="font-display text-3xl font-bold text-gray-900">
                                        5 min
                                    </div>
                                    <div className="mt-1 text-sm text-gray-500">
                                        Pour l'obtenir
                                    </div>
                                </div>
                            </div>
                        </motion.div>

                        {/* Use Cases Carousel */}
                        <div className="relative">
                            {/* Navigation Arrows */}
                            <div className="absolute top-1/2 -left-4 z-10 hidden -translate-y-1/2 md:flex lg:-left-12">
                                <button
                                    onClick={() =>
                                        setUseCasePage(
                                            Math.max(0, useCasePage - 1),
                                        )
                                    }
                                    disabled={useCasePage === 0}
                                    className="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 shadow-sm transition-all hover:border-indigo-200 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30"
                                >
                                    <ChevronLeft className="h-5 w-5" />
                                </button>
                            </div>
                            <div className="absolute top-1/2 -right-4 z-10 hidden -translate-y-1/2 md:flex lg:-right-12">
                                <button
                                    onClick={() =>
                                        setUseCasePage(
                                            Math.min(
                                                Math.ceil(
                                                    addressUseCases.length / 3,
                                                ) - 1,
                                                useCasePage + 1,
                                            ),
                                        )
                                    }
                                    disabled={
                                        useCasePage >=
                                        Math.ceil(addressUseCases.length / 3) -
                                            1
                                    }
                                    className="flex h-10 w-10 items-center justify-center rounded-full border border-gray-200 bg-white text-gray-600 shadow-sm transition-all hover:border-indigo-200 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-30"
                                >
                                    <ChevronRight className="h-5 w-5" />
                                </button>
                            </div>

                            {/* Cards Container */}
                            <div className="overflow-hidden">
                                <motion.div
                                    className="flex gap-6"
                                    animate={{ x: `-${useCasePage * 100}%` }}
                                    transition={{
                                        type: 'spring',
                                        stiffness: 300,
                                        damping: 30,
                                    }}
                                >
                                    {Array.from({
                                        length: Math.ceil(
                                            addressUseCases.length / 3,
                                        ),
                                    }).map((_, pageIndex) => (
                                        <div
                                            key={pageIndex}
                                            className="flex min-w-full gap-6"
                                        >
                                            {addressUseCases
                                                .slice(
                                                    pageIndex * 3,
                                                    pageIndex * 3 + 3,
                                                )
                                                .map((category) => (
                                                    <div
                                                        key={category.category}
                                                        className="group flex-1 overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-sm transition-all hover:border-indigo-100 hover:shadow-lg"
                                                    >
                                                        {/* Category Header */}
                                                        <div className="border-b border-gray-100 p-5">
                                                            <div className="flex items-center gap-3">
                                                                <div className="flex h-12 w-12 items-center justify-center rounded-xl bg-gradient-to-br from-indigo-100 to-violet-100 transition-colors group-hover:from-indigo-200 group-hover:to-violet-200">
                                                                    <category.icon className="h-6 w-6 text-indigo-600" />
                                                                </div>
                                                                <div>
                                                                    <h3 className="text-lg font-semibold text-gray-900">
                                                                        {
                                                                            category.category
                                                                        }
                                                                    </h3>
                                                                    <p className="text-sm text-gray-500">
                                                                        {
                                                                            category.description
                                                                        }
                                                                    </p>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        {/* Use Cases List */}
                                                        <div className="p-5">
                                                            <ul className="space-y-3">
                                                                {category.useCases.map(
                                                                    (
                                                                        useCase,
                                                                        idx,
                                                                    ) => (
                                                                        <li
                                                                            key={
                                                                                idx
                                                                            }
                                                                            className="group/item flex items-start gap-3"
                                                                        >
                                                                            <div className="mt-0.5 flex h-5 w-5 flex-shrink-0 items-center justify-center rounded-full bg-indigo-100 transition-colors group-hover/item:bg-indigo-200">
                                                                                <CheckCircle className="h-3 w-3 text-indigo-600" />
                                                                            </div>
                                                                            <div>
                                                                                <p className="text-sm font-medium text-gray-900">
                                                                                    {
                                                                                        useCase.title
                                                                                    }
                                                                                </p>
                                                                                <p className="mt-0.5 text-xs text-gray-500">
                                                                                    {
                                                                                        useCase.description
                                                                                    }
                                                                                </p>
                                                                            </div>
                                                                        </li>
                                                                    ),
                                                                )}
                                                            </ul>
                                                        </div>
                                                    </div>
                                                ))}
                                        </div>
                                    ))}
                                </motion.div>
                            </div>

                            {/* Pagination Dots */}
                            <div className="mt-8 flex justify-center gap-2">
                                {Array.from({
                                    length: Math.ceil(
                                        addressUseCases.length / 3,
                                    ),
                                }).map((_, index) => (
                                    <button
                                        key={index}
                                        onClick={() => setUseCasePage(index)}
                                        className={`h-2.5 w-2.5 rounded-full transition-all ${
                                            index === useCasePage
                                                ? 'w-8 bg-indigo-500'
                                                : 'bg-gray-300 hover:bg-gray-400'
                                        }`}
                                    />
                                ))}
                            </div>

                            {/* Mobile Navigation */}
                            <div className="mt-6 flex justify-center gap-4 md:hidden">
                                <button
                                    onClick={() =>
                                        setUseCasePage(
                                            Math.max(0, useCasePage - 1),
                                        )
                                    }
                                    disabled={useCasePage === 0}
                                    className="flex items-center gap-2 rounded-lg bg-gray-100 px-4 py-2 font-medium text-gray-600 transition-all disabled:cursor-not-allowed disabled:opacity-30"
                                >
                                    <ChevronLeft className="h-4 w-4" />
                                    Précédent
                                </button>
                                <button
                                    onClick={() =>
                                        setUseCasePage(
                                            Math.min(
                                                Math.ceil(
                                                    addressUseCases.length / 3,
                                                ) - 1,
                                                useCasePage + 1,
                                            ),
                                        )
                                    }
                                    disabled={
                                        useCasePage >=
                                        Math.ceil(addressUseCases.length / 3) -
                                            1
                                    }
                                    className="flex items-center gap-2 rounded-lg bg-gray-100 px-4 py-2 font-medium text-gray-600 transition-all disabled:cursor-not-allowed disabled:opacity-30"
                                >
                                    Suivant
                                    <ChevronRight className="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    </div>
                </section>

                {/* Showcase Section */}
                <section
                    id="showcase"
                    className="bg-gradient-to-b from-gray-50 to-white px-4 py-20 sm:px-6 lg:px-8"
                >
                    <div className="mx-auto max-w-7xl">
                        <div className="mb-16 text-center">
                            <motion.div
                                initial={{ opacity: 0, y: 20 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.5 }}
                                viewport={{ once: true }}
                            >
                                <h2 className="mb-4 font-display text-3xl font-bold sm:text-4xl lg:text-5xl">
                                    Découvrez{' '}
                                    <span className="bg-gradient-to-r from-indigo-500 to-violet-500 bg-clip-text text-transparent">
                                        SomeWhere App
                                    </span>
                                </h2>
                                <p className="mx-auto max-w-2xl text-lg text-gray-600">
                                    Une plateforme complète pour gérer vos
                                    adresses et documents
                                </p>
                            </motion.div>
                        </div>

                        <div className="grid gap-8 md:grid-cols-2">
                            {showcaseItems.map((item, index) => (
                                <motion.div
                                    key={item.title}
                                    initial={{ opacity: 0, y: 20 }}
                                    whileInView={{ opacity: 1, y: 0 }}
                                    transition={{
                                        duration: 0.5,
                                        delay: index * 0.1,
                                    }}
                                    viewport={{ once: true }}
                                    className="group relative overflow-hidden rounded-2xl border border-gray-100 bg-white shadow-lg transition-all hover:shadow-xl"
                                >
                                    <div className="aspect-video overflow-hidden bg-gray-100">
                                        {item.type === 'video' ? (
                                            <video
                                                autoPlay
                                                loop
                                                muted
                                                playsInline
                                                className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                            >
                                                <source
                                                    src={item.media}
                                                    type="video/mp4"
                                                />
                                            </video>
                                        ) : (
                                            <img
                                                src={item.media}
                                                alt={item.title}
                                                className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                            />
                                        )}
                                    </div>
                                    <div className="p-6">
                                        <h3 className="mb-2 text-xl font-semibold">
                                            {item.title}
                                        </h3>
                                        <p className="text-gray-600">
                                            {item.description}
                                        </p>
                                    </div>
                                    {/* Gradient overlay on hover */}
                                    <div className="pointer-events-none absolute inset-0 bg-gradient-to-t from-indigo-500/8 to-transparent opacity-0 transition-opacity group-hover:opacity-100" />
                                </motion.div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Features Section */}
                <section id="features" className="px-4 py-20 sm:px-6 lg:px-8">
                    <div className="mx-auto max-w-7xl">
                        <div className="mb-16 text-center">
                            <motion.div
                                initial={{ opacity: 0, y: 20 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.5 }}
                                viewport={{ once: true }}
                            >
                                <h2 className="mb-4 font-display text-3xl font-bold sm:text-4xl">
                                    Tout ce dont vous avez besoin
                                </h2>
                                <p className="mx-auto max-w-2xl text-lg text-gray-600">
                                    Une solution complète pour simplifier votre
                                    adresse et obtenir des documents officiels.
                                </p>
                            </motion.div>
                        </div>

                        <div className="grid gap-8 md:grid-cols-2 lg:grid-cols-3">
                            {features.map((feature, index) => (
                                <motion.div
                                    key={feature.title}
                                    initial={{ opacity: 0, y: 20 }}
                                    whileInView={{ opacity: 1, y: 0 }}
                                    transition={{
                                        duration: 0.5,
                                        delay: index * 0.1,
                                    }}
                                    viewport={{ once: true }}
                                    className="group rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition-all hover:border-indigo-100 hover:shadow-lg"
                                >
                                    <div className="mb-5 flex h-14 w-14 items-center justify-center rounded-2xl bg-gradient-to-br from-indigo-100 to-violet-100 transition-colors group-hover:from-indigo-200 group-hover:to-violet-200">
                                        <feature.icon className="h-7 w-7 text-indigo-600" />
                                    </div>
                                    <h3 className="mb-2 text-lg font-semibold">
                                        {feature.title}
                                    </h3>
                                    <p className="text-gray-600">
                                        {feature.description}
                                    </p>
                                </motion.div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Pricing Section */}
                <section id="pricing" className="px-4 py-20 sm:px-6 lg:px-8">
                    <div className="mx-auto max-w-7xl">
                        <div className="mb-16 text-center">
                            <motion.div
                                initial={{ opacity: 0, y: 20 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.5 }}
                                viewport={{ once: true }}
                            >
                                <h2 className="mb-4 font-display text-3xl font-bold sm:text-4xl">
                                    Tarifs transparents
                                </h2>
                                <p className="mx-auto max-w-2xl text-lg text-gray-600">
                                    Payez uniquement pour ce dont vous avez
                                    besoin. Pas d'abonnement obligatoire pour
                                    les particuliers.
                                </p>
                            </motion.div>
                        </div>

                        <div className="mx-auto grid max-w-4xl items-stretch gap-8 md:grid-cols-2">
                            {pricing.map((plan, index) => (
                                <motion.div
                                    key={plan.name}
                                    initial={{ opacity: 0, y: 20 }}
                                    whileInView={{ opacity: 1, y: 0 }}
                                    transition={{
                                        duration: 0.5,
                                        delay: index * 0.1,
                                    }}
                                    viewport={{ once: true }}
                                    className={`relative flex flex-col rounded-2xl bg-white p-8 ${
                                        plan.popular
                                            ? 'shadow-xl ring-2 shadow-indigo-500/10 ring-indigo-500'
                                            : 'border border-gray-100 shadow-sm'
                                    }`}
                                >
                                    {plan.popular && (
                                        <div className="absolute -top-4 left-1/2 -translate-x-1/2">
                                            <span className="inline-flex items-center rounded-full bg-gradient-to-r from-indigo-500 to-violet-500 px-4 py-1.5 text-xs font-semibold text-white shadow-md">
                                                Populaire
                                            </span>
                                        </div>
                                    )}
                                    <div className="mb-6">
                                        <h3 className="mb-2 text-xl font-semibold">
                                            {plan.name}
                                        </h3>
                                        <p className="text-sm text-gray-600">
                                            {plan.description}
                                        </p>
                                    </div>
                                    <div className="mb-6">
                                        {plan.pricePrefix && (
                                            <span className="text-sm text-gray-500">
                                                {plan.pricePrefix}{' '}
                                            </span>
                                        )}
                                        {plan.price === '0' ? (
                                            <span className="font-display text-4xl font-bold text-green-600">
                                                Gratuit
                                            </span>
                                        ) : (
                                            <>
                                                <span className="font-display text-4xl font-bold">
                                                    {plan.price}
                                                </span>
                                                <span className="text-gray-600">
                                                    {' '}
                                                    FCFA{plan.period || ''}
                                                </span>
                                            </>
                                        )}
                                    </div>
                                    <ul className="mb-6 space-y-3">
                                        {plan.features.map((feature) => (
                                            <li
                                                key={feature}
                                                className="flex items-start gap-3"
                                            >
                                                <CheckCircle className="mt-0.5 h-5 w-5 shrink-0 text-green-500" />
                                                <span className="text-sm text-gray-600">
                                                    {feature}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                    {plan.extras && (
                                        <div className="mb-6 border-t border-gray-100 pt-4">
                                            <p className="mb-3 text-xs font-medium text-gray-500 uppercase">
                                                Documents additionnels
                                            </p>
                                            <ul className="space-y-2">
                                                {plan.extras.map(
                                                    (extra: {
                                                        name: string;
                                                        price: string;
                                                    }) => (
                                                        <li
                                                            key={extra.name}
                                                            className="flex items-center justify-between text-sm"
                                                        >
                                                            <span className="text-gray-700">
                                                                {extra.name}
                                                            </span>
                                                            <span className="font-semibold text-indigo-600">
                                                                {extra.price}
                                                            </span>
                                                        </li>
                                                    ),
                                                )}
                                            </ul>
                                        </div>
                                    )}
                                    <div className="mt-auto">
                                        {plan.isEnterprise ? (
                                            <a
                                                href="mailto:contact@somewhere-app.com?subject=Demande de démo entreprise"
                                                className="inline-flex w-full items-center justify-center rounded-xl border-2 border-indigo-500 px-4 py-3 text-sm font-medium text-indigo-600 transition-all hover:bg-indigo-50"
                                            >
                                                Demander une démo
                                            </a>
                                        ) : (
                                            <Link
                                                href={register()}
                                                className={`inline-flex w-full items-center justify-center rounded-xl px-4 py-3 text-sm font-medium transition-all ${
                                                    plan.popular
                                                        ? 'hover:from-indigo-550 hover:to-violet-550 bg-gradient-to-r from-indigo-500 to-violet-500 text-white shadow-sm shadow-indigo-500/15'
                                                        : 'bg-gray-100 text-gray-900 hover:bg-gray-200'
                                                }`}
                                            >
                                                Commencer
                                            </Link>
                                        )}
                                    </div>
                                </motion.div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* Testimonials Section */}
                <section className="bg-gradient-to-b from-gray-50 to-white px-4 py-20 sm:px-6 lg:px-8">
                    <div className="mx-auto max-w-7xl">
                        <div className="mb-16 text-center">
                            <motion.div
                                initial={{ opacity: 0, y: 20 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.5 }}
                                viewport={{ once: true }}
                            >
                                <h2 className="mb-4 font-display text-3xl font-bold sm:text-4xl">
                                    Ce que disent nos utilisateurs
                                </h2>
                            </motion.div>
                        </div>

                        <div className="grid gap-8 md:grid-cols-3">
                            {testimonials.map((testimonial, index) => (
                                <motion.div
                                    key={testimonial.name}
                                    initial={{ opacity: 0, y: 20 }}
                                    whileInView={{ opacity: 1, y: 0 }}
                                    transition={{
                                        duration: 0.5,
                                        delay: index * 0.1,
                                    }}
                                    viewport={{ once: true }}
                                    className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm"
                                >
                                    <div className="mb-4 flex gap-1">
                                        {[...Array(testimonial.rating)].map(
                                            (_, i) => (
                                                <Star
                                                    key={i}
                                                    className="h-5 w-5 fill-yellow-400 text-yellow-400"
                                                />
                                            ),
                                        )}
                                    </div>
                                    <p className="mb-6 text-lg text-gray-700">
                                        "{testimonial.content}"
                                    </p>
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-12 w-12 items-center justify-center rounded-full bg-gradient-to-br from-indigo-100 to-violet-100">
                                            <span className="font-semibold text-indigo-600">
                                                {testimonial.name.charAt(0)}
                                            </span>
                                        </div>
                                        <div>
                                            <p className="font-semibold">
                                                {testimonial.name}
                                            </p>
                                            <p className="text-sm text-gray-500">
                                                {testimonial.role}
                                            </p>
                                        </div>
                                    </div>
                                </motion.div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* FAQ Section */}
                <section id="faq" className="px-4 py-20 sm:px-6 lg:px-8">
                    <div className="mx-auto max-w-3xl">
                        <div className="mb-16 text-center">
                            <motion.div
                                initial={{ opacity: 0, y: 20 }}
                                whileInView={{ opacity: 1, y: 0 }}
                                transition={{ duration: 0.5 }}
                                viewport={{ once: true }}
                            >
                                <h2 className="mb-4 font-display text-3xl font-bold sm:text-4xl">
                                    Questions fréquentes
                                </h2>
                            </motion.div>
                        </div>

                        <div className="space-y-4">
                            {faqs.map((faq, index) => (
                                <motion.div
                                    key={index}
                                    initial={{ opacity: 0, y: 20 }}
                                    whileInView={{ opacity: 1, y: 0 }}
                                    transition={{
                                        duration: 0.5,
                                        delay: index * 0.05,
                                    }}
                                    viewport={{ once: true }}
                                    className="overflow-hidden rounded-xl border border-gray-100 bg-white shadow-sm"
                                >
                                    <button
                                        onClick={() =>
                                            setOpenFaq(
                                                openFaq === index
                                                    ? null
                                                    : index,
                                            )
                                        }
                                        className="flex w-full items-center justify-between px-6 py-5 text-left transition-colors hover:bg-gray-50"
                                    >
                                        <span className="text-lg font-medium">
                                            {faq.question}
                                        </span>
                                        <ChevronDown
                                            className={`h-5 w-5 text-gray-500 transition-transform ${
                                                openFaq === index
                                                    ? 'rotate-180'
                                                    : ''
                                            }`}
                                        />
                                    </button>
                                    {openFaq === index && (
                                        <div className="px-6 pb-5">
                                            <p className="text-gray-600">
                                                {faq.answer}
                                            </p>
                                        </div>
                                    )}
                                </motion.div>
                            ))}
                        </div>
                    </div>
                </section>

                {/* CTA Section */}
                <section className="px-4 py-20 sm:px-6 lg:px-8">
                    <div className="mx-auto max-w-4xl">
                        <motion.div
                            initial={{ opacity: 0, y: 20 }}
                            whileInView={{ opacity: 1, y: 0 }}
                            transition={{ duration: 0.5 }}
                            viewport={{ once: true }}
                            className="relative overflow-hidden rounded-3xl bg-gradient-to-br from-indigo-500 via-violet-500 to-purple-500 p-8 text-center text-white md:p-12"
                        >
                            {/* Background decoration */}
                            <div className="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHZpZXdCb3g9IjAgMCA2MCA2MCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48ZyBmaWxsPSJub25lIiBmaWxsLXJ1bGU9ImV2ZW5vZGQiPjxnIGZpbGw9IiNmZmYiIGZpbGwtb3BhY2l0eT0iMC4xIj48cGF0aCBkPSJNMzYgMzRjMC0yLjIwOS0xLjc5MS00LTQtNHMtNCAxLjc5MS00IDQgMS43OTEgNCA0IDQgNC0xLjc5MSA0LTR6bTAtMTZjMC0yLjIwOS0xLjc5MS00LTQtNHMtNCAxLjc5MS00IDQgMS43OTEgNCA0IDQgNC0xLjc5MSA0LTR6Ii8+PC9nPjwvZz48L3N2Zz4=')] opacity-30" />

                            <div className="relative">
                                <h2 className="mb-4 font-display text-3xl font-bold sm:text-4xl lg:text-5xl">
                                    Prêt à simplifier votre adresse ?
                                </h2>
                                <p className="mx-auto mb-8 max-w-2xl text-lg text-white/80">
                                    Rejoignez des milliers de camerounais qui
                                    utilisent SomeWhere App pour leurs documents
                                    d'adresse.
                                </p>
                                <div className="flex flex-col justify-center gap-4 sm:flex-row">
                                    <Link
                                        href={register()}
                                        className="inline-flex items-center justify-center rounded-xl bg-white px-8 py-4 text-base font-medium text-indigo-600 shadow-xl transition-colors hover:bg-gray-100"
                                    >
                                        Créer mon compte gratuitement
                                        <ChevronRight className="ml-2 h-5 w-5" />
                                    </Link>
                                </div>
                            </div>
                        </motion.div>
                    </div>
                </section>

                {/* Footer */}
                <footer className="bg-gray-900 px-4 py-16 text-white sm:px-6 lg:px-8">
                    <div className="mx-auto max-w-7xl">
                        <div className="mb-12 grid gap-8 md:grid-cols-4">
                            <div>
                                <div className="mb-4 flex items-center gap-2">
                                    <img
                                        src="/images/icon.png"
                                        alt="SomeWhere App"
                                        className="h-10 w-10 rounded-xl"
                                        onError={(e) => {
                                            e.currentTarget.style.display =
                                                'none';
                                        }}
                                    />
                                    <span className="font-display text-xl font-bold">
                                        SomeWhere App
                                    </span>
                                </div>
                                <p className="mb-4 text-sm text-gray-400">
                                    La solution d'adressage intelligent au
                                    Cameroun.
                                </p>
                                {/* App Store links in footer */}
                                <div className="flex gap-2">
                                    <a
                                        href="https://play.google.com/store/apps/details?id=com.somewhere.app"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="opacity-70 transition-opacity hover:opacity-100"
                                    >
                                        <img
                                            src="https://play.google.com/intl/en_us/badges/static/images/badges/fr_badge_web_generic.png"
                                            alt="Google Play"
                                            className="h-10"
                                        />
                                    </a>
                                    <a
                                        href="https://apps.apple.com/app/somewhere-app"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        className="opacity-70 transition-opacity hover:opacity-100"
                                    >
                                        <img
                                            src="https://developer.apple.com/assets/elements/badges/download-on-the-app-store.svg"
                                            alt="App Store"
                                            className="h-10"
                                        />
                                    </a>
                                </div>
                            </div>
                            <div>
                                <h4 className="mb-4 font-semibold">Produit</h4>
                                <ul className="space-y-2 text-sm text-gray-400">
                                    <li>
                                        <a
                                            href="#features"
                                            className="transition-colors hover:text-white"
                                        >
                                            Fonctionnalités
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            href="#pricing"
                                            className="transition-colors hover:text-white"
                                        >
                                            Tarifs
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            href="#"
                                            className="transition-colors hover:text-white"
                                        >
                                            Entreprises
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            href="#"
                                            className="transition-colors hover:text-white"
                                        >
                                            API
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <h4 className="mb-4 font-semibold">Support</h4>
                                <ul className="space-y-2 text-sm text-gray-400">
                                    <li>
                                        <a
                                            href="#faq"
                                            className="transition-colors hover:text-white"
                                        >
                                            FAQ
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            href="#"
                                            className="transition-colors hover:text-white"
                                        >
                                            Centre d'aide
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            href="#"
                                            className="transition-colors hover:text-white"
                                        >
                                            Contact
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <div>
                                <h4 className="mb-4 font-semibold">Légal</h4>
                                <ul className="space-y-2 text-sm text-gray-400">
                                    <li>
                                        <a
                                            href="#"
                                            className="transition-colors hover:text-white"
                                        >
                                            Conditions d'utilisation
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            href="#"
                                            className="transition-colors hover:text-white"
                                        >
                                            Politique de confidentialité
                                        </a>
                                    </li>
                                    <li>
                                        <a
                                            href="#"
                                            className="transition-colors hover:text-white"
                                        >
                                            Mentions légales
                                        </a>
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <div className="flex justify-center border-t border-gray-800 pt-8">
                            <p className="text-sm text-gray-400">
                                © {new Date().getFullYear()} Ket-Up SARL. Tous
                                droits réservés.
                            </p>
                        </div>
                    </div>
                </footer>
            </div>
        </>
    );
}
