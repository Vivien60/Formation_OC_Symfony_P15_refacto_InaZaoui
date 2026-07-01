<?php

namespace App\DataFixtures;

use App\Entity\Media;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class MediaFixtures extends Fixture implements DependentFixtureInterface
{
    public const int NB_MEDIA_PER_ALBUM = 4;

    public function getDependencies(): array
    {
        return [UserFixtures::class, AlbumFixtures::class];
    }

    public function load(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');

        for ($i = 0; $i < AlbumFixtures::NB_ALBUMS; ++$i) {
            /** @var \App\Entity\Album $album */
            $album = $this->getReference(AlbumFixtures::albumRef($i), \App\Entity\Album::class);

            for ($j = 0; $j < self::NB_MEDIA_PER_ALBUM; ++$j) {
                /** @var \App\Entity\User $user */
                $user = $this->getReference(UserFixtures::userRef($j % UserFixtures::NB_GUESTS), \App\Entity\User::class);

                $media = new Media();
                $titlePrefix = sprintf('Photo %d - ', $i * self::NB_MEDIA_PER_ALBUM + $j);
                $media->setTitle($titlePrefix.$faker->sentence(4));
                $media->setPath(sprintf('photos/album_%d/photo_%d.jpg', $i, $j));
                if ($i > 0) {
                    $media->setAlbum($album);
                }
                $media->setUser($user);
                $manager->persist($media);
            }
        }

        $manager->flush();
    }
}
