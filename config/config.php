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
        // Se l'app vive direttamente in htdocs, lascia stringa vuota (http://localhost/).
        // Se è in una sottocartella (es. http://localhost/loyalty2), imposta quel percorso.
        'base_url' => ''
    ]
];
