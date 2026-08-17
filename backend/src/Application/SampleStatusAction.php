<?php

declare(strict_types=1);

namespace App\UltralimsTesteBackend\Application;


enum SampleStatusAction: string
{
    case StartAnalysis = 'start_analysis';
    case Conclude = 'conclude';
    case Reject = 'reject';
}