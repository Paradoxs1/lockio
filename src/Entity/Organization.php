<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="organizations")
 * @ORM\Entity(repositoryClass="App\Repository\OrganizationRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class Organization {

    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="profileDetails.not_blank")
     * @Assert\Regex("/^(.){3,50}$/", message="profileDetails.organization_name")
     */
    private $name;
    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="profileDetails.not_blank")
     * @Assert\Regex("/^(.){3,50}$/", message="profileDetails.organization_address1")
     */
    private $address1;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Regex("/^(.){3,50}$/", message="profileDetails.organization_address2")
     */
    private $address2;
    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Assert\Regex("/^(.){3,50}$/", message="profileDetails.organization_address3")
     */
    private $address3;
    /**
     * @ORM\Column(type="string", length=11)
     * @Assert\NotBlank(message="profileDetails.not_blank")
     * @Assert\Regex("/^[a-zA-Z0-9]{4,10}$/", message="profileDetails.organization_zip")
     */
    private $zip;
    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank(message="profileDetails.not_blank")
     * @Assert\Regex("/^(.){2,50}$/", message="profileDetails.organization_city")
     */
    private $city;
    /**
     * @ORM\Column(name="cc_token", type="string", length=255, nullable=true)
     */
    private $ccToken;
    /**
     * @ORM\Column(name="trial_ends_at", type="datetime", nullable=true)
     */
    private $trialEndsAt;
    /**
     * @ORM\Column(name="billing_duedate", type="datetime", nullable=true)
     */
    private $billingDueDate;
    /**
     * @ORM\Column(name="bexio_id", type="integer", nullable=true)
     */
    private $bexioId;
    /**
     * @ORM\Column(name="created_at", type="datetime")
     */
    private $createdAt;
    /**
     * @ORM\Column(name="deleted_at", type="datetime", nullable=true)
     */
    private $deletedAt;
    /**
     * @ORM\ManyToOne(targetEntity="Country", inversedBy="organizations")
     * @Assert\NotBlank(message="profileDetails.organization_country")
     */
    private $country;
    /**
     * @ORM\ManyToMany(targetEntity="StorageObject", inversedBy="organizations")
     */
    private $storageObjects;
    /**
     * @ORM\OneToMany(targetEntity="UserOrganization", mappedBy="organization")
     */
    private $userOrganizations;


    public function __construct() {
        $this->storageObjects = new ArrayCollection();
        $this->userOrganizations = new ArrayCollection();
    }

    /**
     * @return string
     */
    public function __toString() {
        return strval($this->id);
    }

    /**
     * Get id
     * @return integer
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Get name
     * @return string
     */
    public function getName() {
        return $this->name;
    }

    /**
     * Set name
     * @param string $name
     * @return Organization
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * Get address1
     * @return string
     */
    public function getAddress1() {
        return $this->address1;
    }

    /**
     * Set address1
     * @param string $address1
     * @return Organization
     */
    public function setAddress1($address1) {
        $this->address1 = $address1;
        return $this;
    }

    /**
     * Get address2
     * @return string
     */
    public function getAddress2() {
        return $this->address2;
    }

    /**
     * Set address2
     * @param string $address2
     * @return Organization
     */
    public function setAddress2($address2) {
        $this->address2 = $address2;
        return $this;
    }

    /**
     * Get address3
     * @return string
     */
    public function getAddress3() {
        return $this->address3;
    }

    /**
     * Set address3
     * @param string $address3
     * @return Organization
     */
    public function setAddress3($address3) {
        $this->address3 = $address3;
        return $this;
    }

    /**
     * Get zip
     * @return string
     */
    public function getZip() {
        return $this->zip;
    }

    /**
     * Set zip
     * @param string $zip
     * @return Organization
     */
    public function setZip($zip) {
        $this->zip = $zip;
        return $this;
    }

    /**
     * Get city
     * @return string
     */
    public function getCity() {
        return $this->city;
    }

    /**
     * Set city
     * @param string $city
     * @return Organization
     */
    public function setCity($city) {
        $this->city = $city;
        return $this;
    }

    /**
     * Get ccToken
     * @return string
     */
    public function getCcToken() {
        return $this->ccToken;
    }

    /**
     * Set ccToken
     * @param string $ccToken
     * @return Organization
     */
    public function setCcToken($ccToken) {
        $this->ccToken = $ccToken;
        return $this;
    }

    /**
     * Get trialEndsAt
     * @return \DateTime
     */
    public function getTrialEndsAt() {
        return $this->trialEndsAt;
    }

    /**
     * Set trialEndsAt
     * @param \DateTime $trialEndsAt
     * @return Organization
     */
    public function setTrialEndsAt($trialEndsAt) {
        $this->trialEndsAt = $trialEndsAt;
        return $this;
    }

    /**
     * Get billingDueDate
     * @return \DateTime
     */
    public function getBillingDueDate() {
        return $this->billingDueDate;
    }

    /**
     * Set billingDueDate
     * @param \DateTime $billingDueDate
     * @return Organization
     */
    public function setBillingDueDate($billingDueDate) {
        $this->billingDueDate = $billingDueDate;
        return $this;
    }

    /**
     * Get bexioId
     * @return integer
     */
    public function getBexioId() {
        return $this->bexioId;
    }

    /**
     * Set bexioId
     * @param integer $bexioId
     * @return Organization
     */
    public function setBexioId($bexioId) {
        $this->bexioId = $bexioId;
        return $this;
    }

    /**
     * Get createdAt
     * @return \DateTime
     */
    public function getCreatedAt() {
        return $this->createdAt;
    }

    /**
     * Set createdAt
     * @ORM\PrePersist
     */
    public function setCreatedAt($createdAt) {
        $this->createdAt = new \DateTime();
    }

    /**
     * Get deletedAt
     * @return \DateTime
     */
    public function getDeletedAt() {
        return $this->deletedAt;
    }

    /**
     * Set deletedAt
     */
    public function setDeletedAt($deletedAt) {
        $this->deletedAt = new \DateTime();
    }

    /**
     * Get country
     * @return Country
     */
    public function getCountry() {
        return $this->country;
    }

    /**
     * Set country
     * @param Country $country
     * @return Organization
     */
    public function setCountry(Country $country) {
        $this->country = $country;
        return $this;
    }

    /**
     * Get first user
     * @return \App\Entity\User
     */
    public function getFirstUser() {
        $userOrganizations = $this->getUserOrganizations();
        foreach ($userOrganizations as $userOrganization) {
            return $userOrganization->getUser();
        }
        
        return null;
    }

    /**
     * Get first storageObject
     * @return \App\Entity\StorageObject
     */
    public function getFirstStorageObject() {
        $storageObjects = $this->getStorageObjects();
        foreach ($storageObjects as $storageObject) {
            return $storageObject;
        }

        return null;
    }

    /**
     * Get storageObjects
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getStorageObjects() {
        return $this->storageObjects;
    }

    /**
     * Add storageObject
     * @param StorageObject $storageObjects
     * @return Organization
     */
    public function addStorageObject(StorageObject $storageObjects) {
        $this->storageObjects[] = $storageObjects;
        return $this;
    }

    /**
     * Remove storageObject
     * @param StorageObject $storageObjects
     */
    public function removeStorageObject(StorageObject $storageObjects) {
        $this->storageObjects->removeElement($storageObjects);
    }

    /**
     * Get userOrganizations
     * @return \Doctrine\Common\Collections\Collection
     */
    public function getUserOrganizations() {
        return $this->userOrganizations;
    }

    /**
     * Add userOrganizations
     * @param UserOrganization $userOrganizations
     * @return Organization
     */
    public function addUserOrganization(UserOrganization $userOrganizations) {
        $this->userOrganizations[] = $userOrganizations;
        return $this;
    }

    /**
     * Remove userOrganizations
     * @param UserOrganization $userOrganizations
     */
    public function removeUserOrganization(UserOrganization $userOrganizations) {
        $this->userOrganizations->removeElement($userOrganizations);
    }

}
