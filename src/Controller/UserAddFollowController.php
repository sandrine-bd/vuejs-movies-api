<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class UserAddFollowController extends AbstractController
{
    #[Route(
        path: '/api/users/{userId:user.id}/follow/{followId:follow.id}',
        name: 'api_users_add_follow',
        methods: ['POST'],
    )]
    public function __invoke(
        Request $request,
        User $user,
        User $follow,
        EntityManagerInterface $entityManager,
    ): Response {
        $user->addFollow($follow);
        $entityManager->flush();

        return $this->json($user, Response::HTTP_CREATED, [], ["groups"=> ["user:read"]]);
    }
}
