<?php

namespace App\Entity;

use App\Repository\BonCommandeItemRepository;
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
}
