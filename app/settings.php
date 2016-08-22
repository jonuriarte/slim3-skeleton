<?php
    $dotenv = new Dotenv\Dotenv(__DIR__);
    $dotenv->load();
    return [
        'app' => [
            'url' => 'http://pruebaslim.com',
            //'asset_path' => '/resources'
            'asset_path' => getenv('ASSET_PATH'),
            'error_log' => getenv('ERROR_LOG'),
            'language' => getenv('LANGUAGE')
        ],
        'csrf' => [
            'global'   => true,
            'prefix'   => 'csrf',
            'strength' => 16,
        ],
        'settings' => [
            // Slim Settings
            //'determineRouteBeforeAppMiddleware' => false,
            'debug' => true,
            'whoops.editor' => getenv('WHOOPS_EDITOR'),
            'displayErrorDetails' => getenv('DISPLAY_ERROR_DETAILS',true),
            'addContentLengthHeader' => false,
            // View settings
            'view' => [
                'template_path' => __DIR__ . getenv('TEMPLATE_PATH'),
                'twig' => [
                    //'cache' => __DIR__ . '/../cache/twig',
                    'cache' => false,
                    'debug' => true,
                    'auto_reload' => true,
                ],
            ],
            // monolog settings
            'db' => [
                // Eloquent configuration
                'driver'    => getenv('DB_DRIVER', 'mysql'),
                'host'      => getenv('DB_HOST', 'localhost'),
                'database'  => getenv('DB_DATABASE', 'slimapp'),
                'username'  => getenv('DB_USERNAME', 'user'),
                'password'  => getenv('DB_PASSWORD', 'pass'),
                'charset'   => getenv('DB_CHARSET', 'utf8'),
                'collation' => getenv('DB_COLLATION', 'utf8_unicode_ci'),
                'prefix'    => getenv('DB_PREFIX', ''),
            ],
            // monolog settings
            'logger' => [
                'name' => 'app',
                'path' => __DIR__ . getenv('MONOLOG_PATH'),
            ],
        ],
    ];
