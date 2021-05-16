<?php

namespace Xenokore\App\Tests;

use Xenokore\App\App;

use Twig\Environment as TwigEnvironment;

use PHPUnit\Framework\TestCase;
use Xenokore\App\Tests\Data\Test\TestClass;

use Psr\Container\ContainerInterface;
use Slim\Interfaces\RouteInterface;

class AppTest extends TestCase
{
    /**
     * The Xeno App.
     *
     * @var App
     */
    protected $app;

    protected function setUp(): void
    {
        $this->app = new App([
            'src_dir'      => __DIR__ . '/data/src',

            'slim_enabled' => true,

            'twig_enabled' => true,
            'views_dir'    => __DIR__ . '/data/views',
        ]);
    }

    public function testContainer()
    {
        /** @var ContainerInterface $container */
        $container = $this->app->getContainer();
        $this->assertInstanceOf(ContainerInterface::class, $container);

        /** @var TestClass */
        $test_class = $container->get(TestClass::class);
        $this->assertInstanceOf(TestClass::class, $test_class);
        $this->assertEquals('success', $test_class->getTestVar());
    }

    public function testTwig()
    {
        /** @var ContainerInterface $container */
        $container = $this->app->getContainer();

        /** @var TwigEnvironment */
        $twig = $container->get(TwigEnvironment::class);
        $this->assertInstanceOf(TwigEnvironment::class, $twig);

        $output = $twig->render('test.html.twig');
        $this->assertEquals('Twig test success', \trim($output));
    }

    public function testSlim()
    {
        /** @var \Slim\App */
        $router = $this->app->getSlimRouter();
        $this->assertInstanceOf(\Slim\App::class, $router);
    }
}
