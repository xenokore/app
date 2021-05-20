<?php

namespace Xenokore\App\Slim;

use Xenokore\Utility\Helper\ArrayHelper;

class SlimConfig
{

    // Default names for cache outputs
    public const ROUTER_CACHE_FILENAME = 'routes.cache.php';

    public static function getFinalConfig(array $slim_config, string $cache_dir)
    {

        $is_debug = \getenv('APP_ENV') === 'dev';

        $router_cache_file = false;
        if (!$is_debug) {
            $router_cache_file = $cache_dir . '/' . self::ROUTER_CACHE_FILENAME;
        }

        $config = ArrayHelper::mergeRecursiveDistinct(
            [
                'settings.debug'               => $is_debug,
                'settings.displayErrorDetails' => $is_debug,
                'settings.routerCacheFile'     => $router_cache_file,
            ],
            $slim_config ?? []
        );

        // Create a normal array from the dotnotation array and combine them
        return \array_merge($config, ArrayHelper::convertDotNotationToArray($config));
    }
}
