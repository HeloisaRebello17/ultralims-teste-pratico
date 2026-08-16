<?php

declare(strict_types=1);

namespace App\UltralimsTesteBackend\Tests\Fake\Application;

use DateTimeImmutable;
use App\UltralimsTesteBackend\Application\CreateSample;
use App\UltralimsTesteBackend\Domain\SampleStatus;
use App\UltralimsTesteBackend\Domain\SampleType;
use PHPUnit\Framework\TestCase;

class CreateSampleTest extends TestCase
{
    public function testCreatesSampleWithStatusReceived(): void
    {
        $useCase = new CreateSample(new SampleRepositoryFake(), 'CAND047');

        $sample = $useCase->execute(
            type: SampleType::Water,
            receivedAt: new DateTimeImmutable('2026-03-10')
        );

        $this->assertSame(SampleStatus::Received, $sample->getStatus());
        $this->assertSame(SampleType::Water, $sample->getType());
        $this->assertNull($sample->getTechnicalResponsible());
    }

    public function testGeneratesCodeInExpectedFormat(): void
    {
        $useCase = new CreateSample(new SampleRepositoryFake(), 'CAND047');

        $sample = $useCase->execute(
            type: SampleType::Soil,
            receivedAt: new DateTimeImmutable('2026-01-15')
        );

        $this->assertSame('CAND047-2026-0001', $sample->getCode());
    }

    public function testSequentialIncrementsForSamePrefixAndYear(): void
    {
        $repository = new SampleRepositoryFake();
        $useCase = new CreateSample($repository, 'CAND047');

        $first = $useCase->execute(SampleType::Water, new DateTimeImmutable('2026-01-10'));
        $second = $useCase->execute(SampleType::Air, new DateTimeImmutable('2026-06-20'));
        $third = $useCase->execute(SampleType::Soil, new DateTimeImmutable('2026-12-01'));

        $this->assertSame('CAND047-2026-0001', $first->getCode());
        $this->assertSame('CAND047-2026-0002', $second->getCode());
        $this->assertSame('CAND047-2026-0003', $third->getCode());
    }

    public function testSequentialRestartsForADifferentYear(): void
    {
        $repository = new SampleRepositoryFake();
        $useCase = new CreateSample($repository, 'CAND047');

        $useCase->execute(SampleType::Water, new DateTimeImmutable('2025-12-30'));
        $useCase->execute(SampleType::Water, new DateTimeImmutable('2025-12-31'));
        $sampleIn2026 = $useCase->execute(SampleType::Water, new DateTimeImmutable('2026-01-01'));

        // reinicia em 1, mesmo já havendo 2 amostras criadas em 2025
        $this->assertSame('CAND047-2026-0001', $sampleIn2026->getCode());
    }

    public function testSavesSampleInRepository(): void
    {
        $repository = new SampleRepositoryFake();
        $useCase = new CreateSample($repository, 'CAND047');

        $sample = $useCase->execute(SampleType::Effluent, new DateTimeImmutable('2026-02-01'));

        $this->assertSame($sample, $repository->findById($sample->getId()));
    }

    public function testCreatesSampleWithOptionalTechnicalResponsible(): void
    {
        $useCase = new CreateSample(new SampleRepositoryFake(), 'CAND047');

        $sample = $useCase->execute(
            type: SampleType::Water,
            receivedAt: new DateTimeImmutable('2026-01-10'),
            technicalResponsible: 'John Doe'
        );

        $this->assertSame('John Doe', $sample->getTechnicalResponsible());
    }
}