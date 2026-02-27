<?php

namespace App\Entity;

use App\Repository\AuditLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AuditLogRepository::class)]
#[ORM\Table(name: 'audit_log')]
#[ORM\Index(columns: ['table_name'])]
#[ORM\Index(columns: ['performed_by'])]
#[ORM\Index(columns: ['performed_at'])]
class AuditLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $table_name = null;

    #[ORM\Column]
    private ?int $record_id = null;

    #[ORM\Column(length: 50)]
    private ?string $action = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $old_data = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $new_data = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $performed_by = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $performed_at = null;

    public function __construct()
    {
        $this->performed_at = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTableName(): ?string
    {
        return $this->table_name;
    }

    public function setTableName(string $table_name): static
    {
        $this->table_name = $table_name;
        return $this;
    }

    public function getRecordId(): ?int
    {
        return $this->record_id;
    }

    public function setRecordId(int $record_id): static
    {
        $this->record_id = $record_id;
        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): static
    {
        $this->action = $action;
        return $this;
    }

    public function getOldData(): ?array
    {
        return $this->old_data;
    }

    public function setOldData(?array $old_data): static
    {
        $this->old_data = $old_data;
        return $this;
    }

    public function getNewData(): ?array
    {
        return $this->new_data;
    }

    public function setNewData(?array $new_data): static
    {
        $this->new_data = $new_data;
        return $this;
    }

    public function getPerformedBy(): ?User
    {
        return $this->performed_by;
    }

    public function setPerformedBy(?User $performed_by): static
    {
        $this->performed_by = $performed_by;
        return $this;
    }

    public function getPerformedAt(): ?\DateTimeInterface
    {
        return $this->performed_at;
    }

    public function setPerformedAt(\DateTimeInterface $performed_at): static
    {
        $this->performed_at = $performed_at;
        return $this;
    }
}
