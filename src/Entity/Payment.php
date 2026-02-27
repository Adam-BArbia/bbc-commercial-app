<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\Table(name: 'payment')]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    #[Assert\NotBlank]
    private ?\DateTimeInterface $payment_date = null;

    #[ORM\Column(length: 50)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['CASH', 'CHEQUE', 'VIREMENT', 'CARTE'])]
    private ?string $method = null;

    #[ORM\Column(length: 100)]
    private ?string $reference = null;

    #[ORM\Column(type: 'decimal', precision: 12, scale: 3)]
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?string $amount = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $created_by = null;

    /**
     * @var Collection<int, PaymentFacture>
     */
    #[ORM\OneToMany(targetEntity: PaymentFacture::class, mappedBy: 'payment', cascade: ['remove'])]
    private Collection $paymentFactures;

    public function __construct()
    {
        $this->paymentFactures = new ArrayCollection();
        $this->payment_date = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPaymentDate(): ?\DateTimeInterface
    {
        return $this->payment_date;
    }

    public function setPaymentDate(\DateTimeInterface $payment_date): static
    {
        $this->payment_date = $payment_date;
        return $this;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(string $method): static
    {
        $this->method = $method;
        return $this;
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

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
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
            $paymentFacture->setPayment($this);
        }
        return $this;
    }

    public function removePaymentFacture(PaymentFacture $paymentFacture): static
    {
        if ($this->paymentFactures->removeElement($paymentFacture)) {
            if ($paymentFacture->getPayment() === $this) {
                $paymentFacture->setPayment(null);
            }
        }
        return $this;
    }

    /**
     * Get total amount allocated to invoices
     */
    public function getTotalAllocated(): string
    {
        $total = '0.000';
        foreach ($this->paymentFactures as $pf) {
            $total = (string) ((float) $total + (float) $pf->getAmountAllocated());
        }
        return $total;
    }
}
