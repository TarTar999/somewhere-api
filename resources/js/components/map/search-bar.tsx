import { useState, useCallback } from 'react';
import { Search, X, Loader2 } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';

interface SearchResult {
    id: number;
    swAddress: string;
    latitude: number;
    longitude: number;
    streetName?: string;
    lieuDit?: string;
    quarter?: string;
    commune?: string;
    distance?: number;
}

interface SearchBarProps {
    onSearch: (query: string) => Promise<SearchResult[]>;
    onSelectResult: (result: SearchResult) => void;
    placeholder?: string;
}

export function SearchBar({ onSearch, onSelectResult, placeholder = 'Rechercher une adresse...' }: SearchBarProps) {
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<SearchResult[]>([]);
    const [isLoading, setIsLoading] = useState(false);
    const [showResults, setShowResults] = useState(false);

    const handleSearch = useCallback(async () => {
        if (!query.trim()) {
            setResults([]);
            return;
        }

        setIsLoading(true);
        try {
            const searchResults = await onSearch(query);
            setResults(searchResults);
            setShowResults(true);
        } catch (error) {
            console.error('Search error:', error);
            setResults([]);
        } finally {
            setIsLoading(false);
        }
    }, [query, onSearch]);

    const handleSelectResult = (result: SearchResult) => {
        onSelectResult(result);
        setShowResults(false);
        setQuery(result.swAddress);
    };

    const clearSearch = () => {
        setQuery('');
        setResults([]);
        setShowResults(false);
    };

    return (
        <div className="relative w-full max-w-md">
            <div className="relative flex items-center">
                <Search className="absolute left-3 h-4 w-4 text-muted-foreground" />
                <Input
                    type="text"
                    value={query}
                    onChange={(e) => setQuery(e.target.value)}
                    onKeyDown={(e) => e.key === 'Enter' && handleSearch()}
                    onFocus={() => results.length > 0 && setShowResults(true)}
                    placeholder={placeholder}
                    className="pl-10 pr-20"
                />
                <div className="absolute right-2 flex items-center gap-1">
                    {query && (
                        <Button variant="ghost" size="icon" className="h-6 w-6" onClick={clearSearch}>
                            <X className="h-4 w-4" />
                        </Button>
                    )}
                    <Button size="sm" onClick={handleSearch} disabled={isLoading}>
                        {isLoading ? <Loader2 className="h-4 w-4 animate-spin" /> : 'Rechercher'}
                    </Button>
                </div>
            </div>

            {showResults && results.length > 0 && (
                <div className="absolute top-full z-50 mt-1 w-full rounded-md border bg-background shadow-lg">
                    <ul className="max-h-60 overflow-auto py-1">
                        {results.map((result) => (
                            <li
                                key={result.id}
                                onClick={() => handleSelectResult(result)}
                                className="cursor-pointer px-4 py-2 hover:bg-muted"
                            >
                                <div className="font-medium">{result.swAddress}</div>
                                <div className="text-sm text-muted-foreground">
                                    {[result.quarter, result.commune].filter(Boolean).join(', ')}
                                    {result.distance !== null && result.distance !== undefined && (
                                        <span className="ml-2">({result.distance} km)</span>
                                    )}
                                </div>
                            </li>
                        ))}
                    </ul>
                </div>
            )}

            {showResults && results.length === 0 && !isLoading && query && (
                <div className="absolute top-full z-50 mt-1 w-full rounded-md border bg-background p-4 text-center text-muted-foreground shadow-lg">
                    Aucun résultat trouvé
                </div>
            )}
        </div>
    );
}
