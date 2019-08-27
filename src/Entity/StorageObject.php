<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Table(name="storage_objects")
 * @ORM\Entity(repositoryClass="App\Repository\StorageObjectRepository")
 */
class StorageObject {

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $url;
    /**
     * @ORM\Column(name="access_key", type="string", length=255)
     */
    private $accessKey;
    /**
     * @ORM\Column(name="secret_key", type="string", length=255)
     */
    private $secretKey;
    /**
     * @ORM\Column(name="bucket_id", type="string", length=255, nullable=true)
     */
    private $bucketId;
    /**
     * @ORM\Column(name="used_storage_bytes", type="bigint", nullable=true)
     */
    private $usedStorageBytes;
    /**
     * @ORM\Column(name="used_storage_computed_at", type="datetime", nullable=true)
     */
    private $usedStorageComputedAt;
    /**
     * @ORM\ManyToMany(targetEntity="Organization", mappedBy="storageObjects")
     */
    private $organizations;

    public function __construct() {
        $this->organizations = new ArrayCollection();
    }

    public function __toString()
    {
        return strval($this->getId());
    }

    /**
     * Get id
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get url
     * @return string
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * Set url
     * @param string $url
     * @return StorageObject
     */
    public function setUrl($url) {
        $this->url = $url;
        return $this;
    }

    /**
     * Get accessKey
     * @return string
     */
    public function getAccessKey() {
        return $this->accessKey;
    }

    /**
     * Set accessKey
     * @param string $accessKey
     * @return StorageObject
     */
    public function setAccessKey($accessKey) {
        $this->accessKey = $accessKey;
        return $this;
    }

    /**
     * Get secretKey
     * @return string
     */
    public function getSecretKey() {
        return $this->secretKey;
    }

    /**
     * Set secretKey
     * @param string $secretKey
     * @return StorageObject
     */
    public function setSecretKey($secretKey) {
        $this->secretKey = $secretKey;
        return $this;
    }

    /**
     * Get bucketId
     * @return string
     */
    public function getBucketId() {
        return $this->bucketId;
    }

    /**
     * Set bucketId
     * @param string $bucketId
     * @return StorageObject
     */
    public function setBucketId($bucketId) {
        $this->bucketId = $bucketId;
        return $this;
    }

    /**
     * Get usedStorageBytes
     * @return bigint
     */
    public function getUsedStorageBytes() {
        return $this->usedStorageBytes;
    }

    /**
     * Set usedStorageBytes
     * @param bigint $usedStorageBytes
     * @return StorageObject
     */
    public function setUsedStorageBytes($usedStorageBytes) {
        $this->usedStorageBytes = $usedStorageBytes;
        return $this;
    }

    /**
     * Get usedStorageComputedAt
     * @return \DateTime
     */
    public function getUsedStorageComputedAt() {
        return $this->usedStorageComputedAt;
    }

    /**
     * Set usedStorageComputedAt
     * @param \DateTime $usedStorageComputedAt
     * @return StorageObject
     */
    public function setUsedStorageComputedAt($usedStorageComputedAt) {
        $this->usedStorageComputedAt = $usedStorageComputedAt;
        return $this;
    }

    /**
     * Get organizations
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getOrganizations() {
        return $this->organizations;
    }

    /**
     * Add organizations
     * @param Organization $organizations
     * @return StorageObject
     */
    public function addOrganization(Organization $organizations) {
        $this->organizations[] = $organizations;
        return $this;
    }

    /**
     * Remove organizations
     * @param Organization $organizations
     */
    public function removeOrganization(Organization $organizations) {
        $this->organizations->removeElement($organizations);
    }
}
