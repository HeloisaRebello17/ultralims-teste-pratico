<?php

declare(strict_types=1);

namespace App\UltralimsTesteBackend\Application;


use App\UltralimsTesteBackend\Application\Exception\SampleNotFoundException;
use App\UltralimsTesteBackend\Domain\Sample;

class GetSample
{
    public function __construct(
        private readonly SampleRepositoryInterface $repository
    ) {
    }

    public function execute(string $id): Sample
    {
        $sample = $this->repository->findById($id);

        if ($sample === null) {
            throw SampleNotFoundException::withId($id);
        }

        return $sample;
    }
}