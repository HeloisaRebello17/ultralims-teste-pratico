<?php

declare(strict_types=1);

namespace App\UltralimsTesteBackend\Application\Exception;

use DomainException;

class SampleNotFoundException extends DomainException
{
    public static function withId(string $id): self
    {
        return new self("Amostra com id '{$id}' não encontrada.");
    }
}