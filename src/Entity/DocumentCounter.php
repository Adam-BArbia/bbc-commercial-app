<?php

namespace App\Entity;

use App\Repository\DocumentCounterRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DocumentCounterRepository::class)]
#[ORM\Table(name: 'document_counter')]
class DocumentCounter
{
    #[ORM\Id]
    #[ORM\Column(length: 50)]
    private ?string $document_type = null;

    #[ORM\Id]
    #[ORM\Column]
    private ?int $year = null;

    #[ORM\Column]
    private int $last_number = 0;

    public function getDocumentType(): ?string
    {
        return $this->document_type;
    }

    public function setDocumentType(string $document_type): static
    {
        $this->document_type = $document_type;
        return $this;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;
        return $this;
    }

    public function getLastNumber(): int
    {
        return $this->last_number;
    }

    public function setLastNumber(int $last_number): static
    {
        $this->last_number = $last_number;
        return $this;
    }

    /**
     * Get next number and increment counter
     */
    public function getNextNumber(): int
    {
        $this->last_number++;
        return $this->last_number;
    }
}
