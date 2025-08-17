<?php
return [

    'paths' => ['api/v1/*', 'broadcasting/*'],

    'allowed_methods' => ['*'],

    // Liste explicite de ton front local et backend local
    'allowed_origins' => [
        'http://localhost:4000',

        'https://escameroun.devfack.com',
        'https://escameroun.devfack.com/frontend',
        'http://localhost', // Pour les tests locaux
        'http://localhost:5173',],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],
 
    'supports_credentials' => true, // IMPORTANT pour Sanctum

];