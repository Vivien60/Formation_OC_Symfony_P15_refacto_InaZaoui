<?php

namespace App\Controller\Admin;

use App\Entity\Media;
use App\Form\MediaType;
use App\Repository\AlbumRepository;
use App\Repository\MediaRepository;
use App\Service\MediaRemover;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

class MediaController extends AbstractController
{
    #[Route(path: '/admin/media', name: 'admin_media_index', requirements: ['id' => '\d*', 'album' => '\d*'], methods: ['GET', 'POST'])]
    #[IsCsrfTokenValid('album-media', tokenKey: '_token', methods: ['POST'])]
    public function index(
        Request $request,
        MediaRepository $mediaRepository,
        AlbumRepository $albumRepository,
        EntityManagerInterface $entityManager,
        #[Autowire(param: 'app.list.limit')] int $limit,
    ): Response {
        if ($request->isMethod('POST')) {
            $mediaRef = $request->request->get('id');
            $media = $mediaRef ? $mediaRepository->find($mediaRef) : null;

            $albumRef = $request->request->get('album');
            $album = $albumRef ? $albumRepository->find($albumRef) : null;

            $media->setAlbum($album);
            $entityManager->persist($media);
            $entityManager->flush();
        }

        $page = $request->query->getInt('page', 1);

        $criteria = [];

        if (!$this->isGranted('ROLE_ADMIN')) {
            $criteria['user'] = $this->getUser();
        }

        $medias = $mediaRepository->findBy(
            $criteria,
            ['id' => 'ASC'],
            $limit,
            25 * ($page - 1)
        );

        $total = $mediaRepository->count([]);

        $albums = $albumRepository->findAll();

        return $this->render('admin/media/index.html.twig', [
            'medias'            => $medias,
            'total'             => $total,
            'page'              => $page,
            'albums'            => $albums,
        ]);
    }

    #[Route(path: '/admin/media/add', name: 'admin_media_add')]
    public function add(Request $request, EntityManagerInterface $entityManager): Response
    {
        $media = new Media();
        $form = $this->createForm(MediaType::class, $media, ['is_admin' => $this->isGranted('ROLE_ADMIN')]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$this->isGranted('ROLE_ADMIN')) {
                $media->setUser($this->getUser());
                $media->setAlbum(null);
            }
            $media->setPath('uploads/'.md5(uniqid()).'.'.$media->getFile()->guessExtension());
            $media->getFile()->move('uploads/', $media->getPath());
            $entityManager->persist($media);
            $entityManager->flush();

            return $this->redirectToRoute('admin_media_index');
        }

        return $this->render('admin/media/add.html.twig', ['form' => $form]);
    }

    #[Route(path: '/admin/media/delete/{id}', name: 'admin_media_delete', methods: ['POST'])]
    #[IsCsrfTokenValid('delete-media', tokenKey: '_token')]
    public function delete(
        Media $media,
        EntityManagerInterface $entityManager,
        MediaRemover $remover,
    ): RedirectResponse {
        $remover->execute(entityManager: $entityManager, media: $media);
        $entityManager->flush();
        return $this->redirectToRoute('admin_media_index');
    }
}
