<?php

declare(strict_types=1);

namespace App\UltralimsTesteBackend\Domain;

use DateTimeImmutable;
use App\UltralimsTesteBackend\Domain\Exception\InvalidTransitionException;

class Sample
{
    private string $id;
    private string $code;
    private SampleType $type;
    private SampleStatus $status;
    private ?string $technicalResponsible;
    private DateTimeImmutable $receivedAt;
    private ?DateTimeImmutable $concludedAt;

    public function __construct(
        string $id,
        string $code,
        SampleType $type,
        DateTimeImmutable $receivedAt,
        ?string $technicalResponsible = null
    ) {
        $this->id = $id;
        $this->code = $code;
        $this->type = $type;
        $this->receivedAt = $receivedAt;
        $this->technicalResponsible = $technicalResponsible;
        $this->status = SampleStatus::Received;
        $this->concludedAt = null;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function getType(): SampleType
    {
        return $this->type;
    }

    public function getStatus(): SampleStatus
    {
        return $this->status;
    }

    public function getTechnicalResponsible(): ?string
    {
        return $this->technicalResponsible;
    }

    public function getReceivedAt(): DateTimeImmutable
    {
        return $this->receivedAt;
    }

    public function getConcludedAt(): ?DateTimeImmutable
    {
        return $this->concludedAt;
    }

    public function setTechnicalResponsible(string $technicalResponsible): void
    {
        $this->ensureNotFinalized();
        $this->technicalResponsible = $technicalResponsible;
    }

    public function startAnalysis(): void
    {
        $this->ensureNotFinalized();

        if ($this->status !== SampleStatus::Received) {
            throw new InvalidTransitionException(
                "Não é possível iniciar a análise a partir do status {$this->status->value}."
            );
        }

        if ($this->technicalResponsible === null || trim($this->technicalResponsible) === '') {
            throw new InvalidTransitionException(
                'Não é possível iniciar a análise sem um responsável técnico definido.'
            );
        }

        $this->status = SampleStatus::UnderAnalysis;
    }

    public function conclude(DateTimeImmutable $concludedAt): void
    {
        $this->ensureNotFinalized();

        if ($this->status !== SampleStatus::UnderAnalysis) {
            throw new InvalidTransitionException(
                "A amostra só pode ser concluída a partir do status EmAnalise. Status atual: {$this->status->value}."
            );
        }

        if ($concludedAt < $this->receivedAt) {
            throw new InvalidTransitionException(
                'A data de conclusão não pode ser anterior à data de recebimento.'
            );
        }

        $this->concludedAt = $concludedAt;
        $this->status = SampleStatus::Completed;
    }

    public function reject(): void
    {
        $this->ensureNotFinalized();

        if (!in_array($this->status, [SampleStatus::Received, SampleStatus::UnderAnalysis], true)) {
            throw new InvalidTransitionException(
                "A amostra só pode ser rejeitada a partir do status Recebida ou EmAnalise. Status atual: {$this->status->value}."
            );
        }

        $this->status = SampleStatus::Rejected;
    }

    private function ensureNotFinalized(): void
    {
        if ($this->status->isFinal()) {
            throw new InvalidTransitionException(
                "O estado final da amostra ({$this->status->value}) não pode ser alterado."
            );
        }
    }
}