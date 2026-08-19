<?php

declare(strict_types=1);

namespace App\UltralimsTesteBackend\Application;

use App\UltralimsTesteBackend\Application\Exception\SampleNotFoundException;
use App\UltralimsTesteBackend\Domain\Sample;

class SetSampleTechnicalResponsible
{
    public function __construct(
        private readonly SampleRepositoryInterface $repository
    ) {
    }

    public function execute(string $id, string $technicalResponsible): Sample
    {
        $sample = $this->repository->findById($id);
        if ($sample === null) {
            throw SampleNotFoundException::withId($id);
        }

        $sample->setTechnicalResponsible($technicalResponsible);
        $this->repository->save($sample);

        return $sample;
    }
}