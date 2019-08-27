<?php

namespace App\Service;

use App\Entity\StorageObject;
use Aws\S3\S3Client;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

class StorageService
{
    private $endpointUrl;
    private $accessKey;
    private $secretKey;
    private $s3Client;
    private $logger;
    private $em;

    const LOG_PREFIX = 'MINIO';

    public function __construct(LoggerInterface $logger, EntityManagerInterface $em)
    {
        $this->logger = $logger;
        $this->em = $em;
    }

    private function createClient()
    {
        try {
            $this->s3Client = new S3Client([
                'version' => 'latest',
                'region'  => 'us-east-1',
                'endpoint' => $this->endpointUrl,
                'use_path_style_endpoint' => true,
                'credentials' => [
                    'key'    => $this->accessKey,
                    'secret' => $this->secretKey,
                ],
            ]);

            return true;
        } catch (\Exception $e) {
            $this->addErrorLog([
                'message' => $e->getMessage()
            ]);

            return false;
        }
    }

    private function addErrorLog($error)
    {
        $error = array_merge(['prefix' => self::LOG_PREFIX], $error);
        $this->logger->error(json_encode($error));
    }

    private function setConfig($so)
    {
        if (!$so) {
            return false;
        }

        $this->endpointUrl = $so->getUrl();
        $this->accessKey = $so->getAccessKey();
        $this->secretKey = $so->getSecretKey();

        return $this->createClient();
    }

    public function listBuckets($so) {
        if (!$this->setConfig($so))
            return false;

        try {
            return $this->s3Client->listBuckets();
        } catch (\Exception $e) {
            $this->addErrorLog([
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function createBucket($userOrganization) {
        $so = $this->getFreeStorageObject();
        if (!$so) {
            $this->addErrorLog([
                'message' => 'No more free buckets.'
            ]);

            return false;
        }

        if (!$this->setConfig($so))
            return false;

        $name = md5($userOrganization->getId() . microtime() );

        try {
            $bucket = $this->s3Client->createBucket([
                'Bucket' => $name,
            ]);

            $so->setBucketId($name);
            $this->em->persist($so);

            $this->em->flush();

            return $so;
        } catch (\Exception $e) {
            $this->addErrorLog([
                'message' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function getBucketSizeInGB($organization)
    {
        $bucketSize = 0;
        $sos = $organization->getStorageObjects();
        foreach ($sos as $so) {
            $bucketSize += $so->getUsedStorageBytes();
        }

        return $bucketSize / 1024 / 1024 / 1024;
    }

    public function computeBucketUsage($bucketId = null)
    {
        try {
            if ($bucketId) {
                $sos = $this->em->getRepository(StorageObject::class)->findBy(['bucketId' => $bucketId]);
            } else {
                $sos = $this->em->getRepository(StorageObject::class)->findAll();
            }

            foreach ($sos as $so) {
                $bucketSize = 0;

                if (!$this->setConfig($so))
                    return false;

                $listObjects = $this->s3Client->listObjectsV2(['Bucket' => $so->getBucketId()]);
                if ($listObjects['Contents']) {
                    foreach ($listObjects['Contents'] as $object) {
                        $bucketSize += $object['Size'];
                    }
                }

                $so->setUsedStorageBytes($bucketSize);
                $so->setUsedStorageComputedAt(new \DateTime());
                $this->em->persist($so);
            }

            $this->em->flush();

            return true;
        } catch (\Exception $e) {
            $this->addErrorLog([
                'message' => $e->getMessage()
            ]);

            return false;
        }
    }

    public function registerStreamWrapper($so) {
        if (!$this->setConfig($so))
            return false;

        try {
            return $this->s3Client->registerStreamWrapper();
        } catch (\Exception $e) {
            $this->addErrorLog([
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function doesObjectExist($so, $id, $name) {
        if (!$this->setConfig($so))
            return false;

        try {
            return $this->s3Client->doesObjectExist($id, $name);
        } catch (\Exception $e) {
            $this->addErrorLog([
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getObject($so, $id, $name) {
        if (!$this->setConfig($so))
            return false;

        try {
            return $this->s3Client->getObject([
                'Bucket' => $id,
                'Key'    => $name
            ]);
        } catch (\Exception $e) {
            $this->addErrorLog([
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getListObjects($so) {
        if (!$this->setConfig($so))
            return false;

        try {
            $listObjects =  $objects = $this->s3Client->listObjects([
                'Bucket' => $so->getBucketId()
            ]);

            if ($listObjects['Contents']) {
                $listObjects = $this->convertListObjects($listObjects['Contents']);
            } else {
                $listObjects = false;
            }

            return $listObjects;
        } catch (\Exception $e) {
            $this->addErrorLog([
                'message' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function convertListObjects(array $listObjects): array
    {
        $objects = $results = [];

        foreach ($listObjects as $object) {
            //Add value rounding sizeFile to object
            $object = $this->convertFileSizeUnits($object);
            $results[$object['Key']] = $object;
        }

        //Create from a one-dimensional array multidimensional for folder tree
        foreach ($results as $key => $item) {
            $reference =& $objects;

            foreach (explode('/', $key) as $value) {
                if (!isset($reference[$value])) {
                    $reference[$value] = [];
                }
                $reference =& $reference[$value];
            }
            $reference = $item;
        }

        return $objects;
    }

    private function convertFileSizeUnits(array $object): array
    {
        $units = ['Bytes', 'Kb', 'Mb', 'Gb'];

        if ($object['Size'] != 0) {
            $size = round($object['Size'] / pow(1024, ($i = floor(log($object['Size'], 1024)))), 2).' '.$units[$i];
        } else {
            $size = $object['Size'] . ' Bytes' ;
        }

        $object['sizeFile'] = $size;

        return $object;
    }

    private function getFreeStorageObject()
    {
        return $this->em->getRepository(StorageObject::class)->findOneBy(['bucketId' => null]);
    }
}