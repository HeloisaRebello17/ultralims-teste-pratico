<?php
 
declare(strict_types=1);
 
namespace App\UltralimsTesteBackend\Domain;
 
enum SampleType: string
{
    case Water = 'Água';
    case Soil = 'Solo';
    case Air = 'Ar';
    case Effluent = 'Efluente';
} 