<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Link;
use ApiPlatform\Metadata\Patch;
use ApiPlatform\Metadata\Post;
use App\Repository\CustomListRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    normalizationContext: ['groups' => ['customList:read']],
    denormalizationContext: ['groups' => ['customList:write']],
    order: ['updatedAt' => 'DESC'],
)]
#[Get()]
#[Post(securityPostDenormalize: "is_granted('ROLE_USER') and object.getUser() == user")]
#[Patch(securityPostDenormalize: "is_granted('ROLE_USER') and object.getUser() == user")]
#[Delete(securityPostDenormalize: "is_granted('ROLE_USER') and object.getUser() == user")]
#[ApiResource(
    uriTemplate: '/users/{userId}/collections',
    operations: [ new GetCollection() ],
    uriVariables: ['userId' => new Link(toProperty: 'user', fromClass: User::class)],
    normalizationContext: ['groups' => ['customList:read']],
    order: ['updatedAt' => 'DESC']
)]
#[ORM\Entity(repositoryClass: CustomListRepository::class)]
class CustomList
{
    use TimestampableEntity;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'customLists')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\Column(length: 255, nullable: false)]
    private ?string $title = null;

    /**
     * @var Collection<int, CustomListEntry>
     */
    #[ORM\OneToMany(targetEntity: CustomListEntry::class, mappedBy: 'customList', cascade: ['persist'], orphanRemoval: true)]
    #[Groups(['customList:write'])]
    private Collection $entries;

    public function __construct()
    {
        $this->entries = new ArrayCollection();
    }

    #[Groups(['customList:read', 'feed:read', 'customListEntry:read'])]
    public function getId(): ?int
    {
        return $this->id;
    }

    #[Groups(['customList:read', 'feed:read', 'customListEntry:read'])]
    public function getUser(): ?User
    {
        return $this->user;
    }

    #[Groups(['customList:write'])]
    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    #[Groups(['customList:read', 'feed:read', 'customListEntry:read'])]
    public function getTitle(): ?string
    {
        return $this->title;
    }

    #[Groups(['customList:write'])]
    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Collection<int, CustomListEntry>
     */
    #[Groups(['customList:read'])]
    public function getEntries(): Collection
    {
        return $this->entries;
    }

    public function addEntry(CustomListEntry $entry): static
    {
        if (!$this->entries->contains($entry)) {
            $this->entries->add($entry);
            $entry->setCustomList($this);
        }

        return $this;
    }

    public function removeEntry(CustomListEntry $entry): static
    {
        if ($this->entries->removeElement($entry)) {
            // set the owning side to null (unless already changed)
            if ($entry->getCustomList() === $this) {
                $entry->setCustomList(null);
            }
        }

        return $this;
    }
}
