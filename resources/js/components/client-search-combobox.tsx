import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { type Client } from '@/types';
import { Check, ChevronsUpDown, Loader2 } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';

interface ClientSearchComboboxProps {
    value: string | number;
    onSelect: (client: Client) => void;
    selectedClient?: Client | null;
    disabled?: boolean;
    placeholder?: string;
    id?: string;
}

/**
 * Client search combobox that fetches up to 20 results from /clients-search
 * with a 300ms debounce. On selection it fires onSelect with the full client
 * object (including sites and vehicles) so the parent can populate dependent
 * fields immediately without an additional round-trip.
 */
export function ClientSearchCombobox({
    value,
    onSelect,
    selectedClient = null,
    disabled = false,
    placeholder = 'Buscar cliente...',
    id,
}: ClientSearchComboboxProps) {
    const [open, setOpen] = useState(false);
    const [query, setQuery] = useState('');
    const [results, setResults] = useState<Client[]>([]);
    const [loading, setLoading] = useState(false);
    const [selectedLabel, setSelectedLabel] = useState('');
    const debounceRef = useRef<ReturnType<typeof setTimeout> | null>(null);
    const abortRef = useRef<AbortController | null>(null);

    const fetchClients = useCallback((q: string) => {
        if (abortRef.current) {
            abortRef.current.abort();
        }
        abortRef.current = new AbortController();
        setLoading(true);

        fetch(`${route('clients.search')}?q=${encodeURIComponent(q)}`, {
            signal: abortRef.current.signal,
        })
            .then((r) => r.json())
            .then((data: Client[]) => {
                setResults(data);
            })
            .catch(() => {})
            .finally(() => setLoading(false));
    }, []);

    // Load initial list when popover opens with no query.
    useEffect(() => {
        if (open) {
            fetchClients(query);
        }
    }, [open]); // eslint-disable-line react-hooks/exhaustive-deps

    const handleQueryChange = (q: string) => {
        setQuery(q);
        if (debounceRef.current) clearTimeout(debounceRef.current);
        debounceRef.current = setTimeout(() => fetchClients(q), 300);
    };

    const handleSelect = (client: Client) => {
        setSelectedLabel(`${client.razon_social} (${client.numero_documento})`);
        setOpen(false);
        onSelect(client);
    };

    // Sync label when the value changes externally (e.g. form reset).
    useEffect(() => {
        if (!value) {
            setSelectedLabel('');
        }
    }, [value]);

    useEffect(() => {
        if (selectedClient) {
            setSelectedLabel(`${selectedClient.razon_social} (${selectedClient.numero_documento})`);
        }
    }, [selectedClient]);

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    disabled={disabled}
                    className="w-full justify-between font-normal"
                    type="button"
                >
                    <span className="truncate">{selectedLabel || placeholder}</span>
                    <ChevronsUpDown className="ml-2 size-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-[360px] p-0" align="start">
                <Command shouldFilter={false}>
                    <CommandInput placeholder="Buscar por nombre o RUC/DNI..." value={query} onValueChange={handleQueryChange} />
                    <CommandList>
                        {loading ? (
                            <div className="flex items-center justify-center py-4 text-sm text-muted-foreground gap-2">
                                <Loader2 className="size-4 animate-spin" />
                                Buscando...
                            </div>
                        ) : results.length === 0 ? (
                            <CommandEmpty>Sin resultados.</CommandEmpty>
                        ) : (
                            <CommandGroup>
                                {results.map((client) => (
                                    <CommandItem
                                        key={client.id}
                                        value={String(client.id)}
                                        onSelect={() => handleSelect(client)}
                                        className="cursor-pointer"
                                    >
                                        <Check className={`mr-2 size-4 ${Number(value) === client.id ? 'opacity-100' : 'opacity-0'}`} />
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate font-medium text-sm">{client.razon_social}</p>
                                            <p className="text-xs text-muted-foreground">
                                                {client.tipo_documento?.toUpperCase()}: {client.numero_documento}
                                            </p>
                                        </div>
                                    </CommandItem>
                                ))}
                            </CommandGroup>
                        )}
                    </CommandList>
                </Command>
            </PopoverContent>
        </Popover>
    );
}
