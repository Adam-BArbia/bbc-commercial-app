<?php

namespace App\Entity;

use App\Repository\ArticleRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ArticleRepository::class)]
#[ORM\Table(name: 'article')]
class Article
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 50, unique: true)]
    #[Assert\NotBlank(message: 'Le code article est obligatoire.')]
    private ?string $code = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'La désignation est obligatoire.')]
    private ?string $designation = null;

    #[ORM\Column]
    private bool $active = true;

    /**
     * @var Collection<int, BonCommandeItem>
     */
    #[ORM\OneToMany(targetEntity: BonCommandeItem::class, mappedBy: 'article')]
    private Collection $bonCommandeItems;

    /**
     * @var Collection<int, FactureItem>
     */
    #[ORM\OneToMany(targetEntity: FactureItem::class, mappedBy: 'article')]
    private Collection $factureItems;

    public function __construct()
    {
        $this->bonCommandeItems = new ArrayCollection();
        $this->factureItems = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCode(): ?string
    {
        return $this->code;
    }

    public function setCode(string $code): static
    {
        $this->code = $code;
        return $this;
    }

    public function getDesignation(): ?string
    {
        return $this->designation;
    }

    public function setDesignation(string $designation): static
    {
        $this->designation = $designation;
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
            $bonCommandeItem->setArticle($this);
        }
        return $this;
    }

    public function removeBonCommandeItem(BonCommandeItem $bonCommandeItem): static
    {
        if ($this->bonCommandeItems->removeElement($bonCommandeItem)) {
            if ($bonCommandeItem->getArticle() === $this) {
                $bonCommandeItem->setArticle(null);
            }
        }
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
            $factureItem->setArticle($this);
        }
        return $this;
    }

    public function removeFactureItem(FactureItem $factureItem): static
    {
        if ($this->factureItems->removeElement($factureItem)) {
            if ($factureItem->getArticle() === $this) {
                $factureItem->setArticle(null);
            }
        }
        return $this;
    }
}
