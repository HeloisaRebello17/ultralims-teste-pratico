<?php

declare(strict_types=1);

namespace App\UltralimsTesteBackend\Application;

use App\UltralimsTesteBackend\Domain\Sample;
use App\UltralimsTesteBackend\Domain\SampleStatus;
use App\UltralimsTesteBackend\Domain\SampleType;

interface SampleRepositoryInterface
{
    public function save(Sample $sample): void;

    public function findById(string $id): ?Sample;

    /**
     * @return Sample[]
     */
    public function findAll(?SampleStatus $status = null, ?SampleType $type = null): array;

    public function nextSequentialForYear(string $prefix, int $year): int;
}