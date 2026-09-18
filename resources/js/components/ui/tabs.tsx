import * as React from 'react';

import { cn } from '@/lib/utils';

interface TabsContextValue {
    value: string;
    setValue: (value: string) => void;
}

const TabsContext = React.createContext<TabsContextValue | null>(null);

function useTabsContext() {
    const context = React.useContext(TabsContext);

    if (!context) {
        throw new Error('Tabs components must be used within <Tabs>');
    }

    return context;
}

interface TabsProps extends Omit<React.HTMLAttributes<HTMLDivElement>, 'defaultValue'> {
    defaultValue: string;
    value?: string;
    onValueChange?: (value: string) => void;
}

function Tabs({ defaultValue, value, onValueChange, className, children, ...props }: TabsProps) {
    const [internalValue, setInternalValue] = React.useState(defaultValue);
    const currentValue = value ?? internalValue;

    const setValue = React.useCallback(
        (next: string) => {
            setInternalValue(next);
            onValueChange?.(next);
        },
        [onValueChange],
    );

    return (
        <TabsContext.Provider value={{ value: currentValue, setValue }}>
            <div className={cn('w-full', className)} {...props}>
                {children}
            </div>
        </TabsContext.Provider>
    );
}

const TabsList = React.forwardRef<HTMLDivElement, React.HTMLAttributes<HTMLDivElement>>(({ className, ...props }, ref) => (
    <div
        ref={ref}
        role="tablist"
        className={cn('inline-flex h-10 items-center justify-start gap-1 rounded-md bg-muted p-1 text-muted-foreground', className)}
        {...props}
    />
));
TabsList.displayName = 'TabsList';

interface TabsTriggerProps extends React.ButtonHTMLAttributes<HTMLButtonElement> {
    value: string;
}

const TabsTrigger = React.forwardRef<HTMLButtonElement, TabsTriggerProps>(({ className, value, ...props }, ref) => {
    const { value: activeValue, setValue } = useTabsContext();
    const isActive = activeValue === value;

    return (
        <button
            ref={ref}
            type="button"
            role="tab"
            aria-selected={isActive}
            onClick={() => setValue(value)}
            className={cn(
                'inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium transition-colors focus-visible:outline-hidden focus-visible:ring-2 focus-visible:ring-ring disabled:pointer-events-none disabled:opacity-50',
                isActive ? 'bg-background text-foreground shadow-xs' : 'hover:bg-background/50 hover:text-foreground',
                className,
            )}
            {...props}
        />
    );
});
TabsTrigger.displayName = 'TabsTrigger';

interface TabsContentProps extends React.HTMLAttributes<HTMLDivElement> {
    value: string;
}

const TabsContent = React.forwardRef<HTMLDivElement, TabsContentProps>(({ className, value, ...props }, ref) => {
    const { value: activeValue } = useTabsContext();

    if (activeValue !== value) {
        return null;
    }

    return (
        <div
            ref={ref}
            role="tabpanel"
            className={cn('mt-4 focus-visible:outline-hidden', className)}
            {...props}
        />
    );
});
TabsContent.displayName = 'TabsContent';

export { Tabs, TabsContent, TabsList, TabsTrigger };
