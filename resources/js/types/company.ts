export type CompanyRole = 'admin' | 'manager' | 'member';
export type CompanyStatus = 'pending' | 'active' | 'suspended' | 'cancelled';
export type SubscriptionStatus = 'active' | 'past_due' | 'cancelled' | 'expired';
export type MemberStatus = 'pending' | 'active' | 'suspended';

export interface Company {
    id: number;
    name: string;
    slug: string;
    email: string;
    phone?: string;
    logo?: string;
    legalName?: string;
    registrationNumber?: string;
    taxId?: string;
    description?: string;
    address?: string;
    city?: string;
    country?: string;
    status: CompanyStatus;
    membersCount: number;
    createdAt: string;
    activatedAt?: string;
    subscription?: CompanySubscription;
}

export interface CompanyMember {
    id: number;
    name: string;
    email: string;
    phone?: string;
    avatar?: string;
    role: CompanyRole;
    status: MemberStatus;
    joinedAt?: string;
}

export interface CompanySubscription {
    id: number;
    plan: string;
    planName: string;
    price: number;
    priceFormatted: string;
    status: SubscriptionStatus;
    maxMembers: number;
    documentsPerMonth: number;
    periodStart: string;
    periodEnd: string;
    daysUntilRenewal: number;
    isCancelled?: boolean;
}

export interface SubscriptionPlan {
    code: string;
    name: string;
    price: number;
    priceFormatted: string;
    maxMembers: number;
    documentsPerMonth: number;
    features: string[];
}

export interface CompanyStats {
    totalMembers: number;
    maxMembers: number;
    totalAddresses: number;
    documentsThisMonth: number;
    documentsLimit: number;
    documentsRemaining: number;
}

export interface CompanyUsage {
    hasSubscription: boolean;
    plan?: string;
    status?: SubscriptionStatus;
    periodStart?: string;
    periodEnd?: string;
    daysUntilRenewal?: number;
    members?: {
        used: number;
        limit: number;
    };
    documents?: {
        used: number;
        limit: number;
        remaining: number;
    };
}

export interface CompanyDocument {
    id: number;
    documentNumber: string;
    documentType: string;
    documentTypeLabel: string;
    status: string;
    isExpired: boolean;
    expiresAt?: string;
    createdAt: string;
    address?: string;
    createdBy?: string;
}

export interface CompanyAddress {
    id: number;
    swAddress: string;
    latitude: number;
    longitude: number;
    streetNumber?: string;
    streetName?: string;
    lieuDit?: string;
    quarter?: string;
    subQuarter?: string;
    commune?: string;
    houseType?: string;
    homeStatus?: string;
    description?: string;
    verificationStatus: string;
    isNonHabitation?: boolean;
    nonHabitationType?: string;
    owner: {
        id: number;
        name: string;
        email?: string;
    };
    createdAt?: string;
}

export interface CompanyPayment {
    id: number;
    transactionId: string;
    amount: number;
    amountFormatted: string;
    status: string;
    statusLabel: string;
    paidAt?: string;
    createdAt: string;
}

export interface Invitation {
    token: string;
    company: {
        id: number;
        name: string;
        logo?: string;
    };
    role: CompanyRole;
    expiresAt: string;
}
