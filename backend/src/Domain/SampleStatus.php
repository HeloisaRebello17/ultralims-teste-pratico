<?php

declare(strict_types=1);

namespace App\UltralimsTesteBackend\Domain;

enum SampleStatus: string
{
    case Received = 'Recebida';
    case UnderAnalysis = 'EmAnalise';
    case Completed = 'Concluida';
    case Rejected = 'Rejeitada';

    public function isFinal(): bool
    {
        return $this === self::Completed || $this === self::Rejected;
    }
}