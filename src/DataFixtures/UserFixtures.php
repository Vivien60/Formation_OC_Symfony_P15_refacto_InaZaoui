<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const int NB_GUESTS = 8;

    public function __construct(private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        $admin = new User();
        $admin->setEmail('admin@email.com');
        $admin->setLogin('admin');
        $admin->setName('Admin');
        $admin->setAdmin(true);
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->hasher->hashPassword($admin, 'password'));
        $manager->persist($admin);

        for ($i = 0; $i < self::NB_GUESTS; ++$i) {
            $guest = new User();
            $email = sprintf('user+%d@email.com', $i);
            $guest->setEmail($email);
            $guest->setLogin($email);
            $name = sprintf('Invité %d', $i);
            $guest->setName($name);
            $guest->setDescription($faker->paragraph());
            $guest->setAdmin(false);
            if (($i + 1) % 3 == 0) {
                $guest->revocate();
            }
            $guest->setRoles([]);
            $guest->setPassword($this->hasher->hashPassword($guest, 'password'));
            $manager->persist($guest);
            $this->addReference(self::userRef($i), $guest);
        }

        $manager->flush();
    }

    public static function userRef(int $index): string
    {
        return sprintf('user_%d', $index);
    }
}
