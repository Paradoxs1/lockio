<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    const PASSWORD = 'Q1234567';

    public function load(ObjectManager $manager)
    {
        $userRepository = $manager->getRepository(User::class);

        if (!$userRepository->findOneBy(['firstname' => 'admin'])) {
            $user = new User();
            $user->setFirstname('admin');
            $user->setLastname('admin');
            $password = password_hash(self::PASSWORD, PASSWORD_BCRYPT, array('cost' => 12));
            $user->setPassword($password);
            $user->setEmail(getenv('MAILER_ADMIN_EMAIL_ADDRESS'));
            $user->setRoles(['ROLE_ADMIN']);
            $manager->persist($user);
        }
        
        $manager->flush();
    }
}