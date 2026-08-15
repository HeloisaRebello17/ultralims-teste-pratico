<?php

declare(strict_types=1);

namespace App\UltralimsTesteBackend\Tests\Application;

use DateTimeImmutable;
use App\UltralimsTesteBackend\Application\CreateSample;
use App\UltralimsTesteBackend\Application\Exception\SampleNotFoundException;
use App\UltralimsTesteBackend\Application\GetSample;
use App\UltralimsTesteBackend\Domain\SampleType;
use PHPUnit\Framework\TestCase;

class GetSampleTest extends TestCase
{
    public function testReturnsSampleWhenItExists(): void
    {
        $repository = new SampleRepositoryFake();
        $createSample = new CreateSample($repository, 'CAND047');
        $created = $createSample->execute(SampleType::Water, new DateTimeImmutable('2026-01-10'));

        $getSample = new GetSample($repository);
        $result = $getSample->execute($created->getId());

        $this->assertSame($created->getId(), $result->getId());
        $this->assertSame($created->getCode(), $result->getCode());
    }

    public function testThrowsExceptionWhenSampleDoesNotExist(): void
    {
        $repository = new SampleRepositoryFake();
        $getSample = new GetSample($repository);

        $this->expectException(SampleNotFoundException::class);

        $getSample->execute('id-inexistente');
    }
}