import { Button } from '@/components/ui/button';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { cn } from '@/lib/utils';
import { Check, ChevronsUpDown, Loader2 } from 'lucide-react';
import * as React from 'react';

export interface ComboboxOption {
    value: string;
    label: string;
    description?: string;
    data?: any;
}

export interface ComboboxProps {
    options?: ComboboxOption[];
    value?: string;
    onValueChange?: (value: string, option?: ComboboxOption) => void;
    placeholder?: string;
    searchPlaceholder?: string;
    emptyText?: string;
    disabled?: boolean;
    className?: string;
    id?: string;
    loading?: boolean;
    onSearchChange?: (query: string) => void;
    shouldFilter?: boolean;
}

export function Combobox({
    options = [],
    value,
    onValueChange,
    placeholder = 'Seleccionar...',
    searchPlaceholder = 'Buscar...',
    emptyText = 'No se encontraron resultados.',
    disabled = false,
    className,
    id,
    loading = false,
    onSearchChange,
    shouldFilter = true,
}: ComboboxProps) {
    const [open, setOpen] = React.useState(false);
    const [search, setSearch] = React.useState('');

    const selectedOption = React.useMemo(() => {
        return options.find((opt) => opt.value === value);
    }, [options, value]);

    const handleSearch = (q: string) => {
        setSearch(q);
        onSearchChange?.(q);
    };

    const handleSelect = (option: ComboboxOption) => {
        onValueChange?.(option.value, option);
        setOpen(false);
    };

    return (
        <Popover open={open} onOpenChange={setOpen}>
            <PopoverTrigger asChild>
                <Button
                    id={id}
                    variant="outline"
                    role="combobox"
                    aria-expanded={open}
                    disabled={disabled}
                    className={cn('w-full justify-between font-normal', !value && 'text-muted-foreground', className)}
                    type="button"
                >
                    <span className="truncate">{selectedOption?.label || placeholder}</span>
                    <ChevronsUpDown className="ml-2 size-4 shrink-0 opacity-50" />
                </Button>
            </PopoverTrigger>
            <PopoverContent className="w-[360px] p-0" align="start">
                <Command shouldFilter={shouldFilter}>
                    <CommandInput placeholder={searchPlaceholder} value={search} onValueChange={handleSearch} />
                    <CommandList>
                        {loading ? (
                            <div className="flex items-center justify-center py-4 text-sm text-muted-foreground gap-2">
                                <Loader2 className="size-4 animate-spin" />
                                Buscando...
                            </div>
                        ) : options.length === 0 ? (
                            <CommandEmpty>{emptyText}</CommandEmpty>
                        ) : (
                            <CommandGroup>
                                {options.map((option) => (
                                    <CommandItem
                                        key={option.value}
                                        value={`${option.label} ${option.description ?? ''}`.trim()}
                                        onSelect={() => handleSelect(option)}
                                        className="cursor-pointer"
                                    >
                                        <Check
                                            className={cn(
                                                'mr-2 size-4',
                                                value === option.value ? 'opacity-100' : 'opacity-0'
                                            )}
                                        />
                                        <div className="min-w-0 flex-1">
                                            <p className="truncate font-medium text-sm">{option.label}</p>
                                            {option.description && (
                                                <p className="text-xs text-muted-foreground">{option.description}</p>
                                            )}
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
