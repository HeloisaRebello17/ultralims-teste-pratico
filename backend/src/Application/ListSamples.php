<?php

declare(strict_types=1);

namespace App\UltralimsTesteBackend\Application;

use App\UltralimsTesteBackend\Domain\Sample;
use App\UltralimsTesteBackend\Domain\SampleStatus;
use App\UltralimsTesteBackend\Domain\SampleType;


class ListSamples
{
    public function __construct(
        private readonly SampleRepositoryInterface $repository
    ) {
    }

    /**
     * @return Sample[]
     */
    public function execute(?SampleStatus $status = null, ?SampleType $type = null): array
    {
        return $this->repository->findAll($status, $type);
    }
}