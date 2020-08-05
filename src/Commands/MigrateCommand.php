<?php

declare(strict_types=1);

namespace Pest\Drift\Commands;

use Pest\Drift\RectorRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends Command
{
    private RectorRunner $rectorRunner;

    public function __construct(RectorRunner $rectorRunner)
    {
        parent::__construct('migrate');

        $this->rectorRunner = $rectorRunner;
    }

    protected function configure(): void
    {
        $this->setDescription('Migrates PHPUnit tests to Pest');
        $this->addArgument('path', InputArgument::REQUIRED);
        $this->addOption(
            'show',
            's',
            InputOption::VALUE_NONE,
            'Show the diff instead of changing the file directly'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $path = (string) $input->getArgument('path');
        $dryRun = (bool) $input->getOption('show');

        $process = $this->rectorRunner->run($path, $dryRun);
        $output->write($process);

        return (int) $process->getExitCode();
    }
}
