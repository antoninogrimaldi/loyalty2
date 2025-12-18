<?php
// Database and app configuration for the loyalty platform.
// Replace credentials with your own secure values in production.
return [
    'db' => [
        'host' => 'localhost',
        'user' => 'root',
        'password' => '',
        'name' => 'loyalty_app'
    ],
    'app' => [
        'name' => 'Loyalty Hub',
        // Se l'app vive in una sottocartella (es. http://localhost/loyalty2), indica il percorso base
        // altrimenti imposta a '' per la root (es. http://localhost/).
        'base_url' => '/loyalty2'
    ]
];
