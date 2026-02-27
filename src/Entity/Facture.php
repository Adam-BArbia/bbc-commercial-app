<?php

namespace App\Entity;

use App\Repository\FactureRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: FactureRepository::class)]
#[ORM\Table(name: 'facture')]
class Facture
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    private ?string $reference = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['DRAFT', 'ISSUED', 'UNPAID', 'PARTIALLY_PAID', 'PAID', 'CANCELLED'])]
    private ?string $status = 'DRAFT';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    private ?string $total_ht = '0.000';

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2)]
    #[Assert\PositiveOrZero]
    private ?string $tva_rate = '0.00';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    private ?string $tva_amount = '0.000';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    private ?string $timbre = '1.000';

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    private ?string $total_ttc = '0.000';

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $created_by = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $created_at = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $cancelled_at = null;

    /**
     * @var Collection<int, FactureItem>
     */
    #[ORM\OneToMany(targetEntity: FactureItem::class, mappedBy: 'facture', cascade: ['persist', 'remove'])]
    private Collection $factureItems;

    /**
     * @var Collection<int, BonLivraison>
     */
    #[ORM\OneToMany(targetEntity: BonLivraison::class, mappedBy: 'facture')]
    private Collection $bonLivraisons;

    /**
     * @var Collection<int, PaymentFacture>
     */
    #[ORM\OneToMany(targetEntity: PaymentFacture::class, mappedBy: 'facture', cascade: ['remove'])]
    private Collection $paymentFactures;

    public function __construct()
    {
        $this->factureItems = new ArrayCollection();
        $this->bonLivraisons = new ArrayCollection();
        $this->paymentFactures = new ArrayCollection();
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

    public function getTotalHt(): ?string
    {
        return $this->total_ht;
    }

    public function setTotalHt(string $total_ht): static
    {
        $this->total_ht = $total_ht;
        return $this;
    }

    public function getTvaRate(): ?string
    {
        return $this->tva_rate;
    }

    public function setTvaRate(string $tva_rate): static
    {
        $this->tva_rate = $tva_rate;
        return $this;
    }

    public function getTvaAmount(): ?string
    {
        return $this->tva_amount;
    }

    public function setTvaAmount(string $tva_amount): static
    {
        $this->tva_amount = $tva_amount;
        return $this;
    }

    public function getTimbre(): ?string
    {
        return $this->timbre;
    }

    public function setTimbre(string $timbre): static
    {
        $this->timbre = $timbre;
        return $this;
    }

    public function getTotalTtc(): ?string
    {
        return $this->total_ttc;
    }

    public function setTotalTtc(string $total_ttc): static
    {
        $this->total_ttc = $total_ttc;
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
     * @return Collection<int, FactureItem>
     */
    public function getFactureItems(): Collection
    {
        return $this->factureItems;
    }

    public function addFactureItem(FactureItem $factureItem): static
    {
        if (!$this->factureItems->contains($factureItem)) {
            $this->factureItems->add($factureItem);
            $factureItem->setFacture($this);
        }
        return $this;
    }

    public function removeFactureItem(FactureItem $factureItem): static
    {
        if ($this->factureItems->removeElement($factureItem)) {
            if ($factureItem->getFacture() === $this) {
                $factureItem->setFacture(null);
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
            $bonLivraison->setFacture($this);
        }
        return $this;
    }

    public function removeBonLivraison(BonLivraison $bonLivraison): static
    {
        if ($this->bonLivraisons->removeElement($bonLivraison)) {
            if ($bonLivraison->getFacture() === $this) {
                $bonLivraison->setFacture(null);
            }
        }
        return $this;
    }

    /**
     * @return Collection<int, PaymentFacture>
     */
    public function getPaymentFactures(): Collection
    {
        return $this->paymentFactures;
    }

    public function addPaymentFacture(PaymentFacture $paymentFacture): static
    {
        if (!$this->paymentFactures->contains($paymentFacture)) {
            $this->paymentFactures->add($paymentFacture);
            $paymentFacture->setFacture($this);
        }
        return $this;
    }

    public function removePaymentFacture(PaymentFacture $paymentFacture): static
    {
        if ($this->paymentFactures->removeElement($paymentFacture)) {
            if ($paymentFacture->getFacture() === $this) {
                $paymentFacture->setFacture(null);
            }
        }
        return $this;
    }

    /**
     * Get total amount already paid
     */
    public function getTotalPaid(): string
    {
        $total = '0.000';
        foreach ($this->paymentFactures as $pf) {
            $total = (string) ((float) $total + (float) $pf->getAmountAllocated());
        }
        return $total;
    }

    /**
     * Get remaining amount to pay
     */
    public function getRemainingAmount(): string
    {
        $paid = $this->getTotalPaid();
        return (string) (max(0, (float) $this->total_ttc - (float) $paid));
    }
}
