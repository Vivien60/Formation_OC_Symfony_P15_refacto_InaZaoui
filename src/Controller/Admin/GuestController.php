<?php

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\MediaRemover;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsCsrfTokenValid;

final class GuestController extends AbstractController
{
    #[Route('/admin/guest', name: 'admin_guest_index')]
    public function index(UserRepository $repository): Response
    {
        $users = $repository->findBy(['admin' => false]);
        return $this->render('admin/guest/index.html.twig', [
            'guests' => $users,
        ]);
    }

    #[Route('/admin/guest/delete/{id}', name: 'admin_guest_delete', methods: ['POST'])]
    #[IsCsrfTokenValid('delete-guest', tokenKey: '_token')]
    public function delete(User $user, EntityManagerInterface $entityManager, MediaRemover $mediaRemover): RedirectResponse
    {
        $entityManager->remove($user);
        foreach ($user->getMedias() as $media) {
            $mediaRemover->execute(entityManager: $entityManager, media: $media);
        }
        $entityManager->flush();
        return $this->redirectToRoute('admin_guest_index');
    }
}
