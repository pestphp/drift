<?php

declare(strict_types=1);

namespace Pest\Drift;

use Pest\Drift\Commands\MigrateCommand;
use Pest\Drift\Commands\PolishCommand;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Kernel
{
    private Application $container;

    public function __construct(Application $container)
    {
        $this->container = $container;
    }

    public function bootstrap(): void
    {
    }

    public function handle(InputInterface $input, OutputInterface $output): int
    {
        $this->bootstrap();

        $kernelApplication = new \Symfony\Component\Console\Application('Drift - A Pest migration tool');

        $kernelApplication->setCommandLoader(new ContainerCommandLoader($this->container, [
            'migrate' => MigrateCommand::class,
            'polish' => PolishCommand::class,
        ]));

        return $kernelApplication->run($input, $output);
    }

    public function terminate(): void
    {
        $this->container->terminate();
    }
}
