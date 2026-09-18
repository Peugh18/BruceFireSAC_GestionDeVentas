import InputError from '@/components/input-error';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { type ComponentProps } from 'react';

export default function InventoryField({ label, error, ...props }: ComponentProps<typeof Input> & { id: string; label: string; error?: string }) {
    return (
        <div className="grid gap-2">
            <Label htmlFor={props.id}>{label}</Label>
            <Input {...props} aria-invalid={!!error} aria-describedby={error ? props.id + '-error' : undefined} />
            <InputError id={props.id + '-error'} message={error} />
        </div>
    );
}
