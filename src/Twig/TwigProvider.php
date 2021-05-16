<?php

namespace Xenokore\App\Twig;

use Psr\Container\ContainerInterface;

use Twig\Environment as TwigEnvironment;
use Twig\Extension\DebugExtension as TwigDebugExtension;
use Twig\Loader\FilesystemLoader as TwigFilesystemLoader;

use Xenokore\Utility\Helper\DirectoryHelper;

use LogicException;
use Twig\Error\LoaderError as TwigLoaderError;
use Xenokore\App\Exception\TwigProviderException;

class TwigProvider implements TwigProviderInterface
{

    /**
     * DI Container (with autowire support)
     *
     * @var ContainerInterface
     */
    protected $container;

    /**
     * The Twig Environment
     *
     * @var TwigEnvironment
     */
    protected $twig;

    /**
     * Public constructor class.
     * Takes the App container for autowiring.
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Create a TwigEnvironment
     *
     * @param array $twig_config
     * @param mixed $views
     * @param array $extra_paths
     * @param array $twig_container_extensions
     *
     * @return TwigEnvironment
     *
     * @throws TwigProviderException
     * @throws TwigLoaderError
     * @throws LogicException
     */
    public function create(array $twig_config, $views, array $extra_paths = [], array $twig_container_extensions = []): TwigEnvironment
    {
        // Views must be set
        if (empty($views)) {
            throw new TwigProviderException('views must be set for Twig to work');
        }

        // Check if all paths are accessible
        if (\is_array($views)) {
            foreach ($views as $views_path) {
                if (!DirectoryHelper::isAccessible($views_path)) {
                    throw new TwigProviderException("views path {$views_path} is not accessible");
                }
            }
        } else {
            if (!DirectoryHelper::isAccessible($views)) {
                throw new TwigProviderException("views path {$views} is not accessible");
            }
        }

        // Get the Twig Filesystem to load the views
        $twig_filesystem = new TwigFilesystemLoader($views);

        // Add extra views directories (namespace => path)
        if (!empty($extra_paths) && \is_array($extra_paths)) {
            foreach ($extra_paths as $key => $value) {
                if (\is_int($key)) {
                    $twig_filesystem->addPath($value);
                } else {
                    $twig_filesystem->addPath($value, $key);
                }
            }
        }

        // Create the TwigEnvironment
        $twig = new TwigEnvironment($twig_filesystem, $twig_config);

        // Add the Twig debug extension
        if ($twig_config['debug']) {
            $twig->addExtension(new TwigDebugExtension);
        }

        // Add TwigExtensions from the container
        // This allows TwigExtensions to be autowired
        if ($twig_container_extensions) {
            foreach ($twig_container_extensions as $class) {
                $twig->addExtension($this->container->get($class));
            }
        }

        // Add standard globals
        $twig->addGlobal('environment', \getenv('env'));

        // Add custom extendible globals
        foreach ($this->addGlobals() as $var => $val) {
            $twig->addGlobal($var, $val);
        }

        return $twig;
    }

    /**
     * Method used to add standard globals when extending the TwigProvider.
     *
     * @return array
     */
    public function addGlobals(): array
    {
        return [];
    }
}
