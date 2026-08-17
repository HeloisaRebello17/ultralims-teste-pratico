<?php

declare(strict_types=1);

namespace App\UltralimsTesteBackend\Infrastructure;

use PDO;
use App\UltralimsTesteBackend\Application\CreateSample;
use App\UltralimsTesteBackend\Application\GetSample;
use App\UltralimsTesteBackend\Application\ListSamples;
use App\UltralimsTesteBackend\Application\UpdateSampleStatus;
use App\UltralimsTesteBackend\Infrastructure\Persistence\MySqlSampleRepository;

final class Bootstrap
{
    public static function pdo(): PDO
    {
        $host = $_ENV['DB_HOST'] ?? 'localhost';
        $port = $_ENV['DB_PORT'] ?? '3306';
        $database = $_ENV['DB_DATABASE'] ?? 'ultralims_teste';
        $username = $_ENV['DB_USERNAME'] ?? 'root';
        $password = $_ENV['DB_PASSWORD'] ?? '';

        $dsn = "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public static function samplePrefix(): string
    {
        return $_ENV['SAMPLE_PREFIX'] ?? 'CAND000';
    }

    public static function createSample(PDO $pdo): CreateSample
    {
        return new CreateSample(new MySqlSampleRepository($pdo), self::samplePrefix());
    }

    public static function listSamples(PDO $pdo): ListSamples
    {
        return new ListSamples(new MySqlSampleRepository($pdo));
    }

    public static function getSample(PDO $pdo): GetSample
    {
        return new GetSample(new MySqlSampleRepository($pdo));
    }

    public static function updateSampleStatus(PDO $pdo): UpdateSampleStatus
    {
        return new UpdateSampleStatus(new MySqlSampleRepository($pdo));
    }
}