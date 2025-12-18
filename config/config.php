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
        'base_url' => '/'
    ]
];
