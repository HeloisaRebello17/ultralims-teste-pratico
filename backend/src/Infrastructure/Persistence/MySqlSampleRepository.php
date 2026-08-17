<?php

declare(strict_types=1);

namespace App\UltralimsTesteBackend\Infrastructure\Persistence;

use DateTimeImmutable;
use PDO;
use App\UltralimsTesteBackend\Application\SampleRepositoryInterface;
use App\UltralimsTesteBackend\Domain\Sample;
use App\UltralimsTesteBackend\Domain\SampleStatus;
use App\UltralimsTesteBackend\Domain\SampleType;

class MySqlSampleRepository implements SampleRepositoryInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function save(Sample $sample): void
    {
        $sql = <<<SQL
            INSERT INTO samples (id, code, type, status, technical_responsible, received_at, concluded_at)
            VALUES (:id, :code, :type, :status, :technical_responsible, :received_at, :concluded_at)
            ON DUPLICATE KEY UPDATE
                status = VALUES(status),
                technical_responsible = VALUES(technical_responsible),
                concluded_at = VALUES(concluded_at)
            SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'id' => $sample->getId(),
            'code' => $sample->getCode(),
            'type' => $sample->getType()->value,
            'status' => $sample->getStatus()->value,
            'technical_responsible' => $sample->getTechnicalResponsible(),
            'received_at' => $sample->getReceivedAt()->format('Y-m-d H:i:s'),
            'concluded_at' => $sample->getConcludedAt()?->format('Y-m-d H:i:s'),
        ]);
    }

    public function findById(string $id): ?Sample
    {
        $stmt = $this->pdo->prepare('SELECT * FROM samples WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row === false ? null : $this->hydrate($row);
    }

    public function findAll(?SampleStatus $status = null, ?SampleType $type = null): array
    {
        $sql = 'SELECT * FROM samples WHERE 1=1';
        $params = [];

        if ($status !== null) {
            $sql .= ' AND status = :status';
            $params['status'] = $status->value;
        }

        if ($type !== null) {
            $sql .= ' AND type = :type';
            $params['type'] = $type->value;
        }

        $sql .= ' ORDER BY received_at DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return array_map(
            fn (array $row) => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function nextSequentialForYear(string $prefix, int $year): int
    {
        $stmt = $this->pdo->prepare('SELECT COUNT(*) AS total FROM samples WHERE code LIKE :pattern');
        $stmt->execute(['pattern' => "{$prefix}-{$year}-%"]);
        $total = (int) $stmt->fetch(PDO::FETCH_ASSOC)['total'];

        return $total + 1;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrate(array $row): Sample
    {
        return Sample::restore(
            id: $row['id'],
            code: $row['code'],
            type: SampleType::from($row['type']),
            status: SampleStatus::from($row['status']),
            receivedAt: new DateTimeImmutable($row['received_at']),
            technicalResponsible: $row['technical_responsible'],
            concludedAt: $row['concluded_at'] !== null ? new DateTimeImmutable($row['concluded_at']) : null
        );
    }
}