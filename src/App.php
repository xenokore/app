<?php

namespace Xenokore\App;

use function DI\autowire;

use Psr\Log\LoggerInterface;
use Psr\Container\ContainerInterface;

use Slim\App as SlimApp;
use DI\ContainerBuilder;
use Xenokore\App\Twig\TwigProviderInterface;
use Symfony\Component\Dotenv\Dotenv;
use DI\Bridge\Slim\Bridge as SlimBridge;
use Twig\Environment as TwigEnvironment;
use Xenokore\ComponentLoader\Loader as ComponentLoader;

use Xenokore\Utility\Helper\ArrayHelper;
use Xenokore\Utility\Helper\DirectoryHelper;
use Xenokore\Utility\Helper\StringHelper;
use Xenokore\Utility\Helper\ClassHelper;

use Xenokore\App\Exception\AppException;
use Xenokore\App\Exception\TwigProviderException;
use Xenokore\App\Slim\SlimConfig;
use Xenokore\App\Twig\TwigContainer;
use Xenokore\App\Twig\TwigProvider;

class App
{

    // Default names for cache outputs
    public const CONTAINER_COMPILE_DIR   = 'container';
    public const CONTAINER_COMPILE_CLASS = 'CompiledContainer';

    protected static $global_container;

    /**
     * An array that contains the config values
     *
     * @var array
     */
    private $config = [];

    /**
     * An optional logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * The name of the environment
     *
     * @var string
     */
    private $env;

    /**
     * The path of the app cache directory.
     *
     * @var string
     */
    private $cache_dir;

    /**
     * The container
     *
     * @var ContainerInterface
     */
    private $container;

    /**
     * The Slim Bridge app.
     *
     * @var SlimApp
     */
    private $slim_app;

    /**
     * App Constructor.
     *
     * @param array           $config
     * @param LoggerInterface $logger
     */
    public function __construct(array $config = [], LoggerInterface $logger = null)
    {
        // Load environment variables file(s)
        if (!empty($config['.env'])) {
            $dotenv = new Dotenv();
            $dotenv->loadEnv($config['.env']);
        }

        // Load config values over the defaults
        $default_config = include __DIR__ . '/../config/app.conf.default.php';
        $this->config   = ArrayHelper::mergeRecursiveDistinct($default_config, $config);

        // Define current environment
        $this->env = $this->config['env'];

        // Add logger
        if ($logger) {
            $this->logger = $logger;
        }

        // Load app cache dir
        $this->loadAppCacheDir();

        // Add Twig caching configuration option
        $this->config['twig_config']['cache'] = false;
        if ($this->config['twig_use_cache']) {
            $cache_dir = $this->config['cache_dir'] . '/twig';
            if (!DirectoryHelper::createIfNotExist($cache_dir)) {
                throw new TwigProviderException("Twig cache dir can not be created: {$cache_dir}");
            }
            $this->config['twig_config']['cache'] = $cache_dir;
        }
    }

    /**
     * Add a Logger to the application
     *
     * @param LoggerInterface $logger
     * @return void
     */
    public function addLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Get the logger.
     *
     * @return LoggerInterface|null
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    /**
     * Initialize the container and loads it into the class.
     *
     * @return void
     */
    public function initializeContainer(): void
    {
        if ($this->container) {
            return;
        }

        $container = $this->createContainer();

        if (!$container instanceof ContainerInterface) {
            throw new AppException('Failed to create container');
        }

        $this->container = $container;

        // Load the container as a singleton
        // This should only be used for unit-testing purposes
        self::$global_container = $container;
    }

    /**
     * Get the DI container.
     *
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        if (!$this->container) {
            $this->initializeContainer();
        }

        return $this->container;
    }

    public static function getGlobalContainer(): ContainerInterface
    {
        if (self::$global_container === null) {
            throw new AppException(
                'Trying to get global container when the container has not been created yet.' . PHP_EOL .
                'First create an App and initialize the container before calling it with getGlobalContainer().'
            );
        }

        return self::$global_container;
    }

    /**
     * Get the Slim App for routing purposes.
     * https://php-di.org/doc/frameworks/slim.html
     * https://github.com/PHP-DI/Slim-Bridge
     *
     * @return SlimApp
     */
    public function getSlimRouter(): SlimApp
    {
        if ($this->slim_app) {
            return $this->slim_app;
        }

        return $this->slim_app = SlimBridge::create($this->getContainer());
    }

    public function getTwigEnvironment(): TwigEnvironment
    {
        return $this->getContainer()->get(TwigEnvironment::class);
    }

    /**
     * Load the app cache directory.
     *
     * @return void
     */
    private function loadAppCacheDir()
    {
        $cache_dir = $this->config['cache_dir'];

        if (!\is_string($cache_dir)) {
            throw new AppException("invalid `cache_dir`; must be a string");
        }

        if (!DirectoryHelper::createIfNotExist($cache_dir)) {
            throw new AppException("`cache_dir` can not be created: {$cache_dir}");
        }

        $this->cache_dir = $cache_dir;
    }

    /**
     * Get the default container definitions.
     *
     * @return array
     */
    private function getContainerDefinitions(): array
    {
        $definitions = [
            'app_config' => $this->config,
        ];

        $twig_extensions = [];

        // Load container definitions from vendor components
        if ($this->config['load_vendor_components']) {

            if (empty($this->config['vendor_dir'])) {
                throw new AppException('App: vendor_dir config value must be set if loading vendor components');
            }

            $component_loader = new ComponentLoader($this->config['vendor_dir']);
            foreach ($component_loader->getContainerDefinitions() as $var => $val) {
                $definitions[$var] = $val;
            }
        }

        // Add a Logger if one was passed
        if (!is_null($this->logger)) {
            $definitions[LoggerInterface::class] = $this->logger;
        }

        // Add Twig if enabled
        if ($this->config['twig_enabled']) {
            foreach (TwigContainer::getDefinitions(
                $this->config['twig_config'],
                $this->config['views_dir'],
                $this->config['twig_extra_paths'],
            ) as $key => $value) {
                $definitions[$key] = $value;
            }
        }

        // Gather project class files
        $class_files = [];
        $src_dir     = $this->config['src_dir'];
        if ($src_dir && DirectoryHelper::isAccessible($src_dir)) {
            $class_files = \array_merge($class_files, DirectoryHelper::tree($src_dir));
        }

        // Gather project controller files
        $controller_dir = $this->config['controller_dir'];
        if ($controller_dir && DirectoryHelper::isAccessible($controller_dir)) {
            $class_files = \array_merge($class_files, DirectoryHelper::tree($controller_dir));
        }

        foreach ($class_files as $file_path) {

            // Don't include non-php files
            if (!StringHelper::endsWith($file_path, '.php', true)) {
                continue;
            }

            $full_class = ClassHelper::getClassAndNamespace($file_path);

            // Check if the class is found and that it is not an Exception or
            // Helper, as those do not go into the container
            if ($full_class
                && !StringHelper::endsWith($full_class, ['Exception', 'Helper'])
                && \class_exists($full_class)
            ) {
                // Add the class as an autowire
                $definitions[$full_class] = autowire();

                // If we're dealing with a Twig Extension we'll remember the class name
                // so we can dynamically add them to a TwigFactory
                if (StringHelper::endsWith($full_class, 'TwigExtension')) {
                    $twig_extensions[] = $full_class;
                }
            }
        }

        $definitions['twig_extension_classes'] = $twig_extensions;

        return $definitions;
    }

    private function createContainer(): ContainerInterface
    {
        // Setup builder
        $builder = new ContainerBuilder;
        $builder->useAnnotations(true);
        $builder->useAutowiring(true);
        $builder->ignorePhpDocErrors(true);

        // Get path to compiled container file
        $compiled_container_file = \sprintf(
            "%s/%s/%s.php",
            $this->cache_dir,
            self::CONTAINER_COMPILE_DIR,
            self::CONTAINER_COMPILE_CLASS
        );

        // Determine if the container should be compiled
        // If the we're in the dev environment it shouldn't
        $use_compiled_container = $this->config['compile_container'] && $this->env !== 'dev';

        // Tell the builder we're using a compiled container
        if ($use_compiled_container) {
            $builder->enableCompilation(
                $this->cache_dir . '/' . self::CONTAINER_COMPILE_DIR,
                self::CONTAINER_COMPILE_CLASS
            );
        }

        // Add definitions:
        // - If compiling is disabled
        // - If compiling is enabled and the container is not compiled yet
        // This is done so that the loading of definitions only happens when the container is created
        if (!$this->config['compile_container'] ||
            ($use_compiled_container && !\file_exists($compiled_container_file))
        ) {
            // Add container definitions
            $builder->addDefinitions($this->getContainerDefinitions());

            // Add Slim config if we're using the router
            if ($this->config['slim_enabled']) {
                $builder->addDefinitions(
                    SlimConfig::getFinalConfig($this->config['slim'] ?? [], $this->cache_dir)
                );
            }

            // Add custom App container definitions.
            // These *OVERWRITE* existing definitions.
            if (!empty($this->config['container_dir'])) {
                if (!DirectoryHelper::isAccessible($this->config['container_dir'])) {
                    throw new AppException('container_dir is not accessible');
                }

                foreach (\glob($this->config['container_dir'] . '/*.container.php') as $path) {
                    $builder->addDefinitions($path);
                }
            }
        }

        return $builder->build();
    }
}
