<?php

namespace App\Controller;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

final class HomeController extends AbstractController
{
    #[Route('/home', name: 'app_home')]
    public function index(UserRepository $repository): Response
    {
         $users = $repository->findBy(
    array(),
    array('lastLoginAt' => 'DESC'));
        return $this->render('home/index.html.twig', [
            'users' => $users,
        ]);
    }

     #[Route('/handle-users', name: 'handle_users', methods: ['POST'])]
public function handleUsers(TokenStorageInterface $tokenStorage, Request $request, EntityManagerInterface $em, UserRepository $repository, SessionInterface $session): Response
{
    
    $userIds = $request->request->all('userIds');
    $action = $request->request->get('action');

    if (empty($userIds)) {
        $this->addFlash('notice', 'No users selected or no action specified.');
        return $this->redirectToRoute('app_home');
    }

    $users = $repository->findBy(['id' => $userIds]);
    $iscurrent = false;

    foreach ($users as $user) {
        if ($user === $this->getUser()) {
            $iscurrent = true;
        }
        switch ($action) {
            case 'block':
                $user->setIsBlocked(true);
                break;
            case 'unblock':
                $user->setIsBlocked(false);
                break;
            case 'delete':
                $em->remove($user);
                break;
        }
    }

    if ($iscurrent && $action == 'delete') {
        $tokenStorage->setToken(null);
        $session->invalidate();
        $em->flush();
        return $this->redirectToRoute('app_logout');
    }

    $em->flush();
    $this->addFlash('notice', ucfirst($action) . ' completed.');

     if ($iscurrent && $action == 'block') {
        return $this->redirectToRoute('app_logout');
    }

    return $this->redirectToRoute('app_home');
}
}
