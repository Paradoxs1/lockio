<?php

namespace App\Command;

use App\Service\StorageService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Doctrine\Common\Persistence\ObjectManager;

class ComputeBucketUsageCommand extends Command
{
    private $storageService;
    private $manager;

    public function __construct(StorageService $storageService, ObjectManager $manager)
    {
        $this->storageService = $storageService;
        $this->manager = $manager
        parent::__construct();
    }

    protected function configure()
    {
        $this
            ->setName('compute-bucket-usage')
            ->setDescription('Compute storage usage for buckets')
            ->setHelp('This command allows you to compute storage usage for buckets')
            ->addArgument('bucketId', InputArgument::OPTIONAL, 'Bucket name (optional).')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            'Compute storage usage for buckets',
            '============',
            '',
        ]);

        $result = $this->storageService->computeBucketUsage($input->getArgument('bucketId'));

        if ($result)
        {
            $output->writeln('Completed!');
        }
        else
        {
            $output->writeln('Error during computing storage usage for buckets!');
        }
    }
}