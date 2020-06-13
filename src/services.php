<?php

declare(strict_types=1);

use Pest\Drift\Application;
use Pest\Drift\Commands\MigrateCommand;
use Pest\Drift\Commands\PolishCommand;
use Pest\Drift\Kernel;
use Pest\Drift\RectorRunner;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $configurator): void {
    $services = $configurator->services();
    $services->defaults()->autowire()->public();

    $services->set(MigrateCommand::class);
    $services->set(PolishCommand::class);
    $services->set(Kernel::class);
    $services->set(Application::class)->synthetic();
    $services->set('vendorPath')->synthetic();
    $services->set(RectorRunner::class)
        ->args(['%vendorPath%/bin/rector']);
};
