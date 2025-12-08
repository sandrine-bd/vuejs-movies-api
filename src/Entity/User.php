<?php

namespace App\Entity;

use ApiPlatform\Doctrine\Orm\Filter\PartialSearchFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use ApiPlatform\Metadata\QueryParameter;
use App\Controller\UserAddFollowController;
use App\Controller\UserRemoveFollowController;
use App\Repository\UserRepository;
use App\State\UserPasswordHasher;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['user:read']],
    denormalizationContext: ['groups' => ['user:write']],
    order: ['username' => 'ASC'],

)]
#[Get()]
#[GetCollection(
    parameters: [
        'username' => new QueryParameter(filter: new PartialSearchFilter()),
    ],
)]
#[Post(processor: UserPasswordHasher::class)]
#[Patch(securityPostDenormalize: "is_granted('ROLE_USER') and object == user", processor: UserPasswordHasher::class)]
#[Delete(securityPostDenormalize: "is_granted('ROLE_USER') and object == user")]
#[ApiResource(
    uriTemplate: '/users/{userId}/followers',
    operations: [ new GetCollection() ],
    uriVariables: ['userId' => new Link(toProperty: 'follows', fromClass: self::class)],
    normalizationContext: ['groups' => ['user:read']],
    order: ['updatedAt' => 'DESC']
)]
#[ApiResource(
    uriTemplate: '/users/{userId}/follows',
    operations: [new GetCollection()],
    uriVariables: ['userId' => new Link(toProperty: 'followers', fromClass: self::class)],
    normalizationContext: ['groups' => ['user:read']],
    order: ['updatedAt' => 'DESC']
)]
#[Post(
    controller: UserAddFollowController::class,
    security: "is_granted('ROLE_USER') and object == user"
)]
#[Delete(
    controller: UserRemoveFollowController::class,
    security: "is_granted('ROLE_USER') and object == user"
)]
#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true, nullable: false)]
    private ?string $email = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[Groups(['user:write'])]
    private ?string $plainPassword = null;

    /**
     * @var Collection<int, Rating>
     */
    #[ORM\OneToMany(targetEntity: Rating::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $ratings;

    /**
     * @var Collection<int, Review>
     */
    #[ORM\OneToMany(targetEntity: Review::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $reviews;

    /**
     * @var Collection<int, CustomList>
     */
    #[ORM\OneToMany(targetEntity: CustomList::class, mappedBy: 'user', orphanRemoval: true)]
    private Collection $customLists;

    /**
     * @var Collection<int, self>
     */
    #[ORM\ManyToMany(targetEntity: self::class, inversedBy: 'followers')]
    private Collection $follows;

    /**
     * @var Collection<int, self>
     */
    #[ORM\ManyToMany(targetEntity: self::class, mappedBy: 'follows')]
    private Collection $followers;

    #[ORM\Column(length: 255, unique: true, nullable: false)]
    private ?string $username = null;

    public function __construct()
    {
        $this->ratings = new ArrayCollection();
        $this->reviews = new ArrayCollection();
        $this->customLists = new ArrayCollection();
        $this->follows = new ArrayCollection();
        $this->followers = new ArrayCollection();
    }

    #[Groups(['user:read', 'rating:read', 'review:read', 'customList:read', 'feed:read', 'customListEntry:read'])]
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    #[Groups(['user:write'])]
    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
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
            $rating->setUser($this);
        }

        return $this;
    }

    public function removeRating(Rating $rating): static
    {
        if ($this->ratings->removeElement($rating)) {
            // set the owning side to null (unless already changed)
            if ($rating->getUser() === $this) {
                $rating->setUser(null);
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
            $review->setUser($this);
        }

        return $this;
    }

    public function removeReview(Review $review): static
    {
        if ($this->reviews->removeElement($review)) {
            // set the owning side to null (unless already changed)
            if ($review->getUser() === $this) {
                $review->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, CustomList>
     */
    public function getCustomLists(): Collection
    {
        return $this->customLists;
    }

    public function addCustomList(CustomList $customList): static
    {
        if (!$this->customLists->contains($customList)) {
            $this->customLists->add($customList);
            $customList->setUser($this);
        }

        return $this;
    }

    public function removeCustomList(CustomList $customList): static
    {
        if ($this->customLists->removeElement($customList)) {
            // set the owning side to null (unless already changed)
            if ($customList->getUser() === $this) {
                $customList->setUser(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getFollows(): Collection
    {
        return $this->follows;
    }

    public function addFollow(self $follow): static
    {
        if (!$this->follows->contains($follow)) {
            $this->follows->add($follow);
        }

        return $this;
    }

    public function removeFollow(self $follow): static
    {
        $this->follows->removeElement($follow);

        return $this;
    }

    /**
     * @return Collection<int, self>
     */
    public function getFollowers(): Collection
    {
        return $this->followers;
    }

    public function addFollower(self $follower): static
    {
        if (!$this->followers->contains($follower)) {
            $this->followers->add($follower);
            $follower->addFollow($this);
        }

        return $this;
    }

    public function removeFollower(self $follower): static
    {
        if ($this->followers->removeElement($follower)) {
            $follower->removeFollow($this);
        }

        return $this;
    }

    #[Groups(['user:read', 'rating:read', 'review:read', 'customList:read', 'feed:read', 'customListEntry:read'])]
    public function getUsername(): ?string
    {
        return $this->username;
    }

    #[Groups(['user:write'])]
    public function setUsername(string $username): static
    {
        $this->username = $username;

        return $this;
    }

    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;

        return $this;
    }
}
