<?php

return [

    /*
    |--------------------------------------------------------------------------
    | SUNAT Credentials
    |--------------------------------------------------------------------------
    |
    | Beta (test) values: RUC 20000000001, SOL user/pass MODDATOS/MODDATOS.
    | SUNAT beta does not validate the certificate authority, only that the
    | XML is signed, so a self-signed certificate is enough for testing.
    |
    */

    'sunat' => [
        'ruc' => env('SUNAT_RUC', '20000000001'),
        'sol_user' => env('SUNAT_SOL_USER', 'MODDATOS'),
        'sol_pass' => env('SUNAT_SOL_PASS', 'MODDATOS'),

        // Combined PEM file (private key + certificate, unencrypted). See
        // storage/app/certs/README.md for how to generate one for testing.
        'cert_path' => env('SUNAT_CERT_PATH', storage_path('app/certs/certificate.pem')),
        'cert_pass' => env('SUNAT_CERT_PASS'),

        // Default matches Greenter\Ws\Services\SunatEndpoints::FE_BETA.
        'endpoint' => env('SUNAT_ENDPOINT', 'https://e-beta.sunat.gob.pe/ol-ti-itcpfegem-beta/billService'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Issuer (Company) Data
    |--------------------------------------------------------------------------
    */

    'company' => [
        'razon_social' => env('SUNAT_RAZON_SOCIAL', 'BRUCE FIRE S.A.C.'),
        'nombre_comercial' => env('SUNAT_NOMBRE_COMERCIAL', 'BRUCE FIRE'),
        'ubigeo' => '150101',
        'departamento' => 'LIMA',
        'provincia' => 'LIMA',
        'distrito' => 'LIMA',
        'direccion' => 'AV. PRINCIPAL S/N',
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Series
    |--------------------------------------------------------------------------
    */

    'series' => [
        'factura' => 'F001',
        'boleta' => 'B001',
    ],

    /*
    |--------------------------------------------------------------------------
    | Credit/Debit Note Series
    |--------------------------------------------------------------------------
    |
    | SUNAT requires NC/ND series to start with a different letter pair than
    | the invoice series they affect, keyed by the affected CPE's tipo.
    |
    */

    'note_series' => [
        'nota_credito' => ['factura' => 'FC01', 'boleta' => 'BC01'],
        'nota_debito' => ['factura' => 'FD01', 'boleta' => 'BD01'],
    ],

];
