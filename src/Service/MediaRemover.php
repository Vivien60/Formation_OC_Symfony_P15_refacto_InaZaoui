<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Media;
use App\Util\PathBuilder;
use Doctrine\ORM\EntityManager;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaRemover
{
    public function __construct(
        #[Autowire(param: 'app.public_dir')] private string $mediaRootDir,
        private PathBuilder $pathBuiler
    )
    {
    }

    public function execute(EntityManager $entityManager, Media $media): void
    {
        $this->removeFile($media->getPath());
        $entityManager->remove($media);
    }

    private function removeFile(string $filepathRelative): void
    {
        $path = $this->pathBuiler->buildPath($this->mediaRootDir, $filepathRelative);
        if( file_exists($path))
            unlink($path);
    }
}
