<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Table(name="countries")
 * @ORM\Entity(repositoryClass="App\Repository\CountryRepository")
 */
class Country {

    /**
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;
    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;
    /**
     * @ORM\Column(name="iso_alpha2", type="string", length=2)
     */
    private $isoAlpha2;
    /**
     * @ORM\Column(name="bexio_id", type="integer", nullable=true)
     */
    private $bexioId;
    /**
     * @ORM\OneToMany(targetEntity="Organization", mappedBy="country")
     */
    private $organizations;

    public function __construct() {
        $this->organizations = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName();
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
     * @return Country
     */
    public function setName($name) {
        $this->name = $name;
        return $this;
    }

    /**
     * Get isoAlpha2
     * @return string
     */
    public function getIsoAlpha2() {
        return $this->isoAlpha2;
    }

    /**
     * Set isoAlpha2
     * @param string $isoAlpha2
     * @return Country
     */
    public function setIsoAlpha2($isoAlpha2) {
        $this->isoAlpha2 = $isoAlpha2;
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
     * @return Country
     */
    public function setBexioId($bexioId) {
        $this->bexioId = $bexioId;
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
     * @return Country
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
