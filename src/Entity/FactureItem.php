<?php

namespace App\Entity;

use App\Repository\FactureItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FactureItemRepository::class)]
#[ORM\Table(name: 'facture_item')]
class FactureItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'factureItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Facture $facture = null;

    #[ORM\ManyToOne(inversedBy: 'factureItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Article $article = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $quantity = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 3)]
    #[Assert\NotBlank]
    #[Assert\PositiveOrZero]
    private ?string $unit_price = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    private ?string $total_line_ht = '0.000';

    public function getId(): ?int
    {
        return $this->id;
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

    public function getUnitPrice(): ?string
    {
        return $this->unit_price;
    }

    public function setUnitPrice(string $unit_price): static
    {
        $this->unit_price = $unit_price;
        return $this;
    }

    public function getTotalLineHt(): ?string
    {
        return $this->total_line_ht;
    }

    public function setTotalLineHt(string $total_line_ht): static
    {
        $this->total_line_ht = $total_line_ht;
        return $this;
    }

    /**
     * Calculate the line total (quantity * unit price)
     */
    public function calculateLineTotal(): void
    {
        $this->total_line_ht = (string) ((float) $this->quantity * (float) $this->unit_price);
    }
}
