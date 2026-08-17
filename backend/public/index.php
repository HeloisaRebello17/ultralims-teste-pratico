<?php

declare(strict_types=1);

use Slim\Factory\AppFactory;
use App\UltralimsTesteBackend\Infrastructure\Bootstrap;
use App\UltralimsTesteBackend\Infrastructure\Http\Controller\SampleController;

require __DIR__ . '/../vendor/autoload.php';

$pdo = Bootstrap::pdo();

$controller = new SampleController(
    Bootstrap::createSample($pdo),
    Bootstrap::listSamples($pdo),
    Bootstrap::getSample($pdo),
    Bootstrap::updateSampleStatus($pdo)
);

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addErrorMiddleware(true, true, true);
$app->post('/samples', [$controller, 'create']);
$app->get('/samples', [$controller, 'list']);
$app->get('/samples/{id}', [$controller, 'get']);
$app->patch('/samples/{id}/status', [$controller, 'updateStatus']);

$app->run();