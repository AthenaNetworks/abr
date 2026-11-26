<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ABR authentication GUID
    |--------------------------------------------------------------------------
    |
    | Register for a free GUID at https://abr.business.gov.au/Tools/WebServices
    |
    */

    'guid' => env('ABR_GUID'),

    /*
    |--------------------------------------------------------------------------
    | Endpoint
    |--------------------------------------------------------------------------
    |
    | The ABN Lookup document/literal service. Only change this to point at a
    | proxy or a test double.
    |
    */

    'endpoint' => env('ABR_ENDPOINT', 'https://abr.business.gov.au/abrxmlsearch/abrxmlsearch.asmx'),

];
