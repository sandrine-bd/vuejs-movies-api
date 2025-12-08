<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\RatingRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['rating:read']],
    denormalizationContext: ['groups' => ['rating:write']],
    order: ['updatedAt' => 'DESC'],
)]
#[Get()]
#[Post(securityPostDenormalize: "is_granted('ROLE_USER') and object.getUser() == user")]
#[Patch(securityPostDenormalize: "is_granted('ROLE_USER') and object.getUser() == user")]
#[Delete(securityPostDenormalize: "is_granted('ROLE_USER') and object.getUser() == user")]
#[ApiResource(
    uriTemplate: '/users/{userId}/ratings',
    operations: [ new GetCollection() ],
    uriVariables: ['userId' => new Link(toProperty: 'user', fromClass: User::class)],
    normalizationContext: ['groups' => ['rating:read']],
    order: ['updatedAt' => 'DESC']
)]
#[ApiResource(
    uriTemplate: '/movies/{movieId}/ratings',
    operations: [ new GetCollection() ],
    uriVariables: ['movieId' => new Link(toProperty: 'movie', fromClass: Movie::class)],
    normalizationContext: ['groups' => ['rating:read']],
    order: ['updatedAt' => 'DESC']
)]
#[ORM\Entity(repositoryClass: RatingRepository::class)]
class Rating
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(nullable: false)]
    private ?int $note = null;

    #[ORM\ManyToOne(inversedBy: 'ratings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'ratings')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Movie $movie = null;

    #[Groups(['rating:read', 'feed:read'])]
    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups(['rating:read', 'feed:read'])]
    public function getNote(): ?int
    {
        return $this->note;
    }

    #[Groups(['rating:write'])]
    public function setNote(?int $note): static
    {
        if($note > 10){
            $note = 10;
        } elseif ($note < 1){
            $note = 1;
        }

        $this->note = $note;

        return $this;
    }

    #[Groups(['rating:read', 'feed:read'])]
    public function getUser(): ?User
    {
        return $this->user;
    }

    #[Groups(['rating:write'])]
    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    #[Groups(['rating:read', 'feed:read'])]
    public function getMovie(): ?Movie
    {
        return $this->movie;
    }

    #[Groups(['rating:write'])]
    public function setMovie(?Movie $movie): static
    {
        $this->movie = $movie;

        return $this;
    }
}
