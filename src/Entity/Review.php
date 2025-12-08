<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\ReviewRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['review:read']],
    denormalizationContext: ['groups' => ['review:write']],
    order: ['updatedAt' => 'DESC'],
)]
#[Get()]
#[Post(securityPostDenormalize: "is_granted('ROLE_USER') and object.getUser() == user")]
#[Patch(securityPostDenormalize: "is_granted('ROLE_USER') and object.getUser() == user")]
#[Delete(securityPostDenormalize: "is_granted('ROLE_USER') and object.getUser() == user")]
#[ApiResource(
    uriTemplate: '/users/{userId}/reviews',
    operations: [ new GetCollection() ],
    uriVariables: ['userId' => new Link(toProperty: 'user', fromClass: User::class)],
    normalizationContext: ['groups' => ['review:read']],
    order: ['updatedAt' => 'DESC']
)]
#[ApiResource(
    uriTemplate: '/movies/{movieId}/reviews',
    operations: [ new GetCollection() ],
    uriVariables: ['movieId' => new Link(toProperty: 'movie', fromClass: Movie::class)],
    normalizationContext: ['groups' => ['review:read']],
    order: ['updatedAt' => 'DESC']
)]
#[ORM\Entity(repositoryClass: ReviewRepository::class)]
class Review
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: false)]
    private ?string $content = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'reviews')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Movie $movie = null;

    #[Groups(['review:read', 'feed:read'])]
    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups(['review:read', 'feed:read'])]
    public function getContent(): ?string
    {
        return $this->content;
    }

    #[Groups(['review:write'])]
    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    #[Groups(['review:read', 'feed:read'])]
    public function getUser(): ?User
    {
        return $this->user;
    }

    #[Groups(['review:write'])]
    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    #[Groups(['review:read', 'feed:read'])]
    public function getMovie(): ?Movie
    {
        return $this->movie;
    }

    #[Groups(['review:write'])]
    public function setMovie(?Movie $movie): static
    {
        $this->movie = $movie;

        return $this;
    }
}
