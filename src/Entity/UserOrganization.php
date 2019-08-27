<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="users_organizations")
 * @ORM\Entity(repositoryClass="App\Repository\UserOrganizationRepository")
 */
class UserOrganization {

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\ManyToOne(targetEntity="Organization", inversedBy="userOrganizations")
     */
    private $organization;
    /**
     * @ORM\ManyToOne(targetEntity="User", inversedBy="userOrganizations")
     */
    private $user;

    public function __construct() {

    }

    public function __toString() {
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
     * Get organization
     * @return Organization
     */
    public function getOrganization() {
        return $this->organization;
    }

    /**
     * Set organization
     * @param Organization $organization
     * @return UserOrganization
     */
    public function setOrganization(Organization $organization) {
        $this->organization = $organization;
        return $this;
    }

    /**
     * Get user
     * @return User
     */
    public function getUser() {
        return $this->user;
    }

    /**
     * Set user
     * @param User $user
     * @return UserOrganization
     */
    public function setUser(User $user) {
        $this->user = $user;
        return $this;
    }
}
