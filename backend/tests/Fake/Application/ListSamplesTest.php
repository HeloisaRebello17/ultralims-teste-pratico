<?php

declare(strict_types=1);

namespace App\UltralimsTesteBackend\Tests\Fake\Application;

use DateTimeImmutable;
use App\UltralimsTesteBackend\Application\CreateSample;
use App\UltralimsTesteBackend\Application\ListSamples;
use App\UltralimsTesteBackend\Application\SampleStatusAction;
use App\UltralimsTesteBackend\Application\UpdateSampleStatus;
use App\UltralimsTesteBackend\Domain\SampleStatus;
use App\UltralimsTesteBackend\Domain\SampleType;
use PHPUnit\Framework\TestCase;

class ListSamplesTest extends TestCase
{
    public function testListsAllSamplesWhenNoFilterIsGiven(): void
    {
        $repository = new SampleRepositoryFake();
        $createSample = new CreateSample($repository, 'CAND047');

        $createSample->execute(SampleType::Water, new DateTimeImmutable('2026-01-10'));
        $createSample->execute(SampleType::Soil, new DateTimeImmutable('2026-01-11'));

        $listSamples = new ListSamples($repository);
        $result = $listSamples->execute();

        $this->assertCount(2, $result);
    }

    public function testFiltersByStatus(): void
    {
        $repository = new SampleRepositoryFake();
        $createSample = new CreateSample($repository, 'CAND047');
        $updateStatus = new UpdateSampleStatus($repository);

        $received = $createSample->execute(SampleType::Water, new DateTimeImmutable('2026-01-10'));
        $toReject = $createSample->execute(SampleType::Soil, new DateTimeImmutable('2026-01-11'));
        $updateStatus->execute($toReject->getId(), SampleStatusAction::Reject);

        $listSamples = new ListSamples($repository);
        $result = $listSamples->execute(status: SampleStatus::Received);

        $this->assertCount(1, $result);
        $this->assertSame($received->getId(), $result[0]->getId());
    }

    public function testFiltersByType(): void
    {
        $repository = new SampleRepositoryFake();
        $createSample = new CreateSample($repository, 'CAND047');

        $createSample->execute(SampleType::Water, new DateTimeImmutable('2026-01-10'));
        $soilSample = $createSample->execute(SampleType::Soil, new DateTimeImmutable('2026-01-11'));

        $listSamples = new ListSamples($repository);
        $result = $listSamples->execute(type: SampleType::Soil);

        $this->assertCount(1, $result);
        $this->assertSame($soilSample->getId(), $result[0]->getId());
    }

    public function testFiltersByStatusAndTypeCombined(): void
    {
        $repository = new SampleRepositoryFake();
        $createSample = new CreateSample($repository, 'CAND047');

        $match = $createSample->execute(SampleType::Water, new DateTimeImmutable('2026-01-10'));
        $createSample->execute(SampleType::Soil, new DateTimeImmutable('2026-01-11'));

        $listSamples = new ListSamples($repository);
        $result = $listSamples->execute(status: SampleStatus::Received, type: SampleType::Water);

        $this->assertCount(1, $result);
        $this->assertSame($match->getId(), $result[0]->getId());
    }

    public function testReturnsEmptyArrayWhenNothingMatches(): void
    {
        $repository = new SampleRepositoryFake();
        $createSample = new CreateSample($repository, 'CAND047');

        $createSample->execute(SampleType::Water, new DateTimeImmutable('2026-01-10'));

        $listSamples = new ListSamples($repository);
        $result = $listSamples->execute(type: SampleType::Air);

        $this->assertCount(0, $result);
    }
}