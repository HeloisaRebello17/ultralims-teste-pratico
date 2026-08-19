<?php

declare(strict_types=1);

namespace App\UltralimsTesteBackend\Application;

use DateTimeImmutable;
use App\UltralimsTesteBackend\Application\Exception\SampleNotFoundException;
use App\UltralimsTesteBackend\Domain\Sample;

class UpdateSampleStatus
{
    public function __construct(
        private readonly SampleRepositoryInterface $repository
    ) {
    }

    /**
     * @param DateTimeImmutable|null $concludedAt Obrigatório apenas quando $action for Concluída.
     */
    public function execute(
        string $id,
        SampleStatusAction $action,
        ?DateTimeImmutable $concludedAt = null
    ): Sample {
        $sample = $this->repository->findById($id);

        if ($sample === null) {
            throw SampleNotFoundException::withId($id);
        }

        match ($action) {
            SampleStatusAction::StartAnalysis => $sample->startAnalysis(),
            SampleStatusAction::Conclude => $sample->conclude($concludedAt ?? new DateTimeImmutable()),
            SampleStatusAction::Reject => $sample->reject(),
        };

        $this->repository->save($sample);

        return $sample;
    }
}