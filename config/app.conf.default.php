<?php

/**
 * phpcs:disable Symfony.Files.AlphanumericFilename.Invalid
 * phpcs:disable Squiz.PHP.DisallowInlineIf.Found
 */

return [

    // Environment
    'env'  => $_ENV['APP_ENV'] ?? 'dev',

    // Path to a .env file if not using a container
    '.env' => null,

    // Main project source directory (for adding everything to the container)
    'src_dir' => null,

    // Main project controller directory
    // (for adding everything to the container, this allows DI in controllers)
    'controller_dir' => null,

    // Main Twig views dir
    'views_dir' => null,

    // Main project vendor directory
    'vendor_dir' => null,

    // Directory to cache container, templates, routes, etc
    'cache_dir' => \sys_get_temp_dir() . '/xeno-app-cache/' . \date('d-m-Y'),

    // Whether or not to compile to container, disabled when on 'dev' environment
    'compile_container' => isset($_ENV['APP_COMPILE_CONTAINER']) ? (bool) $_ENV['APP_COMPILE_CONTAINER'] : false,

    // Load Xeno compatible components
    'load_vendor_components' => false,

    // Include routing functionality
    'slim_enabled' => false,
    'slim_compile_router'  => isset($_ENV['APP_SLIM_COMPILE_ROUTER']) ? (bool) $_ENV['APP_SLIM_COMPILE_ROUTER'] : false,

    // Custom slim config
    'slim' => [],

    // Include Twig
    'twig_enabled' => false,

    // Cache Twig views
    'twig_use_cache' => isset($_ENV['APP_TWIG_USE_CACHE']) ? (bool) $_ENV['APP_TWIG_USE_CACHE'] : false,

    // Custom Twig config (cache option is handled by the factory)
    'twig_config' => [
        'debug'            => isset($_ENV['APP_TWIG_DEBUG']) ? (bool) $_ENV['APP_TWIG_DEBUG'] : false,
        'charset'          => 'utf8',
        'strict_variables' => false,
    ],

    // Twig extra views directories (key = namespace, value = path)
    'twig_extra_paths' => [],
];
