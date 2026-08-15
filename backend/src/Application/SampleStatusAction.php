<?php

declare(strict_types=1);

namespace App\UltralimsTesteBackend\Application;


enum SampleStatusAction: string
{
    case StartAnalysis = 'emAnalise';
    case Conclude = 'Concluida';
    case Reject = 'Rejeitada';
}