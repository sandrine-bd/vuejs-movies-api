<?php

namespace App\EventListener;

use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class AuthenticationSuccessListener
{
    public function __construct(private NormalizerInterface $normalizer, private ParameterBagInterface $parameterBag)
    {
    }

    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        $data = $event->getData();
        $user = $event->getUser();

        if (!$user instanceof UserInterface) {
            return;
        }

        $ttl = $this->parameterBag->get("lexik_jwt_authentication.token_ttl");
        $data['ttl'] = $ttl;
        $data['user'] = $this->normalizer->normalize($user,null, ["groups" => ["user:read"]]);

        $event->setData($data);
    }
}
