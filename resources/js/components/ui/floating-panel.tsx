import * as React from 'react';
import { cn } from '@/lib/utils';
import { motion, AnimatePresence, type Variants } from 'framer-motion';
import { X } from 'lucide-react';

const panelVariants: Variants = {
    hidden: { opacity: 0, scale: 0.95, y: 10 },
    visible: {
        opacity: 1,
        scale: 1,
        y: 0,
        transition: { type: 'spring', stiffness: 300, damping: 25 },
    },
    exit: {
        opacity: 0,
        scale: 0.95,
        y: 10,
        transition: { duration: 0.15 },
    },
};

export interface FloatingPanelProps extends React.HTMLAttributes<HTMLDivElement> {
    position?: 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right' | 'center';
    size?: 'sm' | 'md' | 'lg' | 'auto';
    closable?: boolean;
    onClose?: () => void;
    animate?: boolean;
    visible?: boolean;
}

const positionStyles: Record<string, string> = {
    'top-left': 'top-20 left-4',
    'top-right': 'top-20 right-4',
    'bottom-left': 'bottom-4 left-4',
    'bottom-right': 'bottom-4 right-4',
    center: 'top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2',
};

const sizeStyles: Record<string, string> = {
    sm: 'w-64',
    md: 'w-80',
    lg: 'w-96',
    auto: 'w-auto',
};

const FloatingPanel = React.forwardRef<HTMLDivElement, FloatingPanelProps>(
    (
        {
            className,
            children,
            position = 'top-left',
            size = 'md',
            closable = false,
            onClose,
            animate = true,
            visible = true,
            ...props
        },
        ref
    ) => {
        const content = (
            <div
                ref={ref}
                className={cn(
                    'fixed z-40 floating-card',
                    positionStyles[position],
                    sizeStyles[size],
                    className
                )}
                {...props}
            >
                {closable && (
                    <button
                        onClick={onClose}
                        className="absolute top-2 right-2 p-1.5 rounded-full hover:bg-muted transition-colors"
                        aria-label="Close panel"
                    >
                        <X className="h-4 w-4 text-muted-foreground" />
                    </button>
                )}
                {children}
            </div>
        );

        if (!animate) {
            return visible ? content : null;
        }

        return (
            <AnimatePresence>
                {visible && (
                    <motion.div
                        initial="hidden"
                        animate="visible"
                        exit="exit"
                        variants={panelVariants}
                    >
                        {content}
                    </motion.div>
                )}
            </AnimatePresence>
        );
    }
);

FloatingPanel.displayName = 'FloatingPanel';

// FloatingPanelHeader
interface FloatingPanelHeaderProps extends React.HTMLAttributes<HTMLDivElement> {
    title: string;
    subtitle?: string;
    icon?: React.ReactNode;
}

const FloatingPanelHeader = React.forwardRef<HTMLDivElement, FloatingPanelHeaderProps>(
    ({ className, title, subtitle, icon, ...props }, ref) => {
        return (
            <div
                ref={ref}
                className={cn('flex items-start gap-3 mb-4', className)}
                {...props}
            >
                {icon && (
                    <div className="flex-shrink-0 p-2 rounded-lg bg-primary/10 text-primary">
                        {icon}
                    </div>
                )}
                <div className="flex-1 min-w-0">
                    <h3 className="font-semibold text-foreground truncate">{title}</h3>
                    {subtitle && (
                        <p className="text-sm text-muted-foreground truncate">{subtitle}</p>
                    )}
                </div>
            </div>
        );
    }
);

FloatingPanelHeader.displayName = 'FloatingPanelHeader';

// FloatingPanelContent
const FloatingPanelContent = React.forwardRef<
    HTMLDivElement,
    React.HTMLAttributes<HTMLDivElement>
>(({ className, ...props }, ref) => {
    return <div ref={ref} className={cn('space-y-3', className)} {...props} />;
});

FloatingPanelContent.displayName = 'FloatingPanelContent';

// FloatingPanelFooter
const FloatingPanelFooter = React.forwardRef<
    HTMLDivElement,
    React.HTMLAttributes<HTMLDivElement>
>(({ className, ...props }, ref) => {
    return (
        <div
            ref={ref}
            className={cn(
                'flex items-center gap-2 mt-4 pt-4 border-t border-border',
                className
            )}
            {...props}
        />
    );
});

FloatingPanelFooter.displayName = 'FloatingPanelFooter';

export {
    FloatingPanel,
    FloatingPanelHeader,
    FloatingPanelContent,
    FloatingPanelFooter,
};
