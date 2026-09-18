<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class DocumentLookupService
{
    /**
     * Query APIsPeru for a DNI (8 digits) and normalize the output.
     *
     * @return array{razon_social: string}|null
     */
    public function lookupDni(string $dni): ?array
    {
        $baseUrl = rtrim((string) config('services.apisperu.base_url', ''), '/');
        $token = (string) config('services.apisperu.token', '');

        if ($baseUrl === '' || $token === '') {
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(5)
                ->get("{$baseUrl}/dni/{$dni}");

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();
            if (! is_array($data)) {
                return null;
            }

            $nombres = trim((string) ($data['nombres'] ?? ''));
            $apellidoPaterno = trim((string) ($data['apellidoPaterno'] ?? ''));
            $apellidoMaterno = trim((string) ($data['apellidoMaterno'] ?? ''));

            $fullName = trim(preg_replace('/\s+/', ' ', "{$nombres} {$apellidoPaterno} {$apellidoMaterno}"));

            if ($fullName === '') {
                return null;
            }

            return [
                'razon_social' => $fullName,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Query APIsPeru for a RUC (11 digits) and normalize the output.
     *
     * @return array{
     *     razon_social: ?string,
     *     nombre_comercial: ?string,
     *     direccion_fiscal: ?string,
     *     departamento: ?string,
     *     provincia: ?string,
     *     distrito: ?string,
     *     ubigeo: ?string
     * }|null
     */
    public function lookupRuc(string $ruc): ?array
    {
        $baseUrl = rtrim((string) config('services.apisperu.base_url', ''), '/');
        $token = (string) config('services.apisperu.token', '');

        if ($baseUrl === '' || $token === '') {
            return null;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(5)
                ->get("{$baseUrl}/ruc/{$ruc}");

            if (! $response->successful()) {
                return null;
            }

            $data = $response->json();
            if (! is_array($data)) {
                return null;
            }

            return [
                'razon_social' => $data['razonSocial'] ?? null,
                'nombre_comercial' => $data['nombreComercial'] ?? null,
                'direccion_fiscal' => $data['direccion'] ?? null,
                'departamento' => $data['departamento'] ?? null,
                'provincia' => $data['provincia'] ?? null,
                'distrito' => $data['distrito'] ?? null,
                'ubigeo' => $data['ubigeo'] ?? null,
            ];
        } catch (Throwable) {
            return null;
        }
    }
}
