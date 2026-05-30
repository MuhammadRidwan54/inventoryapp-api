<?php
// config/l5-swagger.php

return [
    'default' => 'default',
    'documentations' => [
        'default' => [
            'api' => [
                'title' => 'InventoryApp API Documentation',
                'description' => 'API Documentation for InventoryApp - Management Inventory dengan Role (Owner/Admin/Gudang)',
                'version' => '1.0.0',
                'termsOfService' => 'https://inventoryapp.com/terms',
                'contact' => [
                    'email' => 'support@inventoryapp.com',
                ],
                'license' => [
                    'name' => 'MIT',
                    'url' => 'https://opensource.org/licenses/MIT',
                ],
            ],
            'routes' => [
                'api' => 'api/v1/*',
            ],
            'paths' => [
                'base' => 'api/v1',
                'docs' => 'docs',
                'annotations' => base_path('app/Http/Controllers/Api/V1'),
                base_path('app/Swagger'),  // <- Tambahkan ini
            ],
            'generate_always' => env('L5_SWAGGER_GENERATE_ALWAYS', false),
            'apply' => [
                'L5Swagger\Annotations\ApplyForMethods',
                'L5Swagger\Annotations\ApplyForResponseParts',
            ],
            'security' => [
                'passport' => false,
                'sanctum' => true,
                'sanctum' => [
                    'type' => 'http',
                    'scheme' => 'bearer',
                    'bearerFormat' => 'JWT',
                    'description' => 'Enter your Bearer token here',
                ],
            ],
            'basePath' => '',
            'swagger_version' => '3.0',
            'use_absolute_path' => false,
        ],
    ],
    'security' => [
        'sanctum' => [
            'type' => 'http',
            'scheme' => 'bearer',
        ],
    ],
    'routes' => [
        'api' => 'api/documentation',
        'docs' => 'docs',
        'oauth2_callback' => 'api/oauth2-callback',
    ],
];