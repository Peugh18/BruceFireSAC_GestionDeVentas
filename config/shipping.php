<?php

return [

    /*
    |--------------------------------------------------------------------------
    | GRE (Guia de Remision Electronica) SUNAT Endpoint
    |--------------------------------------------------------------------------
    |
    | GRE uses a different SUNAT web service than Factura/Boleta. Credentials
    | (RUC, SOL user/pass, certificate) are shared with billing.php since
    | they belong to the same company/SOL account.
    |
    | Default matches Greenter\Ws\Services\SunatEndpoints::GUIA_BETA.
    |
    */

    'sunat' => [
        'endpoint' => env('SUNAT_GUIA_ENDPOINT', 'https://e-beta.sunat.gob.pe/ol-ti-itemision-guia-gem-beta/billService'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Document Series
    |--------------------------------------------------------------------------
    */

    'serie' => 'T001',

];
