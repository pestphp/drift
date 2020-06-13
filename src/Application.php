<?php

namespace Pest\Drift;


use Exception;
use Psr\Container\ContainerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

class Application implements ContainerInterface
{
    private ContainerInterface $container;

    public function __construct(string $vendorPath, string $path = __DIR__)
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->setParameter('vendorPath', $vendorPath);

        $loader = new PhpFileLoader($containerBuilder, new FileLocator($path));
        $loader->load('services.php');

        $containerBuilder->compile();

        $containerBuilder->set(Application::class, $this);

        $this->container = $containerBuilder;
    }

    /**
     * @param string $id
     *
     * @return mixed|null
     *
     * @throws Exception
     */
    public function get($id)
    {
        return $this->container->get($id);
    }

    /**
     * @param string $id
     */
    public function has($id): bool
    {
        return $this->container->has($id);
    }

    public function terminate(): void
    {
    }
}
