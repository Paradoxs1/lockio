<?php

namespace App\DataFixtures;

use App\Entity\StorageObject;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class StorageObjectFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        for ($i=0;$i<10;$i++)
        {
            $so = new StorageObject();
            $so->setUrl(getenv('MINIO_ENDPOINT_URL'));
            $so->setAccessKey(getenv('MINIO_ACCESS_KEY'));
            $so->setSecretKey(getenv('MINIO_SECRET_KEY'));
            $so->setUsedStorageBytes(0);
            $so->setUsedStorageComputedAt(new \DateTime('now'));
            $manager->persist($so);
        }

        $manager->flush();
    }
}