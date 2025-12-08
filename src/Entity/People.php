<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\QueryParameter;
use App\Repository\PeopleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['people:read']],
    order: ['fullName' => 'ASC'],
)]
#[Get()]
#[GetCollection(
    parameters: [
        'fullName' => new QueryParameter(filter: new PartialSearchFilter()),
    ],
)]
#[ORM\Entity(repositoryClass: PeopleRepository::class)]
class People
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $fullName = null;

    /**
     * @var Collection<int, Movie>
     */
    #[ORM\ManyToMany(targetEntity: Movie::class, mappedBy: 'castMembers')]
    private Collection $movies;

    /**
     * @var Collection<int, Movie>
     */
    #[ORM\ManyToMany(targetEntity: Movie::class, mappedBy: 'directors')]
    private Collection $directed;

    public function __construct()
    {
        $this->movies = new ArrayCollection();
        $this->directed = new ArrayCollection();
    }

    #[Groups(['people:read', 'movie:read'])]
    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups(['people:read', 'movie:read'])]
    public function getFullName(): ?string
    {
        return $this->fullName;
    }

    public function setFullName(?string $fullName): static
    {
        $this->fullName = $fullName;

        return $this;
    }

    /**
     * @return Collection<int, Movie>
     */
    public function getMovies(): Collection
    {
        return $this->movies;
    }

    public function addMovie(Movie $movie): static
    {
        if (!$this->movies->contains($movie)) {
            $this->movies->add($movie);
            $movie->addCastMember($this);
        }

        return $this;
    }

    public function removeMovie(Movie $movie): static
    {
        if ($this->movies->removeElement($movie)) {
            $movie->removeCastMember($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Movie>
     */
    public function getDirected(): Collection
    {
        return $this->directed;
    }

    public function addDirected(Movie $directed): static
    {
        if (!$this->directed->contains($directed)) {
            $this->directed->add($directed);
            $directed->addDirector($this);
        }

        return $this;
    }

    public function removeDirected(Movie $directed): static
    {
        if ($this->directed->removeElement($directed)) {
            $directed->removeDirector($this);
        }

        return $this;
    }
}
