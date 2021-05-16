<?php

namespace Xenokore\App\Twig;

use Psr\Container\ContainerInterface;

use Twig\Environment as TwigEnvironment;

use LogicException;
use Twig\Error\LoaderError as TwigLoaderError;
use Xenokore\App\Exception\TwigProviderException;

interface TwigProviderInterface
{
    /**
     * Create a TwigEnvironment
     *
     * @param array $twig_config
     * @param array|string $views
     * @param array $extra_paths
     * @param array $twig_container_extensions
     *
     * @return TwigEnvironment
     *
     * @throws TwigProviderException
     * @throws TwigLoaderError
     * @throws LogicException
     */
    public function create(array $twig_config, mixed $views, array $extra_paths = [], array $twig_container_extensions = []): TwigEnvironment;

    /**
     * Method used to add standard globals when extending the TwigProvider.
     *
     * @return array
     */
    public function addGlobals(): array;
}
