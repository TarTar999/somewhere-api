import * as React from 'react';
import { cn } from '@/lib/utils';
import { Search, X, MapPin, Loader2 } from 'lucide-react';
import { motion, AnimatePresence } from 'framer-motion';

export interface SearchResult {
    id: string;
    title: string;
    subtitle?: string;
    coordinates?: [number, number];
    type?: 'address' | 'zone' | 'collection' | 'place';
}

export interface MapSearchBarProps {
    className?: string;
    placeholder?: string;
    value?: string;
    onChange?: (value: string) => void;
    onSearch?: (query: string) => void;
    onResultSelect?: (result: SearchResult) => void;
    results?: SearchResult[];
    isLoading?: boolean;
    showResults?: boolean;
}

const MapSearchBar = React.forwardRef<HTMLInputElement, MapSearchBarProps>(
    (
        {
            className,
            placeholder = 'Rechercher une adresse, zone ou collection...',
            value,
            onChange,
            onSearch,
            onResultSelect,
            results = [],
            isLoading = false,
            showResults = true,
        },
        ref
    ) => {
        const [localValue, setLocalValue] = React.useState(value || '');
        const [isFocused, setIsFocused] = React.useState(false);
        const inputRef = React.useRef<HTMLInputElement>(null);

        const currentValue = value !== undefined ? value : localValue;
        const hasResults = results.length > 0 && showResults && isFocused;

        const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
            const newValue = e.target.value;
            setLocalValue(newValue);
            onChange?.(newValue);
        };

        const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
            if (e.key === 'Enter') {
                onSearch?.(currentValue);
            }
            if (e.key === 'Escape') {
                setIsFocused(false);
                inputRef.current?.blur();
            }
        };

        const handleClear = () => {
            setLocalValue('');
            onChange?.('');
            inputRef.current?.focus();
        };

        const handleResultClick = (result: SearchResult) => {
            onResultSelect?.(result);
            setIsFocused(false);
        };

        const getTypeIcon = (type?: string) => {
            switch (type) {
                case 'zone':
                    return <div className="w-3 h-3 rounded-full bg-info/20 border-2 border-info" />;
                case 'collection':
                    return <div className="w-3 h-3 rounded bg-warning/20 border-2 border-warning" />;
                default:
                    return <MapPin className="w-3 h-3 text-muted-foreground" />;
            }
        };

        return (
            <div className={cn('relative', className)}>
                <div
                    className={cn(
                        'map-search-bar transition-shadow',
                        isFocused && 'ring-2 ring-ring shadow-lg'
                    )}
                >
                    {isLoading ? (
                        <Loader2 className="h-5 w-5 text-muted-foreground animate-spin" />
                    ) : (
                        <Search className="h-5 w-5 text-muted-foreground" />
                    )}

                    <input
                        ref={inputRef}
                        type="text"
                        value={currentValue}
                        onChange={handleChange}
                        onKeyDown={handleKeyDown}
                        onFocus={() => setIsFocused(true)}
                        onBlur={() => setTimeout(() => setIsFocused(false), 200)}
                        placeholder={placeholder}
                        className="flex-1 bg-transparent border-none outline-none text-sm placeholder:text-muted-foreground"
                    />

                    {currentValue && (
                        <button
                            onClick={handleClear}
                            className="p-1 rounded-full hover:bg-muted transition-colors"
                            aria-label="Clear search"
                        >
                            <X className="h-4 w-4 text-muted-foreground" />
                        </button>
                    )}
                </div>

                {/* Results Dropdown */}
                <AnimatePresence>
                    {hasResults && (
                        <motion.div
                            initial={{ opacity: 0, y: -10 }}
                            animate={{ opacity: 1, y: 0 }}
                            exit={{ opacity: 0, y: -10 }}
                            transition={{ duration: 0.15 }}
                            className="absolute top-full left-0 right-0 mt-2 glass-panel rounded-xl overflow-hidden shadow-xl z-50"
                        >
                            <div className="max-h-72 overflow-y-auto py-2">
                                {results.map((result) => (
                                    <button
                                        key={result.id}
                                        onClick={() => handleResultClick(result)}
                                        className="w-full px-4 py-3 flex items-start gap-3 hover:bg-muted/50 transition-colors text-left"
                                    >
                                        <div className="mt-0.5">{getTypeIcon(result.type)}</div>
                                        <div className="flex-1 min-w-0">
                                            <p className="text-sm font-medium text-foreground truncate">
                                                {result.title}
                                            </p>
                                            {result.subtitle && (
                                                <p className="text-xs text-muted-foreground truncate">
                                                    {result.subtitle}
                                                </p>
                                            )}
                                        </div>
                                    </button>
                                ))}
                            </div>
                        </motion.div>
                    )}
                </AnimatePresence>

                {/* No Results Message */}
                <AnimatePresence>
                    {showResults && isFocused && currentValue && results.length === 0 && !isLoading && (
                        <motion.div
                            initial={{ opacity: 0, y: -10 }}
                            animate={{ opacity: 1, y: 0 }}
                            exit={{ opacity: 0, y: -10 }}
                            className="absolute top-full left-0 right-0 mt-2 glass-panel rounded-xl p-4 shadow-xl z-50"
                        >
                            <p className="text-sm text-muted-foreground text-center">
                                Aucun résultat pour "{currentValue}"
                            </p>
                        </motion.div>
                    )}
                </AnimatePresence>
            </div>
        );
    }
);

MapSearchBar.displayName = 'MapSearchBar';

export { MapSearchBar };
