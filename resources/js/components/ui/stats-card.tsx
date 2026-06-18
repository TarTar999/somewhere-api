import * as React from 'react';
import { cn } from '@/lib/utils';
import { motion } from 'framer-motion';
import { TrendingUp, TrendingDown, Minus } from 'lucide-react';

export interface StatsCardProps extends React.HTMLAttributes<HTMLDivElement> {
    title: string;
    value: string | number;
    subtitle?: string;
    icon?: React.ReactNode;
    trend?: {
        value: number;
        label?: string;
    };
    variant?: 'default' | 'success' | 'warning' | 'error' | 'info';
    size?: 'sm' | 'md' | 'lg';
    loading?: boolean;
}

const variantStyles: Record<string, string> = {
    default: '',
    success: 'border-success/20 bg-success/5',
    warning: 'border-warning/20 bg-warning/5',
    error: 'border-destructive/20 bg-destructive/5',
    info: 'border-info/20 bg-info/5',
};

const sizeStyles: Record<string, { wrapper: string; value: string; title: string }> = {
    sm: {
        wrapper: 'p-3',
        value: 'text-xl',
        title: 'text-xs',
    },
    md: {
        wrapper: 'p-4',
        value: 'text-2xl',
        title: 'text-sm',
    },
    lg: {
        wrapper: 'p-5',
        value: 'text-3xl',
        title: 'text-sm',
    },
};

const StatsCard = React.forwardRef<HTMLDivElement, StatsCardProps>(
    (
        {
            className,
            title,
            value,
            subtitle,
            icon,
            trend,
            variant = 'default',
            size = 'md',
            loading = false,
            ...props
        },
        ref
    ) => {
        const styles = sizeStyles[size];
        const trendDirection = trend ? (trend.value > 0 ? 'up' : trend.value < 0 ? 'down' : 'neutral') : null;

        if (loading) {
            return (
                <div
                    ref={ref}
                    className={cn(
                        'stats-card',
                        variantStyles[variant],
                        styles.wrapper,
                        className
                    )}
                    {...props}
                >
                    <div className="flex items-start justify-between gap-3">
                        <div className="flex-1 space-y-2">
                            <div className="skeleton h-4 w-20" />
                            <div className="skeleton h-7 w-16" />
                        </div>
                        {icon && <div className="skeleton-avatar h-8 w-8" />}
                    </div>
                </div>
            );
        }

        return (
            <motion.div
                ref={ref}
                initial={{ opacity: 0, y: 10 }}
                animate={{ opacity: 1, y: 0 }}
                transition={{ duration: 0.2 }}
                className={cn(
                    'stats-card',
                    variantStyles[variant],
                    styles.wrapper,
                    className
                )}
                {...props}
            >
                <div className="flex items-start justify-between gap-3">
                    <div className="flex-1 min-w-0">
                        <p className={cn('text-muted-foreground font-medium', styles.title)}>
                            {title}
                        </p>
                        <p className={cn('font-bold font-display tracking-tight text-foreground', styles.value)}>
                            {typeof value === 'number' ? value.toLocaleString('fr-FR') : value}
                        </p>
                        {subtitle && (
                            <p className="text-xs text-muted-foreground mt-1">{subtitle}</p>
                        )}
                        {trend && (
                            <div className="flex items-center gap-1 mt-2">
                                <span
                                    className={cn(
                                        'inline-flex items-center gap-0.5 text-xs font-medium',
                                        trendDirection === 'up' && 'text-success',
                                        trendDirection === 'down' && 'text-destructive',
                                        trendDirection === 'neutral' && 'text-muted-foreground'
                                    )}
                                >
                                    {trendDirection === 'up' && <TrendingUp className="h-3 w-3" />}
                                    {trendDirection === 'down' && <TrendingDown className="h-3 w-3" />}
                                    {trendDirection === 'neutral' && <Minus className="h-3 w-3" />}
                                    {trend.value > 0 ? '+' : ''}
                                    {trend.value}%
                                </span>
                                {trend.label && (
                                    <span className="text-xs text-muted-foreground">
                                        {trend.label}
                                    </span>
                                )}
                            </div>
                        )}
                    </div>
                    {icon && (
                        <div
                            className={cn(
                                'flex-shrink-0 p-2 rounded-lg',
                                variant === 'default' && 'bg-primary/10 text-primary',
                                variant === 'success' && 'bg-success/10 text-success',
                                variant === 'warning' && 'bg-warning/10 text-warning',
                                variant === 'error' && 'bg-destructive/10 text-destructive',
                                variant === 'info' && 'bg-info/10 text-info'
                            )}
                        >
                            {icon}
                        </div>
                    )}
                </div>
            </motion.div>
        );
    }
);

StatsCard.displayName = 'StatsCard';

// StatsCardGrid - Helper for laying out stats cards
interface StatsCardGridProps extends React.HTMLAttributes<HTMLDivElement> {
    columns?: 1 | 2 | 3 | 4;
}

const StatsCardGrid = React.forwardRef<HTMLDivElement, StatsCardGridProps>(
    ({ className, columns = 4, ...props }, ref) => {
        const gridStyles: Record<number, string> = {
            1: 'grid-cols-1',
            2: 'grid-cols-1 sm:grid-cols-2',
            3: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
            4: 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-4',
        };

        return (
            <div
                ref={ref}
                className={cn('grid gap-4', gridStyles[columns], className)}
                {...props}
            />
        );
    }
);

StatsCardGrid.displayName = 'StatsCardGrid';

export { StatsCard, StatsCardGrid };
