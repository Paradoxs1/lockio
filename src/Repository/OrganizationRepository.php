<?php

namespace App\Repository;

use App\Entity\Organization;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class OrganizationRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, Organization::class);
    }

    /**
     * @return array
     * @throws \Exception
     */
    public function getOrganizationsForBilling(): array
    {
        $qb = $this->createQueryBuilder('o')
            ->select('o')
            ->where('o.trialEndsAt IS NULL')
            ->andWhere('o.billingDueDate < :tomorrow')
            ->setParameter('tomorrow', new \DateTime('tomorrow'))
            ->orderBy('o.billingDueDate', 'DESC');

        return $qb->getQuery()->getResult();
    }

    /**
     * @param $trialEndsAt
     * @param $dateNow
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getDaysTrialPeriodBanner($trialEndsAt, $dateNow)
    {
        return $this->createQueryBuilder('o')
            ->select('DATE_DIFF(:daysTrialBanner,:dateNow) AS days')
            ->where('o.trialEndsAt = :trialEndsAt')
            ->setParameter('daysTrialBanner', $trialEndsAt->format('Y-m-d'))
            ->setParameter('dateNow', $dateNow)
            ->setParameter('trialEndsAt', $trialEndsAt)
            ->getQuery()
            ->getOneOrNullResult();
    }

}