<?php

namespace App\Entity;

use App\Repository\BonCommandeItemRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BonCommandeItemRepository::class)]
#[ORM\Table(name: 'bon_commande_item')]
class BonCommandeItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bonCommandeItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?BonCommande $bon_commande = null;

    #[ORM\ManyToOne(inversedBy: 'bonCommandeItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Article $article = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $quantity = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 3)]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private ?string $unit_price_snapshot = null;

    /**
     * @var Collection<int, BonLivraisonItem>
     */
    #[ORM\OneToMany(targetEntity: BonLivraisonItem::class, mappedBy: 'bon_commande_item')]
    private Collection $bonLivraisonItems;

    public function __construct()
    {
        $this->bonLivraisonItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getArticle(): ?Article
    {
        return $this->article;
    }

    public function setArticle(?Article $article): static
    {
        $this->article = $article;
        return $this;
    }

    public function getQuantity(): ?string
    {
        return $this->quantity;
    }

    public function setQuantity(string $quantity): static
    {
        $this->quantity = $quantity;
        return $this;
    }

    public function getUnitPriceSnapshot(): ?string
    {
        return $this->unit_price_snapshot;
    }

    public function setUnitPriceSnapshot(string $unit_price_snapshot): static
    {
        $this->unit_price_snapshot = $unit_price_snapshot;
        return $this;
    }

    /**
     * Calculate the total for this line
     */
    public function getLineTotal(): string
    {
        return (string) ((float) $this->quantity * (float) $this->unit_price_snapshot);
    }

    /**
     * Get the sum of delivered quantities
     */
    public function getDeliveredQuantity(): string
    {
        $total = 0;
        foreach ($this->bonLivraisonItems as $item) {
            // Cancelled delivery notes must not consume ordered quantity.
            if ($item->getBonLivraison()?->getStatus() === 'CANCELLED') {
                continue;
            }
            $total += (float) $item->getQuantityDelivered();
        }
        return (string) $total;
    }

    /**
     * Calculate remaining quantity to be delivered
     */
    public function getRemainingQuantity(): string
    {
        $ordered = (float) $this->quantity;
        $delivered = (float) $this->getDeliveredQuantity();
        return (string) ($ordered - $delivered);
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
            $bonLivraisonItem->setBonCommandeItem($this);
        }
        return $this;
    }

    public function removeBonLivraisonItem(BonLivraisonItem $bonLivraisonItem): static
    {
        if ($this->bonLivraisonItems->removeElement($bonLivraisonItem)) {
            if ($bonLivraisonItem->getBonCommandeItem() === $this) {
                $bonLivraisonItem->setBonCommandeItem(null);
            }
        }
        return $this;
    }
}
