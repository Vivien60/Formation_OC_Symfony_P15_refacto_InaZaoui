<?php

namespace App\Controller\Admin;

use Symfony\Component\Routing\Attribute\Route;
use App\Entity\Album;
use App\Form\AlbumType;
use App\Repository\AlbumRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class AlbumController extends AbstractController
{
    #[Route(path: '/admin/album', name: 'admin_album_index')]
    public function index(AlbumRepository $albumRepository)
    {
        $albums = $albumRepository->findAll();

        return $this->render('admin/album/index.html.twig', ['albums' => $albums]);
    }

    #[Route(path: '/admin/album/add', name: 'admin_album_add')]
    public function add(Request $request, EntityManagerInterface $entityManager)
    {
        $album = new Album();
        $form = $this->createForm(AlbumType::class, $album);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($album);
            $entityManager->flush();

            return $this->redirectToRoute('admin_album_index');
        }

        return $this->render('admin/album/add.html.twig', ['form' => $form]);
    }

    #[Route(path: '/admin/album/update/{id}', name: 'admin_album_update')]
    public function update(Request $request, int $id, AlbumRepository $albumRepository, EntityManagerInterface $entityManager)
    {
        $album = $albumRepository->find($id);
        $form = $this->createForm(AlbumType::class, $album);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('admin_album_index');
        }

        return $this->render('admin/album/update.html.twig', ['form' => $form]);
    }

    #[Route(path: '/admin/album/delete/{id}', name: 'admin_album_delete')]
    public function delete(int $id, AlbumRepository $albumRepository, EntityManagerInterface $entityManager)
    {
        $media = $albumRepository->find($id);
        $entityManager->remove($media);
        $entityManager->flush();

        return $this->redirectToRoute('admin_album_index');
    }
}
