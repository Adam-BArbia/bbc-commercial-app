<?php

namespace App\Entity;

use App\Repository\BonLivraisonRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BonLivraisonRepository::class)]
#[ORM\Table(name: 'bon_livraison')]
class BonLivraison
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['DRAFT', 'VALIDATED', 'CANCELLED'])]
    private ?string $status = 'DRAFT';

    #[ORM\ManyToOne(inversedBy: 'bonLivraisons')]
    #[ORM\JoinColumn(nullable: false)]
    private ?BonCommande $bon_commande = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $created_by = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $cancelled_at = null;

    #[ORM\ManyToOne(inversedBy: 'bonLivraisons')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Facture $facture = null;

    /**
     * @var Collection<int, BonLivraisonItem>
     */
    #[ORM\OneToMany(targetEntity: BonLivraisonItem::class, mappedBy: 'bon_livraison', cascade: ['persist', 'remove'])]
    private Collection $bonLivraisonItems;

    public function __construct()
    {
        $this->bonLivraisonItems = new ArrayCollection();
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

    public function getBonCommande(): ?BonCommande
    {
        return $this->bon_commande;
    }

    public function setBonCommande(?BonCommande $bon_commande): static
    {
        $this->bon_commande = $bon_commande;
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

    public function getFacture(): ?Facture
    {
        return $this->facture;
    }

    public function setFacture(?Facture $facture): static
    {
        $this->facture = $facture;
        return $this;
    }

    /**
     * @return Collection<int, BonLivraisonItem>
     */
    public function getBonLivraisonItems(): Collection
    {
        return $this->bonLivraisonItems;
    }

    public function addBonLivraisonItem(BonLivraisonItem $bonLivraisonItem): static
    {
        if (!$this->bonLivraisonItems->contains($bonLivraisonItem)) {
            $this->bonLivraisonItems->add($bonLivraisonItem);
            $bonLivraisonItem->setBonLivraison($this);
        }
        return $this;
    }

    public function removeBonLivraisonItem(BonLivraisonItem $bonLivraisonItem): static
    {
        if ($this->bonLivraisonItems->removeElement($bonLivraisonItem)) {
            if ($bonLivraisonItem->getBonLivraison() === $this) {
                $bonLivraisonItem->setBonLivraison(null);
            }
        }
        return $this;
    }

    /**
     * Check if this delivery note is invoiced
     */
    public function isInvoiced(): bool
    {
        return $this->facture !== null;
    }

    /**
     * Check if this delivery note can be edited
     */
    public function canBeEdited(): bool
    {
        return !$this->isInvoiced() && $this->status !== 'CANCELLED';
    }
}
