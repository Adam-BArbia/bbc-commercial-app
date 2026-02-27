<?php

namespace App\Entity;

use App\Repository\RoleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RoleRepository::class)]
#[ORM\Table(name: 'role')]
class Role
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $name = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\OneToMany(targetEntity: User::class, mappedBy: 'role')]
    private Collection $users;

    /**
     * @var Collection<int, Privilege>
     */
    #[ORM\ManyToMany(targetEntity: Privilege::class, inversedBy: 'roles')]
    #[ORM\JoinTable(name: 'role_privilege')]
    private Collection $privileges;

    public function __construct()
    {
        $this->users = new ArrayCollection();
        $this->privileges = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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
            $user->setRole($this);
        }

        return $this;
    }

    public function removeUser(User $user): static
    {
        if ($this->users->removeElement($user)) {
            // set the owning side to null (unless already changed)
            if ($user->getRole() === $this) {
                $user->setRole(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Privilege>
     */
    public function getPrivileges(): Collection
    {
        return $this->privileges;
    }

    public function addPrivilege(Privilege $privilege): static
    {
        if (!$this->privileges->contains($privilege)) {
            $this->privileges->add($privilege);
        }

        return $this;
    }

    public function removePrivilege(Privilege $privilege): static
    {
        $this->privileges->removeElement($privilege);

        return $this;
    }

    /**
     * Check if this role has a specific privilege by code
     */
    public function hasPrivilege(string $code): bool
    {
        foreach ($this->privileges as $privilege) {
            if ($privilege->getCode() === $code) {
                return true;
            }
        }
        return false;
    }
}
