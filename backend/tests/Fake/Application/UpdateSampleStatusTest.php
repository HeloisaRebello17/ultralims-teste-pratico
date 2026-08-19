<?php

declare(strict_types=1);

namespace App\UltralimsTesteBackend\Tests\Fake\Application;

use DateTimeImmutable;
use App\UltralimsTesteBackend\Application\CreateSample;
use App\UltralimsTesteBackend\Application\Exception\SampleNotFoundException;
use App\UltralimsTesteBackend\Application\SampleStatusAction;
use App\UltralimsTesteBackend\Application\UpdateSampleStatus;
use App\UltralimsTesteBackend\Domain\Exception\InvalidTransitionException;
use App\UltralimsTesteBackend\Domain\SampleStatus;
use App\UltralimsTesteBackend\Domain\SampleType;
use PHPUnit\Framework\TestCase;

class UpdateSampleStatusTest extends TestCase
{
    public function testThrowsExceptionWhenSampleDoesNotExist(): void
    {
        $repository = new SampleRepositoryFake();
        $updateStatus = new UpdateSampleStatus($repository);

        $this->expectException(SampleNotFoundException::class);

        $updateStatus->execute('id-inexistente', SampleStatusAction::StartAnalysis);
    }

    public function testStartsAnalysisWhenTechnicalResponsibleIsSet(): void
    {
        $repository = new SampleRepositoryFake();
        $createSample = new CreateSample($repository, 'CAND047');
        $sample = $createSample->execute(
            SampleType::Water,
            new DateTimeImmutable('2026-01-10'),
            technicalResponsible: 'John Doe'
        );

        $updateStatus = new UpdateSampleStatus($repository);
        $updated = $updateStatus->execute($sample->getId(), SampleStatusAction::StartAnalysis);

        $this->assertSame(SampleStatus::UnderAnalysis, $updated->getStatus());
    }

    public function testPropagatesDomainExceptionWhenStartingAnalysisWithoutTechnicalResponsible(): void
    {
        $repository = new SampleRepositoryFake();
        $createSample = new CreateSample($repository, 'CAND047');
        $sample = $createSample->execute(SampleType::Water, new DateTimeImmutable('2026-01-10'));

        $updateStatus = new UpdateSampleStatus($repository);
        $this->expectException(InvalidTransitionException::class);

        $updateStatus->execute($sample->getId(), SampleStatusAction::StartAnalysis);
    }

    public function testConcludesSampleWithGivenDate(): void
    {
        $repository = new SampleRepositoryFake();
        $createSample = new CreateSample($repository, 'CAND047');
        $sample = $createSample->execute(
            SampleType::Water,
            new DateTimeImmutable('2026-01-10'),
            technicalResponsible: 'John Doe'
        );

        $updateStatus = new UpdateSampleStatus($repository);
        $updateStatus->execute($sample->getId(), SampleStatusAction::StartAnalysis);
        $updated = $updateStatus->execute(
            $sample->getId(),
            SampleStatusAction::Conclude,
            new DateTimeImmutable('2026-01-20')
        );

        $this->assertSame(SampleStatus::Completed, $updated->getStatus());
        $this->assertEquals(new DateTimeImmutable('2026-01-20'), $updated->getConcludedAt());
    }

    public function testRejectsSampleFromReceived(): void
    {
        $repository = new SampleRepositoryFake();
        $createSample = new CreateSample($repository, 'CAND047');
        $sample = $createSample->execute(SampleType::Water, new DateTimeImmutable('2026-01-10'));

        $updateStatus = new UpdateSampleStatus($repository);
        $updated = $updateStatus->execute($sample->getId(), SampleStatusAction::Reject);

        $this->assertSame(SampleStatus::Rejected, $updated->getStatus());
    }

    public function testPersistsUpdatedStatusInRepository(): void
    {
        $repository = new SampleRepositoryFake();
        $createSample = new CreateSample($repository, 'CAND047');
        $sample = $createSample->execute(SampleType::Water, new DateTimeImmutable('2026-01-10'));

        $updateStatus = new UpdateSampleStatus($repository);
        $updateStatus->execute($sample->getId(), SampleStatusAction::Reject);

        $persisted = $repository->findById($sample->getId());// busca de novo no repositório para confirmar que o save() persistiu a mudança
        $this->assertSame(SampleStatus::Rejected, $persisted->getStatus());
    }
}