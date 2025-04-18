<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Post;
use App\Repository\SocietyRepository;
use App\State\Society\SocietyPostProcessor;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: SocietyRepository::class)]
#[ApiResource(
    normalizationContext: ['groups' => ["read:society:get", "read:society:collection"]],
    denormalizationContext: ['groups' => ['write:society']],
    operations: [
        new Post(
            security: 'is_granted("ROLE_USER")',
            denormalizationContext: ['groups' => ['write:society:post']],
            processor: SocietyPostProcessor::class
        )
    ]
)]
class Society
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[
        ORM\Column,
        Groups(["read:society:get", "read:society:collection"])
    ]
    private ?int $id = null;

    #[
        ORM\Column(length: 255),
        Groups(["read:society:get", "read:society:collection", 'write:society:post', 'write:society'])
    ]
    private ?string $nom_society = null;

    /**
     * @var Collection<int, User>
     */
    #[
        ORM\OneToMany(targetEntity: User::class, mappedBy: 'society'),
        Groups(["read:society:get", "read:society:collection"])
    ]
    private Collection $users;

    public function __construct()
    {
        $this->users = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomSociety(): ?string
    {
        return $this->nom_society;
    }

    public function setNomSociety(string $nom_society): static
    {
        $this->nom_society = strtoupper($nom_society);

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getUsers(): Collection
    {
        return $this->users;
    }

    public function addUser(User $user): static
    {
        if (!$this->users->contains($user)) {
            $this->users->add($user);
            $user->setSociety($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getSociety() === $this) {
                $user->setSociety(null);
            }
        }

        return $this;
    }
}
