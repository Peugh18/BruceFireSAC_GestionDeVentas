import { ClientForm, type ClientFormData } from '@/components/clients/client-form';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { type Client } from '@/types';
import { Plus } from 'lucide-react';
import { FormEventHandler, ReactNode, useState } from 'react';

const initialClientData: ClientFormData = {
    tipo_documento: 'ruc',
    numero_documento: '',
    razon_social: '',
    nombre_comercial: '',
    telefono: '',
    whatsapp: '',
    email: '',
    direccion_fiscal: '',
    departamento: '',
    provincia: '',
    distrito: '',
    ubigeo: '',
    activo: true,
    observaciones: '',
};

interface InlineClientDialogProps {
    onCreated: (client: Client) => void;
    trigger?: ReactNode;
}

export function InlineClientDialog({ onCreated, trigger }: InlineClientDialogProps) {
    const [open, setOpen] = useState(false);
    const [data, setDataState] = useState<ClientFormData>(initialClientData);
    const [errors, setErrors] = useState<Partial<Record<keyof ClientFormData, string>>>({});
    const [processing, setProcessing] = useState(false);

    const setData = (key: keyof ClientFormData, value: string | boolean) => {
        setDataState((previous) => ({ ...previous, [key]: value }));
    };

    const reset = () => {
        setDataState(initialClientData);
        setErrors({});
    };

    const submit: FormEventHandler = async (event) => {
        event.preventDefault();
        setProcessing(true);
        setErrors({});

        const csrfToken = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')?.content ?? '';

        const response = await fetch(route('clients.store'), {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(data),
        });

        setProcessing(false);

        if (response.status === 422) {
            const payload = (await response.json()) as { errors?: Record<string, string[]> };
            const validationErrors: Partial<Record<keyof ClientFormData, string>> = {};

            for (const [field, messages] of Object.entries(payload.errors ?? {})) {
                validationErrors[field as keyof ClientFormData] = messages[0] ?? '';
            }

            setErrors(validationErrors);
            return;
        }

        if (!response.ok) {
            setErrors({ razon_social: 'No se pudo crear el cliente. Intenta nuevamente.' });
            return;
        }

        const client = (await response.json()) as Client;
        onCreated(client);
        reset();
        setOpen(false);
    };

    return (
        <Dialog
            open={open}
            onOpenChange={(nextOpen) => {
                setOpen(nextOpen);
                if (!nextOpen) {
                    reset();
                }
            }}
        >
            <DialogTrigger asChild>
                {trigger ?? (
                    <Button type="button" variant="outline">
                        <Plus className="mr-1 size-4" />
                        Nuevo cliente
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="max-h-[90vh] max-w-4xl overflow-y-auto">
                <DialogHeader>
                    <DialogTitle>Nuevo cliente</DialogTitle>
                    <DialogDescription>Registra el cliente sin salir del formulario actual.</DialogDescription>
                </DialogHeader>
                <ClientForm
                    data={data}
                    setData={setData}
                    setValues={(values) => setDataState((previous) => ({ ...previous, ...values }))}
                    errors={errors}
                    processing={processing}
                    onSubmit={submit}
                    submitLabel="Crear cliente"
                />
            </DialogContent>
        </Dialog>
    );
}
