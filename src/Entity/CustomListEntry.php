<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use App\Repository\CustomListEntryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['customListEntry:read']],
    order: ['position' => 'ASC'],
)]
#[Get()]
#[ApiResource(
    uriTemplate: '/movies/{movieId}/custom_list_entries',
    operations: [ new GetCollection() ],
    uriVariables: ['movieId' => new Link(toProperty: 'movie', fromClass: Movie::class)],
    normalizationContext: ['groups' => ['customListEntry:read']],
    order: ['customList.updatedAt' => 'DESC']
)]
#[ORM\Entity(repositoryClass: CustomListEntryRepository::class)]
class CustomListEntry
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column]
    private ?int $position = null;

    #[ORM\ManyToOne(inversedBy: 'customListEntries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Movie $movie = null;

    #[ORM\ManyToOne(inversedBy: 'entries')]
    #[ORM\JoinColumn(nullable: false)]
    private ?CustomList $customList = null;

    #[Groups(['customListEntry:read', 'customList:read'])]
    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups(['customListEntry:read', 'customList:read'])]
    public function getPosition(): ?int
    {
        return $this->position;
    }

    #[Groups(['customList:write'])]
    public function setPosition(int $position): static
    {
        $this->position = $position;

        return $this;
    }

    #[Groups(['customListEntry:read', 'customList:read'])]
    public function getMovie(): ?Movie
    {
        return $this->movie;
    }

    #[Groups(['customList:write'])]
    public function setMovie(?Movie $movie): static
    {
        $this->movie = $movie;

        return $this;
    }

    #[Groups(['customListEntry:read'])]
    public function getCustomList(): ?CustomList
    {
        return $this->customList;
    }

    public function setCustomList(?CustomList $customList): static
    {
        $this->customList = $customList;

        return $this;
    }
}
