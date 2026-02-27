<?php

namespace App\Entity;

use App\Repository\ClientRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ClientRepository::class)]
#[ORM\Table(name: 'client')]
#[UniqueEntity(fields: ['matricule_fiscale'], message: 'Ce matricule fiscal est déjà utilisé.')]
class Client
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank(message: 'Le code client est obligatoire.')]
    private ?string $client_code = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Le matricule fiscal est obligatoire.')]
    private ?string $matricule_fiscale = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom du client est obligatoire.')]
    private ?string $name = null;

    #[ORM\Column(type: 'text')]
    private ?string $address = null;

    #[ORM\Column]
    private bool $active = true;

    /**
     * @var Collection<int, BonCommande>
     */
    #[ORM\OneToMany(targetEntity: BonCommande::class, mappedBy: 'client')]
    private Collection $bonCommandes;

    public function __construct()
    {
        $this->bonCommandes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClientCode(): ?string
    {
        return $this->client_code;
    }

    public function setClientCode(string $client_code): static
    {
        $this->client_code = $client_code;
        return $this;
    }

    public function getMatriculeFiscale(): ?string
    {
        return $this->matricule_fiscale;
    }

    public function setMatriculeFiscale(string $matricule_fiscale): static
    {
        $this->matricule_fiscale = $matricule_fiscale;
        return $this;
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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(string $address): static
    {
        $this->address = $address;
        return $this;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): static
    {
        $this->active = $active;
        return $this;
    }

    /**
     * @return Collection<int, BonCommande>
     */
    public function getBonCommandes(): Collection
    {
        return $this->bonCommandes;
    }

    public function addBonCommande(BonCommande $bonCommande): static
    {
        if (!$this->bonCommandes->contains($bonCommande)) {
            $this->bonCommandes->add($bonCommande);
            $bonCommande->setClient($this);
        }
        return $this;
    }

    public function removeBonCommande(BonCommande $bonCommande): static
    {
        if ($this->bonCommandes->removeElement($bonCommande)) {
            if ($bonCommande->getClient() === $this) {
                $bonCommande->setClient(null);
            }
        }
        return $this;
    }
}
