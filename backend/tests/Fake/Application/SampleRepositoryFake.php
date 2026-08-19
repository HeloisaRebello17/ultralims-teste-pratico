<?php

declare(strict_types=1);

namespace App\UltralimsTesteBackend\Tests\Fake\Application;

use App\UltralimsTesteBackend\Application\SampleRepositoryInterface;
use App\UltralimsTesteBackend\Domain\Sample;
use App\UltralimsTesteBackend\Domain\SampleStatus;
use App\UltralimsTesteBackend\Domain\SampleType;


class SampleRepositoryFake implements SampleRepositoryInterface
{
    /** @var array<string, Sample> */
    private array $samples = [];

    public function save(Sample $sample): void
    {
        $this->samples[$sample->getId()] = $sample;
    }

    public function findById(string $id): ?Sample
    {
        return $this->samples[$id] ?? null;
    }

    public function findAll(?SampleStatus $status = null, ?SampleType $type = null): array
    {
        return array_values(array_filter(
            $this->samples,
            function (Sample $sample) use ($status, $type): bool {
                if ($status !== null && $sample->getStatus() !== $status) {
                    return false;
                }

                if ($type !== null && $sample->getType() !== $type) {
                    return false;
                }

                return true;
            }
        ));
    }

    public function nextSequentialForYear(string $prefix, int $year): int
    {
        $count = 0;

        foreach ($this->samples as $sample) {
            if (str_starts_with($sample->getCode(), "{$prefix}-{$year}-")) {
                $count++;
            }
        }

        return $count + 1;
    }
}