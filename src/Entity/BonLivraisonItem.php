<?php

namespace App\Entity;

use App\Repository\BonLivraisonItemRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BonLivraisonItemRepository::class)]
#[ORM\Table(name: 'bon_livraison_item')]
class BonLivraisonItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'bonLivraisonItems')]
    #[ORM\JoinColumn(nullable: false)]
    private ?BonLivraison $bon_livraison = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?BonCommandeItem $bon_commande_item = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $quantity_delivered = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBonLivraison(): ?BonLivraison
    {
        return $this->bon_livraison;
    }

    public function setBonLivraison(?BonLivraison $bon_livraison): static
    {
        $this->bon_livraison = $bon_livraison;
        return $this;
    }

    public function getBonCommandeItem(): ?BonCommandeItem
    {
        return $this->bon_commande_item;
    }

    public function setBonCommandeItem(?BonCommandeItem $bon_commande_item): static
    {
        $this->bon_commande_item = $bon_commande_item;
        return $this;
    }

    public function getQuantityDelivered(): ?string
    {
        return $this->quantity_delivered;
    }

    public function setQuantityDelivered(string $quantity_delivered): static
    {
        $this->quantity_delivered = $quantity_delivered;
        return $this;
    }
}
