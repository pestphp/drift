<?php

declare(strict_types=1);

use Pest\Drift\Pest\PestCollectingRector;
use Pest\Drift\PestCollector;
use Pest\Drift\PHPUnit\Class_\CustomTestCaseToUsesRector;
use Pest\Drift\PHPUnit\Class_\PhpDocGroupOnClassToFileScopeGroupRector;
use Pest\Drift\PHPUnit\Class_\RemovePHPUnitClassRector;
use Pest\Drift\PHPUnit\Class_\TraitUsesToUsesRector;
use Pest\Drift\PHPUnit\ClassMethod\AfterClassToAfterAllRector;
use Pest\Drift\PHPUnit\ClassMethod\BeforeClassToBeforeAllRector;
use Pest\Drift\PHPUnit\ClassMethod\DataProviderRector;
use Pest\Drift\PHPUnit\ClassMethod\HelperMethodRector;
use Pest\Drift\PHPUnit\ClassMethod\MethodToPestTestRector;
use Pest\Drift\PHPUnit\ClassMethod\SetUpToBeforeEachRector;
use Pest\Drift\PHPUnit\ClassMethod\TearDownToAfterEachRector;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    $services = $containerConfigurator->services();

    $services->set(MethodToPestTestRector::class);

    $services->set(TraitUsesToUsesRector::class);

    $services->set(SetUpToBeforeEachRector::class);

    $services->set(TearDownToAfterEachRector::class);

    $services->set(AfterClassToAfterAllRector::class);

    $services->set(BeforeClassToBeforeAllRector::class);

    $services->set(PhpDocGroupOnClassToFileScopeGroupRector::class);

    $services->set(DataProviderRector::class);

    $services->set(HelperMethodRector::class);

    $services->set(CustomTestCaseToUsesRector::class);

    $services->set(RemovePHPUnitClassRector::class);

    $services->set(PestCollectingRector::class);

    $services->set(PestCollector::class);
};
