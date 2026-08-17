<?php

declare(strict_types=1);

namespace App\UltralimsTesteBackend\Infrastructure\Http\Controller;

use DateTimeImmutable;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\UltralimsTesteBackend\Application\CreateSample;
use App\UltralimsTesteBackend\Application\Exception\SampleNotFoundException;
use App\UltralimsTesteBackend\Application\GetSample;
use App\UltralimsTesteBackend\Application\ListSamples;
use App\UltralimsTesteBackend\Application\SampleStatusAction;
use App\UltralimsTesteBackend\Application\UpdateSampleStatus;
use App\UltralimsTesteBackend\Domain\Exception\InvalidTransitionException;
use App\UltralimsTesteBackend\Domain\Sample;
use App\UltralimsTesteBackend\Domain\SampleStatus;
use App\UltralimsTesteBackend\Domain\SampleType;

class SampleController
{
    public function __construct(
        private readonly CreateSample $createSample,
        private readonly ListSamples $listSamples,
        private readonly GetSample $getSample,
        private readonly UpdateSampleStatus $updateSampleStatus
    ) {
    }

    public function create(Request $request, Response $response): Response
    {
        $body = json_decode((string) $request->getBody(), true) ?? [];

        if (!isset($body['type']) || !isset($body['receivedAt'])) {
            return $this->jsonError($response, 'Os campos tipo e data de recebimento são obrigatórios.', 400);
        }

        $type = SampleType::tryFrom($body['type']);
        if ($type === null) {
            return $this->jsonError($response, 'Valor inválido para o tipo da amostra.', 400);
        }

        try {
            $receivedAt = new DateTimeImmutable($body['receivedAt']);
        } catch (\Exception) {
            return $this->jsonError($response, 'Formato inválido para a data de recebimento.', 400);
        }

        $sample = $this->createSample->execute(
            type: $type,
            receivedAt: $receivedAt,
            technicalResponsible: $body['technicalResponsible'] ?? null
        );

        return $this->jsonResponse($response, $this->toArray($sample), 201);
    }

    public function list(Request $request, Response $response): Response
    {
        $query = $request->getQueryParams();

        $status = isset($query['status']) ? SampleStatus::tryFrom($query['status']) : null;
        $type = isset($query['type']) ? SampleType::tryFrom($query['type']) : null;

        $samples = $this->listSamples->execute($status, $type);

        $data = array_map(fn (Sample $sample) => $this->toArray($sample), $samples);

        return $this->jsonResponse($response, $data, 200);
    }

    public function get(Request $request, Response $response, array $args): Response
    {
        try {
            $sample = $this->getSample->execute($args['id']);
        } catch (SampleNotFoundException $e) {
            return $this->jsonError($response, $e->getMessage(), 404);
        }

        return $this->jsonResponse($response, $this->toArray($sample), 200);
    }

    public function updateStatus(Request $request, Response $response, array $args): Response
    {
        $body = json_decode((string) $request->getBody(), true) ?? [];

        if (!isset($body['action'])) {
            return $this->jsonError($response, 'É necessário informar a ação desejada.', 400);
        }

        $action = SampleStatusAction::tryFrom($body['action']);
        if ($action === null) {
            return $this->jsonError(
                $response,
                'Ação inválida. Use: iniciar análise, concluir ou rejeitar.',
                400
            );
        }

        $concludedAt = null;
        if ($action === SampleStatusAction::Conclude) {
            if (!isset($body['concludedAt'])) {
                return $this->jsonError($response, 'A data de conclusão é obrigatória para concluir a amostra.', 400);
            }

            try {
                $concludedAt = new DateTimeImmutable($body['concludedAt']);
            } catch (\Exception) {
                return $this->jsonError($response, 'Formato inválido para a data de conclusão.', 400);
            }
        }

        try {
            $sample = $this->updateSampleStatus->execute($args['id'], $action, $concludedAt);
        } catch (SampleNotFoundException $e) {
            return $this->jsonError($response, $e->getMessage(), 404);
        } catch (InvalidTransitionException $e) {
            return $this->jsonError($response, $e->getMessage(), 422);
        }

        return $this->jsonResponse($response, $this->toArray($sample), 200);
    }

    /**
     * @return array<string, mixed>
     */
    private function toArray(Sample $sample): array
    {
        return [
            'id' => $sample->getId(),
            'code' => $sample->getCode(),
            'type' => $sample->getType()->value,
            'status' => $sample->getStatus()->value,
            'technicalResponsible' => $sample->getTechnicalResponsible(),
            'receivedAt' => $sample->getReceivedAt()->format(DateTimeImmutable::ATOM),
            'concludedAt' => $sample->getConcludedAt()?->format(DateTimeImmutable::ATOM),
        ];
    }

    private function jsonResponse(Response $response, mixed $data, int $status): Response
    {
        $response->getBody()->write((string) json_encode($data, JSON_UNESCAPED_UNICODE));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }

    private function jsonError(Response $response, string $message, int $status): Response
    {
        return $this->jsonResponse($response, ['error' => $message], $status);
    }
}