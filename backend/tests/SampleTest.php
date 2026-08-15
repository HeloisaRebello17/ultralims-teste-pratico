<?php

declare(strict_types=1);

namespace App\UltralimsTesteBackend\Tests;

use DateTimeImmutable;
use App\UltralimsTesteBackend\Domain\Sample;
use App\UltralimsTesteBackend\Domain\Exception\InvalidTransitionException;
use App\UltralimsTesteBackend\Domain\SampleType;
use App\UltralimsTesteBackend\Domain\SampleStatus;
use PHPUnit\Framework\TestCase;

class SampleTest extends TestCase
{
    private function createSample(?string $technicalResponsible = null): Sample
    {
        return new Sample(
            id: '1',
            code: 'CAND047-2026-0001',
            type: SampleType::Water,
            receivedAt: new DateTimeImmutable('2026-01-10'),
            technicalResponsible: $technicalResponsible
        );
    }

    public function testSampleIsCreatedWithStatusReceived(): void
    {
        $sample = $this->createSample();

        $this->assertSame(SampleStatus::Received, $sample->getStatus());
    }

    public function testCannotStartAnalysisWithoutTechnicalResponsible(): void
    {
        $sample = $this->createSample(technicalResponsible: null);

        $this->expectException(InvalidTransitionException::class);

        $sample->startAnalysis();
    }

    public function testCanStartAnalysisWithTechnicalResponsible(): void
    {
        $sample = $this->createSample(technicalResponsible: 'John Doe');

        $sample->startAnalysis();

        $this->assertSame(SampleStatus::UnderAnalysis, $sample->getStatus());
    }

    public function testCanSetTechnicalResponsibleLaterAndThenStartAnalysis(): void
    {
        $sample = $this->createSample(technicalResponsible: null);

        $sample->setTechnicalResponsible('John Doe');
        $sample->startAnalysis();

        $this->assertSame(SampleStatus::UnderAnalysis, $sample->getStatus());
    }

    public function testCannotConcludeSampleThatIsNotUnderAnalysis(): void
    {
        $sample = $this->createSample(technicalResponsible: 'John Doe');

        $this->expectException(InvalidTransitionException::class);

        $sample->conclude(new DateTimeImmutable('2026-01-15'));
    }

    public function testCannotConcludeWithConclusionDateBeforeReceivedDate(): void
    {
        $sample = $this->createSample(technicalResponsible: 'John Doe');
        $sample->startAnalysis();

        $this->expectException(InvalidTransitionException::class);

        $sample->conclude(new DateTimeImmutable('2026-01-05'));
    }

    public function testCanConcludeWithConclusionDateEqualToReceivedDate(): void
    {
        $sample = $this->createSample(technicalResponsible: 'John Doe');
        $sample->startAnalysis();

        $sample->conclude(new DateTimeImmutable('2026-01-10'));

        $this->assertSame(SampleStatus::Completed, $sample->getStatus());
        $this->assertEquals(new DateTimeImmutable('2026-01-10'), $sample->getConcludedAt());
    }

    public function testCanConcludeWithConclusionDateAfterReceivedDate(): void
    {
        $sample = $this->createSample(technicalResponsible: 'John Doe');
        $sample->startAnalysis();

        $sample->conclude(new DateTimeImmutable('2026-01-20'));

        $this->assertSame(SampleStatus::Completed, $sample->getStatus());
    }

    public function testCanRejectFromReceived(): void
    {
        $sample = $this->createSample();

        $sample->reject();

        $this->assertSame(SampleStatus::Rejected, $sample->getStatus());
    }

    public function testCanRejectFromUnderAnalysis(): void
    {
        $sample = $this->createSample(technicalResponsible: 'John Doe');
        $sample->startAnalysis();

        $sample->reject();

        $this->assertSame(SampleStatus::Rejected, $sample->getStatus());
    }

    public function testCannotRejectCompletedSample(): void
    {
        $sample = $this->createSample(technicalResponsible: 'John Doe');
        $sample->startAnalysis();
        $sample->conclude(new DateTimeImmutable('2026-01-15'));

        $this->expectException(InvalidTransitionException::class);

        $sample->reject();
    }

    public function testCompletedSampleCannotStartAnalysisAgain(): void
    {
        $sample = $this->createSample(technicalResponsible: 'John Doe');
        $sample->startAnalysis();
        $sample->conclude(new DateTimeImmutable('2026-01-15'));

        $this->expectException(InvalidTransitionException::class);

        $sample->startAnalysis();
    }

    public function testRejectedSampleCannotBeConcluded(): void
    {
        $sample = $this->createSample(technicalResponsible: 'John Doe');
        $sample->reject();

        $this->expectException(InvalidTransitionException::class);

        $sample->conclude(new DateTimeImmutable('2026-01-15'));
    }

    public function testRejectedSampleCannotBeRejectedAgain(): void
    {
        $sample = $this->createSample();
        $sample->reject();

        $this->expectException(InvalidTransitionException::class);

        $sample->reject();
    }

    public function testCompletedSampleCannotHaveTechnicalResponsibleChanged(): void
    {
        $sample = $this->createSample(technicalResponsible: 'John Doe');
        $sample->startAnalysis();
        $sample->conclude(new DateTimeImmutable('2026-01-15'));

        $this->expectException(InvalidTransitionException::class);

        $sample->setTechnicalResponsible('Someone Else');
    }
}