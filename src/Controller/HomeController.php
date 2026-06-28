<?php

namespace App\Controller;

use App\Repository\AlbumRepository;
use App\Repository\MediaRepository;
use App\Repository\UserRepository;
use App\Service\GetActiveUsers;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    #[Route(path: '/', name: 'home')]
    public function home()
    {
        return $this->render('front/home.html.twig');
    }

    #[Route(path: '/guests', name: 'guests')]
    public function guests(GetActiveUsers $getActiveUsers): Response
    {
        $guests = $getActiveUsers->all();

        return $this->render('front/guests.html.twig', [
            'guests' => $guests,
        ]);
    }

    #[Route(path: '/guest/{id}', name: 'guest')]
    public function guest(int $id, GetActiveUsers $getActiveUsers): Response
    {
        $guest = $getActiveUsers->one($id);

        return $this->render('front/guest.html.twig', [
            'guest' => $guest,
        ]);
    }

    #[Route(path: '/portfolio/{id}', name: 'portfolio')]
    public function portfolio(
        AlbumRepository $albumRepository,
        UserRepository $userRepository,
        MediaRepository $mediaRepository,
        ?int $id = null,
    ): Response {
        $albums = $albumRepository->findAll();
        $album = $id ? $albumRepository->find($id) : null;
        $user = $userRepository->findOneByAdmin(true);

        $medias = $album
            ? $mediaRepository->findByAlbum($album)
            : $mediaRepository->findByUser($user);

        return $this->render('front/portfolio.html.twig', [
            'albums' => $albums,
            'album'  => $album,
            'medias' => $medias,
        ]);
    }

    #[Route(path: '/about', name: 'about')]
    public function about()
    {
        return $this->render('front/about.html.twig');
    }
}
