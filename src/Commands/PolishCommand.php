<?php

namespace Pest\Drift\Commands;

use Pest\Drift\RectorRunner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class PolishCommand extends Command
{
    private RectorRunner $rectorRunner;

    public function __construct(RectorRunner $rectorRunner)
    {
        parent::__construct('polish');

        $this->rectorRunner = $rectorRunner;
    }

    protected function configure(): void
    {
        $this->setDescription('Polishes pest tests');
        $this->addArgument('path', InputArgument::REQUIRED);
        $this->addOption(
            'show',
            's',
            InputOption::VALUE_NONE,
            'Show the diff instead of changing the file directly'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->write("No available polishes yet.");
        return 0;
    }
}
