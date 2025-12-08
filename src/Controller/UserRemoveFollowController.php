<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserRemoveFollowController extends AbstractController
{
    #[Route(
        path: '/api/users/{userId:user.id}/follow/{followId:follow.id}',
        name: 'api_users_remove_follow',
        methods: ['DELETE'],
    )]
    public function __invoke(
        Request $request,
        User $user,
        User $follow,
        EntityManagerInterface $entityManager,
    ): Response {
        $user->removeFollow($follow);
        $entityManager->flush();

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
