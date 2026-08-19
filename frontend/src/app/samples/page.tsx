"use client";

import { useCallback, useEffect, useState } from "react";
import Link from "next/link";
import { listSamples, setTechnicalResponsible, updateSampleStatus } from "@/lib/api";
import { Sample, SampleStatus, SampleType } from "@/lib/types";

const STATUS_OPTIONS: SampleStatus[] = ["Recebida", "EmAnalise", "Concluida", "Rejeitada"];
const TYPE_OPTIONS: SampleType[] = ["Água", "Solo", "Ar", "Efluente"];

type ModalState =
  | { type: "responsible"; sampleId: string }
  | { type: "conclude"; sampleId: string }
  | null;

const STATUS_BADGE_CLASSES: Record<SampleStatus, string> = {
  Recebida: "text-blue-300 bg-blue-900/50 border-blue-700/40",
  EmAnalise: "text-amber-300 bg-amber-900/40 border-amber-700/40",
  Concluida: "text-emerald-300 bg-emerald-900/40 border-emerald-700/40",
  Rejeitada: "text-red-300 bg-red-900/40 border-red-700/40",
};

const STATUS_LABELS: Record<SampleStatus, string> = {
  Recebida: "Recebida",
  EmAnalise: "Em análise",
  Concluida: "Concluída",
  Rejeitada: "Rejeitada",
};

function StatusBadge({ status }: { status: SampleStatus }) {
  return (
    <span
      className={`inline-block rounded-full border px-2.5 py-0.5 text-xs font-medium ${STATUS_BADGE_CLASSES[status]}`}
    >
      {STATUS_LABELS[status]}
    </span>
  );
}

export default function SamplesPage() {
  const [samples, setSamples] = useState<Sample[]>([]);
  const [statusFilter, setStatusFilter] = useState<SampleStatus | "">("");
  const [typeFilter, setTypeFilter] = useState<SampleType | "">("");
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionError, setActionError] = useState<string | null>(null);

  const [modal, setModal] = useState<ModalState>(null);
  const [modalValue, setModalValue] = useState("");
  const [modalSubmitting, setModalSubmitting] = useState(false);

  const loadSamples = useCallback(async () => {
    setLoading(true);
    setError(null);

    try {
      const data = await listSamples({
        status: statusFilter || undefined,
        type: typeFilter || undefined,
      });
      setSamples(data);
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erro ao carregar amostras.");
    } finally {
      setLoading(false);
    }
  }, [statusFilter, typeFilter]);

  useEffect(() => {
    loadSamples();
  }, [loadSamples]);

  async function handleAction(id: string, action: "start_analysis" | "reject") {
    setActionError(null);

    try {
      await updateSampleStatus(id, action);
      await loadSamples();
    } catch (err) {
      setActionError(err instanceof Error ? err.message : "Erro ao atualizar amostra.");
    }
  }

  function openConcludeModal(id: string) {
    setActionError(null);
    setModalValue(new Date().toISOString().slice(0, 10));
    setModal({ type: "conclude", sampleId: id });
  }

  function openResponsibleModal(id: string) {
    setActionError(null);
    setModalValue("");
    setModal({ type: "responsible", sampleId: id });
  }

  function closeModal() {
    setModal(null);
    setModalValue("");
  }

  async function handleModalConfirm() {
    if (!modal) return;

    if (!modalValue.trim()) {
      return;
    }

    setModalSubmitting(true);
    setActionError(null);

    try {
      if (modal.type === "conclude") {
        await updateSampleStatus(modal.sampleId, "conclude", modalValue);
      } else {
        await setTechnicalResponsible(modal.sampleId, modalValue.trim());
      }

      await loadSamples();
      closeModal();
    } catch (err) {
      setActionError(
        err instanceof Error
          ? err.message
          : modal.type === "conclude"
            ? "Erro ao concluir amostra."
            : "Erro ao definir responsável técnico."
      );
    } finally {
      setModalSubmitting(false);
    }
  }

  return (
    <main className="min-h-screen bg-[#080e1a] p-6 text-white">
      <div className="mx-auto max-w-6xl">
        <div className="mb-6 flex items-center justify-between">
          <h1 className="text-2xl font-semibold text-white">Amostras</h1>
          <Link
            href="/samples/createSample"
            className="rounded-md bg-[#3b7ef8] px-4 py-2 text-sm font-medium text-white hover:bg-[#2563eb]"
          >
            Nova amostra
          </Link>
        </div>

        <div className="mb-4 flex gap-3">
          <select
            value={statusFilter}
            onChange={(e) => setStatusFilter(e.target.value as SampleStatus | "")}
            className="rounded-full border border-[#243044] bg-[#0d1525] px-4 py-2 text-sm text-[#7a90a8] focus:border-[#3b7ef8] focus:outline-none"
          >
            <option value="">Todos os status</option>
            {STATUS_OPTIONS.map((status) => (
              <option key={status} value={status}>
                {STATUS_LABELS[status]}
              </option>
            ))}
          </select>

          <select
            value={typeFilter}
            onChange={(e) => setTypeFilter(e.target.value as SampleType | "")}
            className="rounded-full border border-[#243044] bg-[#0d1525] px-4 py-2 text-sm text-[#7a90a8] focus:border-[#3b7ef8] focus:outline-none"
          >
            <option value="">Todos os tipos</option>
            {TYPE_OPTIONS.map((type) => (
              <option key={type} value={type}>
                {type}
              </option>
            ))}
          </select>
        </div>

        {actionError && (
          <div className="mb-4 rounded-md border border-red-700/40 bg-red-900/20 px-4 py-2 text-sm text-red-300">
            {actionError}
          </div>
        )}

        {loading && <p className="text-sm text-[#7a90a8]">Carregando...</p>}

        {error && (
          <div className="rounded-md border border-red-700/40 bg-red-900/20 px-4 py-2 text-sm text-red-300">
            {error}
          </div>
        )}

        {!loading && !error && samples.length === 0 && (
          <p className="text-sm text-[#7a90a8]">Nenhuma amostra encontrada.</p>
        )}

        {!loading && !error && samples.length > 0 && (
          <div className="overflow-hidden rounded-lg border border-[#1a2740] bg-[#0d1525]">
            <table className="w-full border-collapse text-sm">
              <thead>
                <tr className="border-b border-[#1a2740] text-left">
                  <th className="px-4 py-3 font-medium uppercase tracking-wide text-[#7a90a8]">Código</th>
                  <th className="px-4 py-3 font-medium uppercase tracking-wide text-[#7a90a8]">Tipo</th>
                  <th className="px-4 py-3 font-medium uppercase tracking-wide text-[#7a90a8]">Status</th>
                  <th className="px-4 py-3 font-medium uppercase tracking-wide text-[#7a90a8]">Responsável</th>
                  <th className="px-4 py-3 font-medium uppercase tracking-wide text-[#7a90a8]">Recebida em</th>
                  <th className="px-4 py-3 font-medium uppercase tracking-wide text-[#7a90a8]">Concluída em</th>
                  <th className="px-4 py-3 font-medium uppercase tracking-wide text-[#7a90a8]">Ações</th>
                </tr>
              </thead>
              <tbody>
                {samples.map((sample) => {
                  const isFinal = sample.status === "Concluida" || sample.status === "Rejeitada";

                  return (
                    <tr
                      key={sample.id}
                      className="border-b border-[#1a2740] last:border-b-0 hover:bg-[#0f1929]"
                    >
                      <td className="px-4 py-3 font-mono text-[#94b4d4]">{sample.code}</td>
                      <td className="px-4 py-3 text-white">{sample.type}</td>
                      <td className="px-4 py-3">
                        <StatusBadge status={sample.status} />
                      </td>
                      <td className="px-4 py-3 text-white">
                        {sample.technicalResponsible ?? <span className="text-[#4a6080]">—</span>}
                        {!sample.technicalResponsible && !isFinal && (
                          <button
                            onClick={() => openResponsibleModal(sample.id)}
                            className="ml-2 text-xs text-[#3b7ef8] hover:underline"
                          >
                            Definir
                          </button>
                        )}
                      </td>
                      <td className="px-4 py-3 text-[#94b4d4]">{sample.receivedAt.slice(0, 10)}</td>
                      <td className="px-4 py-3 text-[#94b4d4]">
                        {sample.concludedAt ? (
                          sample.concludedAt.slice(0, 10)
                        ) : (
                          <span className="text-[#4a6080]">—</span>
                        )}
                      </td>
                      <td className="px-4 py-3">
                        <div className="flex gap-2">
                          {sample.status === "Recebida" && (
                            <button
                              onClick={() => handleAction(sample.id, "start_analysis")}
                              className="rounded-md border border-[#3b7ef8]/50 bg-[#3b7ef8]/10 px-2.5 py-1 text-xs font-medium text-[#3b7ef8] hover:bg-[#3b7ef8]/20"
                            >
                              Iniciar análise
                            </button>
                          )}
                          {sample.status === "EmAnalise" && (
                            <button
                              onClick={() => openConcludeModal(sample.id)}
                              className="rounded-md border border-emerald-600/50 bg-emerald-900/20 px-2.5 py-1 text-xs font-medium text-emerald-400 hover:bg-emerald-900/40"
                            >
                              Concluir
                            </button>
                          )}
                          {(sample.status === "Recebida" || sample.status === "EmAnalise") && (
                            <button
                              onClick={() => handleAction(sample.id, "reject")}
                              className="rounded-md border border-red-700/50 bg-red-900/20 px-2.5 py-1 text-xs font-medium text-red-400 hover:bg-red-900/40"
                            >
                              Rejeitar
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
            <div className="border-t border-[#1a2740] px-4 py-3 text-xs text-[#7a90a8]">
              {samples.length} {samples.length === 1 ? "amostra encontrada" : "amostras encontradas"}
            </div>
          </div>
        )}
      </div>

      {modal && (
        <div className="fixed inset-0 flex items-center justify-center bg-black/60 p-4">
          <div className="w-full max-w-sm rounded-lg border border-[#1a2740] bg-[#0d1525] p-6 shadow-lg">
            <h2 className="mb-4 text-lg font-semibold text-white">
              {modal.type === "conclude" ? "Data de conclusão" : "Responsável técnico"}
            </h2>

            <input
              type={modal.type === "conclude" ? "date" : "text"}
              value={modalValue}
              onChange={(e) => setModalValue(e.target.value)}
              placeholder={modal.type === "responsible" ? "Nome do responsável" : undefined}
              autoFocus
              className="mb-4 w-full rounded-md border border-[#243044] bg-[#131e30] px-3 py-2 text-sm text-white placeholder-[#4a6080] focus:border-[#3b7ef8] focus:outline-none"
            />

            <div className="flex justify-end gap-2">
              <button
                onClick={closeModal}
                className="rounded-md px-4 py-2 text-sm text-[#7a90a8] hover:bg-[#131e30]"
              >
                Cancelar
              </button>
              <button
                onClick={handleModalConfirm}
                disabled={modalSubmitting || !modalValue.trim()}
                className="rounded-md bg-[#3b7ef8] px-4 py-2 text-sm font-medium text-white hover:bg-[#2563eb] disabled:opacity-50"
              >
                {modalSubmitting ? "Salvando..." : "Confirmar"}
              </button>
            </div>
          </div>
        </div>
      )}
    </main>
  );
}