<?php

namespace App\Controller;

use Symfony\Component\Routing\Attribute\Route;
use App\Repository\AlbumRepository;
use App\Repository\MediaRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class HomeController extends AbstractController
{
    #[Route(path: '/', name: 'home')]
    public function home()
    {
        return $this->render('front/home.html.twig');
    }

    #[Route(path: '/guests', name: 'guests')]
    public function guests(UserRepository $userRepository)
    {
        $guests = $userRepository->findBy(['admin' => false]);

        return $this->render('front/guests.html.twig', [
            'guests' => $guests,
        ]);
    }

    #[Route(path: '/guest/{id}', name: 'guest')]
    public function guest(int $id, UserRepository $userRepository)
    {
        $guest = $userRepository->find($id);

        return $this->render('front/guest.html.twig', [
            'guest' => $guest,
        ]);
    }

    #[Route(path: '/portfolio/{id}', name: 'portfolio')]
    public function portfolio(AlbumRepository $albumRepository, UserRepository $userRepository, MediaRepository $mediaRepository, ?int $id = null)
    {
        $albums = $albumRepository->findAll();
        $album = $id ? $albumRepository->find($id) : null;
        $user = $userRepository->findOneByAdmin(true);

        $medias = $album
            ? $mediaRepository->findByAlbum($album)
            : $mediaRepository->findByUser($user);

        return $this->render('front/portfolio.html.twig', [
            'albums' => $albums,
            'album' => $album,
            'medias' => $medias,
        ]);
    }

    #[Route(path: '/about', name: 'about')]
    public function about()
    {
        return $this->render('front/about.html.twig');
    }
}
