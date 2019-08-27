<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Service\AccountingService;

class SyncCountriesBexioCommand extends Command
{
    private $accountingService;

    public function __construct(AccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('bexio:sync-countries')
            ->setDescription('Synchronization countries with Bexio accounting system.')
            ->setHelp('This command allows you to get countries from Bexio accounting system and store its ids in countries database table')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            'Synchronization countries id with Bexio accounting system',
            '============',
            '',
        ]);

        $result = $this->accountingService->syncCountries();

        if ($result)
        {
            $output->writeln('Completed!');
        }
        else
        {
            $output->writeln('Error during synchronization!');
        }
    }
}