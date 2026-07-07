import * as React from 'react';
import { cn } from '@/lib/utils';
import { motion, AnimatePresence } from 'framer-motion';
import { X, ChevronLeft } from 'lucide-react';

export interface SideDrawerProps {
    children: React.ReactNode;
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
    side?: 'left' | 'right';
    size?: 'sm' | 'md' | 'lg' | 'xl' | 'full';
    title?: string;
    description?: string;
    showOverlay?: boolean;
    closeOnOverlayClick?: boolean;
    showCloseButton?: boolean;
    showBackButton?: boolean;
    onBack?: () => void;
    className?: string;
    headerClassName?: string;
    contentClassName?: string;
    footer?: React.ReactNode;
}

const sizeStyles: Record<string, string> = {
    sm: 'max-w-sm',
    md: 'max-w-md',
    lg: 'max-w-lg',
    xl: 'max-w-xl',
    full: 'max-w-full',
};

const SideDrawer = React.forwardRef<HTMLDivElement, SideDrawerProps>(
    (
        {
            children,
            open = false,
            onOpenChange,
            side = 'right',
            size = 'md',
            title,
            description,
            showOverlay = true,
            closeOnOverlayClick = true,
            showCloseButton = true,
            showBackButton = false,
            onBack,
            className,
            headerClassName,
            contentClassName,
            footer,
        },
        ref
    ) => {
        const handleClose = () => {
            onOpenChange?.(false);
        };

        const handleOverlayClick = () => {
            if (closeOnOverlayClick) {
                handleClose();
            }
        };

        // Handle escape key
        React.useEffect(() => {
            const handleEscape = (e: KeyboardEvent) => {
                if (e.key === 'Escape' && open) {
                    handleClose();
                }
            };

            document.addEventListener('keydown', handleEscape);
            return () => document.removeEventListener('keydown', handleEscape);
        }, [open]);

        // Prevent body scroll when open
        React.useEffect(() => {
            if (open) {
                document.body.style.overflow = 'hidden';
            } else {
                document.body.style.overflow = '';
            }
            return () => {
                document.body.style.overflow = '';
            };
        }, [open]);

        const slideVariants = {
            hidden: {
                x: side === 'right' ? '100%' : '-100%',
            },
            visible: {
                x: 0,
                transition: { type: 'spring', damping: 30, stiffness: 300 },
            },
            exit: {
                x: side === 'right' ? '100%' : '-100%',
                transition: { duration: 0.2 },
            },
        };

        return (
            <AnimatePresence>
                {open && (
                    <>
                        {/* Overlay */}
                        {showOverlay && (
                            <motion.div
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                exit={{ opacity: 0 }}
                                transition={{ duration: 0.2 }}
                                className="fixed inset-0 bg-black/50 z-50"
                                onClick={handleOverlayClick}
                            />
                        )}

                        {/* Drawer */}
                        <motion.div
                            ref={ref}
                            initial="hidden"
                            animate="visible"
                            exit="exit"
                            variants={slideVariants}
                            className={cn(
                                'side-drawer',
                                side === 'left' && 'left-0 right-auto border-r border-l-0',
                                side === 'right' && 'right-0 left-auto border-l',
                                sizeStyles[size],
                                className
                            )}
                        >
                            <div className="flex flex-col h-full">
                                {/* Header */}
                                {(title || showCloseButton || showBackButton) && (
                                    <div
                                        className={cn(
                                            'flex items-center gap-3 px-4 py-4 border-b border-border',
                                            headerClassName
                                        )}
                                    >
                                        {showBackButton && (
                                            <button
                                                onClick={onBack || handleClose}
                                                className="p-1.5 rounded-full hover:bg-muted transition-colors -ml-1.5"
                                                aria-label="Go back"
                                            >
                                                <ChevronLeft className="h-5 w-5 text-foreground" />
                                            </button>
                                        )}

                                        <div className="flex-1 min-w-0">
                                            {title && (
                                                <h2 className="font-semibold text-lg text-foreground truncate">
                                                    {title}
                                                </h2>
                                            )}
                                            {description && (
                                                <p className="text-sm text-muted-foreground truncate">
                                                    {description}
                                                </p>
                                            )}
                                        </div>

                                        {showCloseButton && (
                                            <button
                                                onClick={handleClose}
                                                className="p-1.5 rounded-full hover:bg-muted transition-colors"
                                                aria-label="Close drawer"
                                            >
                                                <X className="h-5 w-5 text-muted-foreground" />
                                            </button>
                                        )}
                                    </div>
                                )}

                                {/* Content */}
                                <div
                                    className={cn(
                                        'flex-1 overflow-y-auto overflow-x-hidden',
                                        contentClassName
                                    )}
                                >
                                    <div className="w-full max-w-full">
                                        {children}
                                    </div>
                                </div>

                                {/* Footer */}
                                {footer && (
                                    <div className="flex-shrink-0 px-4 py-4 border-t border-border">
                                        {footer}
                                    </div>
                                )}
                            </div>
                        </motion.div>
                    </>
                )}
            </AnimatePresence>
        );
    }
);

SideDrawer.displayName = 'SideDrawer';

// SideDrawerContent - Wrapper for consistent padding
const SideDrawerContent = React.forwardRef<
    HTMLDivElement,
    React.HTMLAttributes<HTMLDivElement>
>(({ className, ...props }, ref) => {
    return (
        <div ref={ref} className={cn('px-4 py-4', className)} {...props} />
    );
});

SideDrawerContent.displayName = 'SideDrawerContent';

// SideDrawerSection - Section within drawer
interface SideDrawerSectionProps extends React.HTMLAttributes<HTMLDivElement> {
    title?: string;
}

const SideDrawerSection = React.forwardRef<HTMLDivElement, SideDrawerSectionProps>(
    ({ className, title, children, ...props }, ref) => {
        return (
            <div ref={ref} className={cn('py-4', className)} {...props}>
                {title && (
                    <h3 className="text-sm font-medium text-muted-foreground mb-3 px-4">
                        {title}
                    </h3>
                )}
                {children}
            </div>
        );
    }
);

SideDrawerSection.displayName = 'SideDrawerSection';

export { SideDrawer, SideDrawerContent, SideDrawerSection };
