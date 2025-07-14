<?php
return [

    'paths' => ['api/v1/*'],

    'allowed_methods' => ['*'],

    // Liste explicite de ton front local et backend local
    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],
 
    'supports_credentials' => true, // IMPORTANT pour Sanctum

];