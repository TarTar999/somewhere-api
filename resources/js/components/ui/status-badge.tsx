import * as React from 'react';
import { cn } from '@/lib/utils';
import { Check, X, Clock, AlertTriangle, Info, Loader2 } from 'lucide-react';

export type StatusBadgeVariant = 'success' | 'warning' | 'error' | 'info' | 'pending' | 'default';

export interface StatusBadgeProps extends React.HTMLAttributes<HTMLSpanElement> {
    variant?: StatusBadgeVariant;
    size?: 'sm' | 'md' | 'lg';
    showIcon?: boolean;
    pulse?: boolean;
    label: string;
}

const variantStyles: Record<StatusBadgeVariant, string> = {
    success: 'status-badge-success',
    warning: 'status-badge-warning',
    error: 'status-badge-error',
    info: 'status-badge-info',
    pending: 'status-badge bg-muted text-muted-foreground',
    default: 'status-badge bg-secondary text-secondary-foreground',
};

const sizeStyles: Record<string, string> = {
    sm: 'text-xs px-1.5 py-0.5',
    md: 'text-xs px-2 py-0.5',
    lg: 'text-sm px-2.5 py-1',
};

const iconSize: Record<string, string> = {
    sm: 'h-3 w-3',
    md: 'h-3.5 w-3.5',
    lg: 'h-4 w-4',
};

const StatusBadge = React.forwardRef<HTMLSpanElement, StatusBadgeProps>(
    (
        {
            className,
            variant = 'default',
            size = 'md',
            showIcon = true,
            pulse = false,
            label,
            ...props
        },
        ref
    ) => {
        const IconComponent = React.useMemo(() => {
            switch (variant) {
                case 'success':
                    return Check;
                case 'warning':
                    return AlertTriangle;
                case 'error':
                    return X;
                case 'info':
                    return Info;
                case 'pending':
                    return Clock;
                default:
                    return null;
            }
        }, [variant]);

        return (
            <span
                ref={ref}
                className={cn(
                    variantStyles[variant],
                    sizeStyles[size],
                    pulse && variant === 'pending' && 'animate-pulse',
                    className
                )}
                {...props}
            >
                {showIcon && IconComponent && (
                    <IconComponent className={cn(iconSize[size], '-ml-0.5')} />
                )}
                {label}
            </span>
        );
    }
);

StatusBadge.displayName = 'StatusBadge';

// DocumentStatusBadge - Pre-configured for document statuses
interface DocumentStatusBadgeProps extends Omit<StatusBadgeProps, 'variant' | 'label'> {
    status: 'pending' | 'approved' | 'rejected' | 'expired' | 'active';
}

const documentStatusConfig: Record<
    string,
    { variant: StatusBadgeVariant; label: string }
> = {
    pending: { variant: 'pending', label: 'En attente' },
    approved: { variant: 'success', label: 'Approuvé' },
    rejected: { variant: 'error', label: 'Rejeté' },
    expired: { variant: 'warning', label: 'Expiré' },
    active: { variant: 'success', label: 'Actif' },
};

const DocumentStatusBadge = React.forwardRef<HTMLSpanElement, DocumentStatusBadgeProps>(
    ({ status, ...props }, ref) => {
        const config = documentStatusConfig[status] || documentStatusConfig.pending;
        return (
            <StatusBadge
                ref={ref}
                variant={config.variant}
                label={config.label}
                {...props}
            />
        );
    }
);

DocumentStatusBadge.displayName = 'DocumentStatusBadge';

// VerificationStatusBadge - For KYC verification
interface VerificationStatusBadgeProps extends Omit<StatusBadgeProps, 'variant' | 'label'> {
    status: 'not_started' | 'in_progress' | 'pending_review' | 'approved' | 'rejected';
}

const verificationStatusConfig: Record<
    string,
    { variant: StatusBadgeVariant; label: string }
> = {
    not_started: { variant: 'default', label: 'Non démarré' },
    in_progress: { variant: 'info', label: 'En cours' },
    pending_review: { variant: 'pending', label: 'En révision' },
    approved: { variant: 'success', label: 'Vérifié' },
    rejected: { variant: 'error', label: 'Rejeté' },
};

const VerificationStatusBadge = React.forwardRef<HTMLSpanElement, VerificationStatusBadgeProps>(
    ({ status, ...props }, ref) => {
        const config = verificationStatusConfig[status] || verificationStatusConfig.not_started;
        return (
            <StatusBadge
                ref={ref}
                variant={config.variant}
                label={config.label}
                pulse={status === 'pending_review'}
                {...props}
            />
        );
    }
);

VerificationStatusBadge.displayName = 'VerificationStatusBadge';

// SubscriptionStatusBadge - For company subscriptions
interface SubscriptionStatusBadgeProps extends Omit<StatusBadgeProps, 'variant' | 'label'> {
    status: 'active' | 'expired' | 'cancelled' | 'trial' | 'past_due';
}

const subscriptionStatusConfig: Record<
    string,
    { variant: StatusBadgeVariant; label: string }
> = {
    active: { variant: 'success', label: 'Actif' },
    expired: { variant: 'error', label: 'Expiré' },
    cancelled: { variant: 'default', label: 'Annulé' },
    trial: { variant: 'info', label: 'Essai' },
    past_due: { variant: 'warning', label: 'Paiement en retard' },
};

const SubscriptionStatusBadge = React.forwardRef<HTMLSpanElement, SubscriptionStatusBadgeProps>(
    ({ status, ...props }, ref) => {
        const config = subscriptionStatusConfig[status] || subscriptionStatusConfig.active;
        return (
            <StatusBadge
                ref={ref}
                variant={config.variant}
                label={config.label}
                {...props}
            />
        );
    }
);

SubscriptionStatusBadge.displayName = 'SubscriptionStatusBadge';

// LoadingBadge - Shows loading state
interface LoadingBadgeProps extends Omit<StatusBadgeProps, 'variant' | 'label' | 'showIcon'> {
    label?: string;
}

const LoadingBadge = React.forwardRef<HTMLSpanElement, LoadingBadgeProps>(
    ({ label = 'Chargement...', size = 'md', className, ...props }, ref) => {
        return (
            <span
                ref={ref}
                className={cn(
                    'status-badge bg-muted text-muted-foreground',
                    sizeStyles[size],
                    className
                )}
                {...props}
            >
                <Loader2 className={cn(iconSize[size], '-ml-0.5 animate-spin')} />
                {label}
            </span>
        );
    }
);

LoadingBadge.displayName = 'LoadingBadge';

export {
    StatusBadge,
    DocumentStatusBadge,
    VerificationStatusBadge,
    SubscriptionStatusBadge,
    LoadingBadge,
};
