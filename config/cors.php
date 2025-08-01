<?php
return [

    'paths' => ['api/v1/*', 'broadcasting/*'],

    'allowed_methods' => ['*'],

    // Liste explicite de ton front local et backend local
    'allowed_origins' => ['http://localhost:4000'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],
 
    'supports_credentials' => true, // IMPORTANT pour Sanctum

];