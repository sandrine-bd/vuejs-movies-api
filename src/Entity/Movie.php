<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\QueryParameter;
use App\Repository\MovieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['movie:read']],
    order: ['year' => 'DESC'],
)]
#[Get()]
#[GetCollection(
    parameters: [
        'title' => new QueryParameter(filter: new PartialSearchFilter()),
    ],
)]
#[ApiResource(
    uriTemplate: '/genres/{genreId}/movies',
    operations: [ new GetCollection(
        parameters: [
            'title' => new QueryParameter(filter: new PartialSearchFilter()),
        ]
    ) ],
    uriVariables: [
        'genreId' => new Link(toProperty: 'genres', fromClass: Genre::class),
    ],
    normalizationContext: ['groups' => ['movie:read']],
    order: ['year' => 'DESC']
)]
#[ApiResource(
    uriTemplate: '/casts/{peopleId}/movies',
    operations: [ new GetCollection(
        parameters: [
            'title' => new QueryParameter(filter: new PartialSearchFilter()),
        ],
    ) ],
    uriVariables: [
        'peopleId' => new Link(toProperty: 'castMembers', fromClass: People::class),
    ],
    normalizationContext: ['groups' => ['movie:read']],
    order: ['year' => 'DESC']
)]
#[ApiResource(
    uriTemplate: '/directors/{peopleId}/movies',
    operations: [ new GetCollection(
        parameters: [
            'title' => new QueryParameter(filter: new PartialSearchFilter()),
        ],
    ) ],
    uriVariables: [
        'peopleId' => new Link(toProperty: 'directors', fromClass: People::class),
    ],
    normalizationContext: ['groups' => ['movie:read']],
    order: ['year' => 'DESC']
)]
#[ORM\Entity(repositoryClass: MovieRepository::class)]
class Movie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $plot = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $fullPlot = null;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(nullable: true)]
    private ?int $year = null;

    #[ORM\Column(nullable: true)]
    private ?array $imdb = null;

    #[ORM\Column(nullable: true)]
    private ?array $tomatoes = null;

    #[ORM\Column(length: 300, nullable: true)]
    private ?string $poster = null;

    /**
     * @var Collection<int, Genre>
     */
    #[ORM\ManyToMany(targetEntity: Genre::class, inversedBy: 'movies')]
    private Collection $genres;

    /**
     * @var Collection<int, People>
     */
    #[ORM\ManyToMany(targetEntity: People::class, inversedBy: 'movies')]
    private Collection $castMembers;

    /**
     * @var Collection<int, People>
     */
    #[ORM\ManyToMany(targetEntity: People::class, inversedBy: 'directed')]
    #[ORM\JoinTable(name: 'directors_movies')]
    private Collection $directors;

    #[ORM\Column(nullable: true)]
    private ?array $countries = null;

    /**
     * @var Collection<int, Rating>
     */
    #[ORM\OneToMany(targetEntity: Rating::class, mappedBy: 'movie', orphanRemoval: true)]
    private Collection $ratings;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'movie', orphanRemoval: true)]
    private Collection $reviews;

    /**
     * @var Collection<int, CustomListEntry>
     */
    #[ORM\OneToMany(targetEntity: CustomListEntry::class, mappedBy: 'movie', orphanRemoval: true)]
    private Collection $customListEntries;

    public function __construct()
    {
        $this->genres = new ArrayCollection();
        $this->castMembers = new ArrayCollection();
        $this->directors = new ArrayCollection();
        $this->ratings = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->customListEntries = new ArrayCollection();
    }

    #[Groups(['movie:read', 'rating:read', 'review:read', 'customList:read', 'feed:read', 'customListEntry:read'])]
    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups(['movie:read'])]
    public function getPlot(): ?string
    {
        return $this->plot;
    }

    public function setPlot(?string $plot): static
    {
        $this->plot = $plot;

        return $this;
    }

    #[Groups(['movie:read'])]

    public function getFullPlot(): ?string
    {
        return $this->fullPlot;
    }

    public function setFullPlot(?string $fullPlot): static
    {
        $this->fullPlot = $fullPlot;

        return $this;
    }

    #[Groups(['movie:read', 'rating:read', 'review:read', 'customList:read', 'feed:read', 'customListEntry:read'])]

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    #[Groups(['movie:read', 'rating:read', 'review:read', 'customList:read', 'feed:read', 'customListEntry:read'])]

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(?int $year): static
    {
        $this->year = $year;

        return $this;
    }

    #[Groups(['movie:read'])]

    public function getImdb(): ?array
    {
        return $this->imdb;
    }

    public function setImdb(?array $imdb): static
    {
        $this->imdb = $imdb;

        return $this;
    }

    #[Groups(['movie:read'])]

    public function getTomatoes(): ?array
    {
        return $this->tomatoes;
    }

    public function setTomatoes(?array $tomatoes): static
    {
        $this->tomatoes = $tomatoes;

        return $this;
    }

    #[Groups(['movie:read', 'rating:read', 'review:read', 'customList:read', 'feed:read', 'customListEntry:read'])]

    public function getPoster(): ?string
    {
        return $this->poster;
    }

    public function setPoster(?string $poster): static
    {
        $this->poster = $poster;

        return $this;
    }

    /**
     * @return Collection<int, Genre>
     */
    #[Groups(['movie:read'])]
    public function getGenres(): Collection
    {
        return $this->genres;
    }

    public function addGenre(Genre $genre): static
    {
        if (!$this->genres->contains($genre)) {
            $this->genres->add($genre);
        }

        return $this;
    }

    public function removeGenre(Genre $genre): static
    {
        $this->genres->removeElement($genre);

        return $this;
    }

    /**
     * @return Collection<int, People>
     */
    #[Groups(['movie:read'])]
    public function getCastMembers(): Collection
    {
        return $this->castMembers;
    }

    public function addCastMember(People $castMember): static
    {
        if (!$this->castMembers->contains($castMember)) {
            $this->castMembers->add($castMember);
        }

        return $this;
    }

    public function removeCastMember(People $castMember): static
    {
        $this->castMembers->removeElement($castMember);

        return $this;
    }

    /**
     * @return Collection<int, People>
     */
    #[Groups(['movie:read'])]
    public function getDirectors(): Collection
    {
        return $this->directors;
    }

    public function addDirector(People $director): static
    {
        if (!$this->directors->contains($director)) {
            $this->directors->add($director);
        }

        return $this;
    }

    public function removeDirector(People $director): static
    {
        $this->directors->removeElement($director);

        return $this;
    }

    #[Groups(['movie:read'])]
    public function getCountries(): ?array
    {
        return $this->countries;
    }

    public function setCountries(?array $countries): static
    {
        $this->countries = $countries;

        return $this;
    }

    /**
     * @return Collection<int, Rating>
     */
    public function getRatings(): Collection
    {
        return $this->ratings;
    }

    public function addRating(Rating $rating): static
    {
        if (!$this->ratings->contains($rating)) {
            $this->ratings->add($rating);
            $rating->setMovie($this);
        }

        return $this;
    }

    public function removeRating(Rating $rating): static
    {
        if ($this->ratings->removeElement($rating)) {
            // set the owning side to null (unless already changed)
            if ($rating->getMovie() === $this) {
                $rating->setMovie(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Review>
     */
    public function getReviews(): Collection
    {
        return $this->reviews;
    }

    public function addReview(Review $review): static
    {
        if (!$this->reviews->contains($review)) {
            $this->reviews->add($review);
            $review->setMovie($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getMovie() === $this) {
                $review->setMovie(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CustomListEntry>
     */
    public function getCustomListEntries(): Collection
    {
        return $this->customListEntries;
    }

    public function addCustomListEntry(CustomListEntry $customListEntry): static
    {
        if (!$this->customListEntries->contains($customListEntry)) {
            $this->customListEntries->add($customListEntry);
            $customListEntry->setMovie($this);
        }

        return $this;
    }

    public function removeCustomListEntry(CustomListEntry $customListEntry): static
    {
        if ($this->customListEntries->removeElement($customListEntry)) {
            // set the owning side to null (unless already changed)
            if ($customListEntry->getMovie() === $this) {
                $customListEntry->setMovie(null);
            }
        }

        return $this;
    }

    #[Groups(['movie:read'])]
    public function getAverageRating(): ?float
    {
        if ($this->ratings->isEmpty()) {
            return null;
        }

        $sum = array_sum(
            array_map(fn($r) => $r->getNote(), $this->ratings->toArray())
        );

        return round($sum / count($this->ratings), 2);
    }
}
