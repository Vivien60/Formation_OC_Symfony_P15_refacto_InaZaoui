<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Media;
use App\Util\PathBuilder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Gère la suppression des fichiers médias et de leurs entités correspondantes.
 */
class MediaRemover
{
    public function __construct(
        #[Autowire(param: 'app.public_dir')] private string $mediaRootDir,
        private PathBuilder $pathBuiler,
    ) {
    }

    /**
     * Supprime effectivement le média, à la fois en base via l'entité, et le fichier.
     */
    public function execute(EntityManagerInterface $entityManager, Media $media): void
    {
        $this->removeFile($media->getPath());
        $entityManager->remove($media);
    }

    private function removeFile(string $filepathRelative): void
    {
        $path = $this->pathBuiler->buildPath($this->mediaRootDir, $filepathRelative);
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
