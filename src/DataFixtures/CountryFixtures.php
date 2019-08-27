<?php

namespace App\DataFixtures;

use App\Entity\Country;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class CountryFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $countryRepository = $manager->getRepository(Country::class);

        if (!$countryRepository->findOneBy(['isoAlpha2' => 'CH'])) {
            $country = new Country();
            $country->setName('Switzerland');
            $country->setIsoAlpha2('CH');
            $manager->persist($country);
        }

        if (!$countryRepository->findOneBy(['isoAlpha2' => 'AT'])) {
            $country = new Country();
            $country->setName('Austria');
            $country->setIsoAlpha2('AT');
            $manager->persist($country);
        }

        if (!$countryRepository->findOneBy(['isoAlpha2' => 'DE'])) {
            $country = new Country();
            $country->setName('Germany');
            $country->setIsoAlpha2('DE');
            $manager->persist($country);
        }

        if (!$countryRepository->findOneBy(['isoAlpha2' => 'FR'])) {
            $country = new Country();
            $country->setName('France');
            $country->setIsoAlpha2('FR');
            $manager->persist($country);
        }

        if (!$countryRepository->findOneBy(['isoAlpha2' => 'NL'])) {
            $country = new Country();
            $country->setName('Netherlands');
            $country->setIsoAlpha2('NL');
            $manager->persist($country);
        }

        if (!$countryRepository->findOneBy(['isoAlpha2' => 'GB'])) {
            $country = new Country();
            $country->setName('United Kingdom');
            $country->setIsoAlpha2('GB');
            $manager->persist($country);
        }

        if (!$countryRepository->findOneBy(['isoAlpha2' => 'US'])) {
            $country = new Country();
            $country->setName('United States');
            $country->setIsoAlpha2('US');
            $manager->persist($country);
        }

        $manager->flush();
    }
}