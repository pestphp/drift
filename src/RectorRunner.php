<?php

namespace Pest\Drift;

use Symfony\Component\Process\Process;

class RectorRunner
{
    private string $binPath;

    public function __construct(string $binPath)
    {
        $this->binPath = $binPath;
    }


    public function run(string $path, bool $dryRun = false): Process
    {
        $process = new \Symfony\Component\Process\Process(array_filter([
            $this->binPath,
            'process',
            $path,
            $dryRun ? '--dry-run' : null,
            '--config',
            __DIR__ . '/../config/phpunit-to-pest.yml',
            '--ansi',
            '--no-progress-bar',
        ]));

        $process->run();

        return $process;
    }
}
