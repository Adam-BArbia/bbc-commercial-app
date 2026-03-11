<?php

namespace App\EventSubscriber;

use App\Entity\AuditLog;
use App\Entity\User;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Bundle\SecurityBundle\Security;

class AuditLogSubscriber implements EventSubscriber
{
    /**
     * @var array<int, array{table: string, record_id: int, old_data: array<string, mixed>|null}>
     */
    private array $pendingRemovals = [];

    public function __construct(private readonly Security $security)
    {
    }

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::preUpdate,
            Events::preRemove,
            Events::postRemove,
        ];
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($this->shouldSkipEntity($entity)) {
            return;
        }

        $em = $args->getObjectManager();
        $meta = $em->getClassMetadata($entity::class);

        $this->insertAuditRow(
            $em,
            $this->resolveTableName($meta, $entity),
            $this->resolveEntityId($entity),
            'CREATE',
            null,
            $this->snapshotEntity($entity, $meta)
        );
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($this->shouldSkipEntity($entity)) {
            return;
        }

        $em = $args->getObjectManager();
        $meta = $em->getClassMetadata($entity::class);

        $oldData = [];
        $newData = [];

        foreach ($args->getEntityChangeSet() as $field => $values) {
            $oldData[$field] = $this->normalizeValue($values[0] ?? null);
            $newData[$field] = $this->normalizeValue($values[1] ?? null);
        }

        $this->insertAuditRow(
            $em,
            $this->resolveTableName($meta, $entity),
            $this->resolveEntityId($entity),
            'UPDATE',
            $oldData,
            $newData
        );
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($this->shouldSkipEntity($entity)) {
            return;
        }

        $em = $args->getObjectManager();
        $meta = $em->getClassMetadata($entity::class);

        $this->pendingRemovals[spl_object_id($entity)] = [
            'table' => $this->resolveTableName($meta, $entity),
            'record_id' => $this->resolveEntityId($entity),
            'old_data' => $this->snapshotEntity($entity, $meta),
        ];
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($this->shouldSkipEntity($entity)) {
            return;
        }

        $key = spl_object_id($entity);
        $payload = $this->pendingRemovals[$key] ?? null;
        unset($this->pendingRemovals[$key]);

        if ($payload === null) {
            return;
        }

        $em = $args->getObjectManager();

        $this->insertAuditRow(
            $em,
            $payload['table'],
            $payload['record_id'],
            'DELETE',
            $payload['old_data'],
            null
        );
    }

    private function shouldSkipEntity(object $entity): bool
    {
        if ($entity instanceof AuditLog) {
            return true;
        }

        return $this->currentUserId() === null;
    }

    private function currentUserId(): ?int
    {
        $user = $this->security->getUser();

        return $user instanceof User ? $user->getId() : null;
    }

    private function resolveEntityId(object $entity): int
    {
        if (method_exists($entity, 'getId')) {
            $id = $entity->getId();
            if (is_numeric($id)) {
                return (int) $id;
            }
        }

        return 0;
    }

    /**
     * @return array<string, mixed>
     */
    private function snapshotEntity(object $entity, ClassMetadata $meta): array
    {
        $snapshot = [];

        foreach ($meta->getFieldNames() as $field) {
            $snapshot[$field] = $this->normalizeValue($meta->getFieldValue($entity, $field));
        }

        foreach ($meta->getAssociationNames() as $association) {
            if (!$meta->isSingleValuedAssociation($association)) {
                continue;
            }

            $related = $meta->getFieldValue($entity, $association);
            if (is_object($related) && method_exists($related, 'getId')) {
                $snapshot[$association . '_id'] = $this->normalizeValue($related->getId());
            } else {
                $snapshot[$association . '_id'] = null;
            }
        }

        return $snapshot;
    }

    private function normalizeValue(mixed $value): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if (is_scalar($value) || $value === null) {
            return $value;
        }

        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $k => $v) {
                $normalized[$k] = $this->normalizeValue($v);
            }
            return $normalized;
        }

        if (is_object($value) && method_exists($value, 'getId')) {
            return $value->getId();
        }

        return (string) $value;
    }

    private function resolveTableName(object $meta, object $entity): string
    {
        if (method_exists($meta, 'getTableName')) {
            return (string) $meta->getTableName();
        }

        return strtolower((new \ReflectionClass($entity))->getShortName());
    }

    /**
     * @param array<string, mixed>|null $oldData
     * @param array<string, mixed>|null $newData
     */
    private function insertAuditRow(
        EntityManagerInterface $entityManager,
        string $tableName,
        int $recordId,
        string $action,
        ?array $oldData,
        ?array $newData
    ): void {
        $userId = $this->currentUserId();
        if ($userId === null) {
            return;
        }

        $entityManager->getConnection()->insert('audit_log', [
            'table_name' => $tableName,
            'record_id' => $recordId,
            'action' => $action,
            'old_data' => $oldData !== null ? json_encode($oldData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'new_data' => $newData !== null ? json_encode($newData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'performed_by' => $userId,
            'performed_at' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);
    }
}
