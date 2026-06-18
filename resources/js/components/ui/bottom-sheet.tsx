import * as React from 'react';
import { cn } from '@/lib/utils';
import { motion, AnimatePresence, useDragControls, PanInfo } from 'framer-motion';
import { X } from 'lucide-react';

export interface BottomSheetProps {
    children: React.ReactNode;
    open?: boolean;
    onOpenChange?: (open: boolean) => void;
    snapPoints?: number[];
    defaultSnapPoint?: number;
    className?: string;
    showHandle?: boolean;
    closable?: boolean;
    title?: string;
    overlay?: boolean;
}

const BottomSheet = React.forwardRef<HTMLDivElement, BottomSheetProps>(
    (
        {
            children,
            open = true,
            onOpenChange,
            snapPoints = [0.25, 0.5, 0.9],
            defaultSnapPoint = 0,
            className,
            showHandle = true,
            closable = true,
            title,
            overlay = false,
        },
        ref
    ) => {
        const [currentSnap, setCurrentSnap] = React.useState(defaultSnapPoint);
        const dragControls = useDragControls();
        const sheetRef = React.useRef<HTMLDivElement>(null);

        const currentHeight = `${snapPoints[currentSnap] * 100}%`;

        const handleDragEnd = (_: MouseEvent | TouchEvent | PointerEvent, info: PanInfo) => {
            const velocity = info.velocity.y;
            const offset = info.offset.y;
            const sheetHeight = sheetRef.current?.offsetHeight || 0;
            const windowHeight = window.innerHeight;

            // Determine direction and find next snap point
            if (velocity > 500 || offset > sheetHeight * 0.3) {
                // Swiping down - go to lower snap or close
                if (currentSnap > 0) {
                    setCurrentSnap(currentSnap - 1);
                } else if (closable) {
                    onOpenChange?.(false);
                }
            } else if (velocity < -500 || offset < -sheetHeight * 0.3) {
                // Swiping up - go to higher snap
                if (currentSnap < snapPoints.length - 1) {
                    setCurrentSnap(currentSnap + 1);
                }
            }
        };

        const handleClose = () => {
            onOpenChange?.(false);
        };

        const handleSnapTo = (index: number) => {
            if (index >= 0 && index < snapPoints.length) {
                setCurrentSnap(index);
            }
        };

        return (
            <AnimatePresence>
                {open && (
                    <>
                        {/* Overlay */}
                        {overlay && (
                            <motion.div
                                initial={{ opacity: 0 }}
                                animate={{ opacity: 1 }}
                                exit={{ opacity: 0 }}
                                className="fixed inset-0 bg-black/40 z-40"
                                onClick={closable ? handleClose : undefined}
                            />
                        )}

                        {/* Sheet */}
                        <motion.div
                            ref={sheetRef}
                            initial={{ y: '100%' }}
                            animate={{ y: `${100 - snapPoints[currentSnap] * 100}%` }}
                            exit={{ y: '100%' }}
                            transition={{ type: 'spring', damping: 30, stiffness: 300 }}
                            drag="y"
                            dragControls={dragControls}
                            dragConstraints={{ top: 0, bottom: 0 }}
                            dragElastic={0.1}
                            onDragEnd={handleDragEnd}
                            className={cn(
                                'bottom-sheet',
                                'touch-none',
                                className
                            )}
                            style={{ height: '100%' }}
                        >
                            <div
                                ref={ref}
                                className="h-full flex flex-col safe-area-bottom"
                            >
                                {/* Handle */}
                                {showHandle && (
                                    <div
                                        className="flex justify-center pt-3 pb-2 cursor-grab active:cursor-grabbing"
                                        onPointerDown={(e) => dragControls.start(e)}
                                    >
                                        <div className="bottom-sheet-handle" />
                                    </div>
                                )}

                                {/* Header */}
                                {(title || closable) && (
                                    <div className="flex items-center justify-between px-4 py-2 border-b border-border">
                                        {title && (
                                            <h3 className="font-semibold text-foreground">{title}</h3>
                                        )}
                                        {closable && (
                                            <button
                                                onClick={handleClose}
                                                className="p-1.5 rounded-full hover:bg-muted transition-colors ml-auto"
                                                aria-label="Close"
                                            >
                                                <X className="h-5 w-5 text-muted-foreground" />
                                            </button>
                                        )}
                                    </div>
                                )}

                                {/* Content */}
                                <div className="flex-1 overflow-y-auto overscroll-contain px-4 py-4">
                                    {children}
                                </div>

                                {/* Snap Point Indicators */}
                                {snapPoints.length > 1 && (
                                    <div className="flex justify-center gap-1.5 py-3">
                                        {snapPoints.map((_, index) => (
                                            <button
                                                key={index}
                                                onClick={() => handleSnapTo(index)}
                                                className={cn(
                                                    'w-2 h-2 rounded-full transition-colors',
                                                    index === currentSnap
                                                        ? 'bg-primary'
                                                        : 'bg-muted-foreground/30 hover:bg-muted-foreground/50'
                                                )}
                                                aria-label={`Snap to ${Math.round(snapPoints[index] * 100)}%`}
                                            />
                                        ))}
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

BottomSheet.displayName = 'BottomSheet';

// BottomSheetTrigger - Button to open sheet
interface BottomSheetTriggerProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    asChild?: boolean;
}

const BottomSheetTrigger = React.forwardRef<HTMLButtonElement, BottomSheetTriggerProps>(
    ({ className, children, ...props }, ref) => {
        return (
            <button
                ref={ref}
                className={cn('quick-action-btn', className)}
                {...props}
            >
                {children}
            </button>
        );
    }
);

BottomSheetTrigger.displayName = 'BottomSheetTrigger';

export { BottomSheet, BottomSheetTrigger };
