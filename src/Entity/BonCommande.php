<?php

namespace App\Entity;

use App\Repository\BonCommandeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BonCommandeRepository::class)]
#[ORM\Table(name: 'bon_commande')]
class BonCommande
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['DRAFT', 'CONFIRMED', 'PARTIALLY_DELIVERED', 'DELIVERED', 'CANCELLED'])]
    private ?string $status = 'DRAFT';

    #[ORM\ManyToOne(inversedBy: 'bonCommandes')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\Column(type: Types::JSON)]
    private array $client_snapshot = [];

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $created_by = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $cancelled_at = null;

    /**
     * @var Collection<int, BonCommandeItem>
     */
    #[ORM\OneToMany(targetEntity: BonCommandeItem::class, mappedBy: 'bon_commande', cascade: ['persist', 'remove'])]
    private Collection $bonCommandeItems;

    /**
     * @var Collection<int, BonLivraison>
     */
    #[ORM\OneToMany(targetEntity: BonLivraison::class, mappedBy: 'bon_commande')]
    private Collection $bonLivraisons;

    public function __construct()
    {
        $this->bonCommandeItems = new ArrayCollection();
        $this->bonLivraisons = new ArrayCollection();
        $this->created_at = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;
        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;
        return $this;
    }

    public function getClientSnapshot(): array
    {
        return $this->client_snapshot;
    }

    public function setClientSnapshot(array $client_snapshot): static
    {
        $this->client_snapshot = $client_snapshot;
        return $this;
    }

    public function getCreatedBy(): ?User
    {
        return $this->created_by;
    }

    public function setCreatedBy(?User $created_by): static
    {
        $this->created_by = $created_by;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;
        return $this;
    }

    public function getCancelledAt(): ?\DateTimeInterface
    {
        return $this->cancelled_at;
    }

    public function setCancelledAt(?\DateTimeInterface $cancelled_at): static
    {
        $this->cancelled_at = $cancelled_at;
        return $this;
    }

    /**
     * @return Collection<int, BonCommandeItem>
     */
    public function getBonCommandeItems(): Collection
    {
        return $this->bonCommandeItems;
    }

    public function addBonCommandeItem(BonCommandeItem $bonCommandeItem): static
    {
        if (!$this->bonCommandeItems->contains($bonCommandeItem)) {
            $this->bonCommandeItems->add($bonCommandeItem);
            $bonCommandeItem->setBonCommande($this);
        }
        return $this;
    }

    public function removeBonCommandeItem(BonCommandeItem $bonCommandeItem): static
    {
        if ($this->bonCommandeItems->removeElement($bonCommandeItem)) {
            if ($bonCommandeItem->getBonCommande() === $this) {
                $bonCommandeItem->setBonCommande(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, BonLivraison>
     */
    public function getBonLivraisons(): Collection
    {
        return $this->bonLivraisons;
    }

    public function addBonLivraison(BonLivraison $bonLivraison): static
    {
        if (!$this->bonLivraisons->contains($bonLivraison)) {
            $this->bonLivraisons->add($bonLivraison);
            $bonLivraison->setBonCommande($this);
        }
        return $this;
    }

    public function removeBonLivraison(BonLivraison $bonLivraison): static
    {
        if ($this->bonLivraisons->removeElement($bonLivraison)) {
            if ($bonLivraison->getBonCommande() === $this) {
                $bonLivraison->setBonCommande(null);
            }
        }
        return $this;
    }

    /**
     * Check if this order has any delivery notes
     */
    public function hasDeliveryNotes(): bool
    {
        return $this->bonLivraisons->count() > 0;
    }

    /**
     * Check if order can be edited
     */
    public function canBeEdited(): bool
    {
        return !$this->hasDeliveryNotes() && $this->status !== 'CANCELLED';
    }
}
