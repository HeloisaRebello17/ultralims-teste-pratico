<?php

declare(strict_types=1);

namespace App\UltralimsTesteBackend\Application;

use DateTimeImmutable;
use Ramsey\Uuid\Uuid;
use App\UltralimsTesteBackend\Domain\Sample;
use App\UltralimsTesteBackend\Domain\SampleType;

class CreateSample
{
    public function __construct(
        private readonly SampleRepositoryInterface $repository,
        private readonly string $samplePrefix
    ) {
    }

    public function execute(
        SampleType $type,
        DateTimeImmutable $receivedAt,
        ?string $technicalResponsible = null
    ): Sample {
        $year = (int) $receivedAt->format('Y');
        $sequential = $this->repository->nextSequentialForYear($this->samplePrefix, $year);
        $code = sprintf('%s-%d-%04d', $this->samplePrefix, $year, $sequential);

        $sample = new Sample(
            id: Uuid::uuid4()->toString(),
            code: $code,
            type: $type,
            receivedAt: $receivedAt,
            technicalResponsible: $technicalResponsible
        );

        $this->repository->save($sample);

        return $sample;
    }
}