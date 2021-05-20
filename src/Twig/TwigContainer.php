<?php

namespace Xenokore\App\Twig;

use function DI\autowire;


use Twig\Environment as TwigEnvironment;
use Xenokore\App\Twig\TwigProviderInterface;

class TwigContainer
{

    public static function getDefinitions(array $twig_config, $views, array $twig_extra_paths = []): array
    {
        return [
            TwigProviderInterface::class => autowire(TwigProvider::class),
            TwigEnvironment::class => function ($container) use ($twig_config, $views, $twig_extra_paths) {
                // Return a TwigEnvironment
                return $container->get(TwigProviderInterface::class)->create(
                    $twig_config,
                    $views,
                    $twig_extra_paths,
                    $container->get('twig_extension_classes')
                );
            },
        ];
    }
}
