import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { Bot, Send, Sparkles } from 'lucide-react';
import { FormEventHandler } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Asistente Gerencial', href: route('ai-assistant.index') }];

const EXAMPLE_QUESTIONS = [
    '¿Cuánto vendimos este mes?',
    '¿Qué clientes tienen más equipos por vencer?',
    '¿Qué servicio creció más este mes?',
    '¿Qué repuestos están cerca de agotarse?',
];

export default function AiAssistantIndex({ question, answer }: { question?: string; answer?: string }) {
    const { data, setData, post, processing, errors } = useForm({ question: '' });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('ai-assistant.ask'), { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Asistente Gerencial" />

            <div className="mx-auto flex w-full max-w-2xl flex-1 flex-col gap-6 p-4 md:p-6">
                <HeadingSmall
                    title="Asistente Gerencial"
                    description="Preguntas en lenguaje natural sobre los datos ya registrados en el sistema. No predice ni inventa cifras."
                />

                <Card>
                    <CardContent className="space-y-4 pt-6">
                        <form onSubmit={submit} className="space-y-3">
                            <Textarea
                                value={data.question}
                                onChange={(e) => setData('question', e.target.value)}
                                placeholder="Escribe tu pregunta, por ejemplo: ¿Cuánto vendimos este mes?"
                                rows={3}
                            />
                            <InputError message={errors.question} />

                            <div className="flex flex-wrap gap-2">
                                {EXAMPLE_QUESTIONS.map((example) => (
                                    <button
                                        key={example}
                                        type="button"
                                        onClick={() => setData('question', example)}
                                        className="rounded-full border px-3 py-1 text-xs text-muted-foreground hover:bg-muted"
                                    >
                                        {example}
                                    </button>
                                ))}
                            </div>

                            <Button type="submit" disabled={processing || data.question.trim() === ''} className="gap-1.5">
                                <Send className="size-4" />
                                {processing ? 'Consultando...' : 'Preguntar'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>

                {question && (
                    <Card>
                        <CardHeader className="flex flex-row items-center gap-2 pb-2">
                            <Bot className="size-4 text-muted-foreground" />
                            <CardTitle className="text-sm text-muted-foreground">Pregunta</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-0 text-sm">{question}</CardContent>
                    </Card>
                )}

                {answer && (
                    <Card>
                        <CardHeader className="flex flex-row items-center gap-2 pb-2">
                            <Sparkles className="size-4 text-primary" />
                            <CardTitle className="text-sm">Respuesta</CardTitle>
                        </CardHeader>
                        <CardContent className="pt-0 text-sm whitespace-pre-wrap">{answer}</CardContent>
                    </Card>
                )}

                <p className="text-xs text-muted-foreground">
                    El asistente solo responde con datos ya registrados en el sistema (ventas, equipos, servicios, inventario). No autoriza
                    descuentos, no emite comprobantes ni guías, y no modifica inventario.
                </p>
            </div>
        </AppLayout>
    );
}
