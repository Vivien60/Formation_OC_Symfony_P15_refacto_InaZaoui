<?php

namespace App\DataFixtures;

use App\Entity\Album;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AlbumFixtures extends Fixture
{
    public const int NB_ALBUMS = 10;

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < self::NB_ALBUMS; ++$i) {
            $album = new Album();
            $album->setName('Album '.$i);
            $manager->persist($album);
            $this->addReference(self::albumRef($i), $album);
        }

        $manager->flush();
    }

    public static function albumRef(int $index): string
    {
        return sprintf('album_%d', $index);
    }
}
