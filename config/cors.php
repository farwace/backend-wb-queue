<?php


return [

    // Маршруты, на которых включается CORS
    'paths' => [
        'api/*',
        'sanctum/csrf-cookie',
    ],

    // Разрешённые HTTP-методы (OPTIONS для preflight включён)
    'allowed_methods' => ['*'],

    // Оставляем пустым, т.к. используем шаблоны
    'allowed_origins' => [
        '*'
    ],

    // Шаблон для всех субдоменов вида https://*.wb.h1n.ru
    'allowed_origins_patterns' => [
    ],

    // Разрешить любые заголовки от клиента
    'allowed_headers' => ['*'],

    // Не передаём куки/credentials
    'supports_credentials' => false,

    // (остальные опции по-умолчанию)
    'exposed_headers'      => [],
    'max_age'              => 0,
];
