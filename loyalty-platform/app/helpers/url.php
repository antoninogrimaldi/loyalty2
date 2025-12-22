<?php
require_once __DIR__ . '/../config/config.php';

function app_url(string $path = ''): string
{
    $base = rtrim(APP_URL, '/');
    if ($path && $path[0] !== '/') {
        $path = '/' . $path;
    }
    return $base . $path;
}

function route_url(string $route, array $params = []): string
{
    $query = http_build_query(array_merge(['route' => $route], $params));
    return app_url('/?' . $query);
}
