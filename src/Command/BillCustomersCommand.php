<?php

namespace App\Command;

use App\Service\BillingService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Common\Persistence\ObjectManager;

class BillCustomersCommand extends Command
{
    private $billingService;
    private $manager;

    public function __construct(BillingService $billingService, ObjectManager $manager)
    {
        $this->billingService = $billingService;
        $this->manager = $manager;
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('bill-customers')
            ->setDescription('Create transactions and send invoices')
            ->setHelp('This command allows you to create transactions and send invoices')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            'Creating transactions and sending invoices',
            '============',
            '',
        ]);

        $result = $this->billingService->createTransactionsSendInvoices();

        if ($result)
        {
            $output->writeln('Completed!');
        }
        else
        {
            $output->writeln('Error during creating transactions and sending invoices!');
        }
    }
}