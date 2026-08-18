"use client";

import { FormEvent, useState } from "react";
import { useRouter } from "next/navigation";
import Link from "next/link";
import { createSample } from "@/lib/api";
import { SampleType } from "@/lib/types";

const TYPE_OPTIONS: SampleType[] = ["Água", "Solo", "Ar", "Efluente"];

export default function NewSamplePage() {
  const router = useRouter();

  const [type, setType] = useState<SampleType | "">("");
  const [receivedAt, setReceivedAt] = useState(new Date().toISOString().slice(0, 10));
  const [technicalResponsible, setTechnicalResponsible] = useState("");

  const [submitting, setSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setError(null);

    if (!type) {
      setError("Selecione o tipo da amostra.");
      return;
    }

    setSubmitting(true);

    try {
      await createSample({
        type,
        receivedAt,
        technicalResponsible: technicalResponsible.trim() || undefined,
      });

      router.push("/samples");
    } catch (err) {
      setError(err instanceof Error ? err.message : "Erro ao cadastrar amostra.");
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <main className="min-h-screen bg-[#080e1a] p-6 text-white">
      <div className="mx-auto max-w-md">
        <div className="mb-6 flex items-center justify-between rounded-lg border border-[#1a2740] bg-[#0d1525] px-6 py-4">
          <h1 className="text-2xl font-semibold text-white">Nova amostra</h1>
          <Link href="/samples" className="text-sm text-[#3b7ef8] hover:underline">
            Voltar
          </Link>
        </div>

        <form
          onSubmit={handleSubmit}
          className="flex flex-col gap-4 rounded-lg border border-[#1a2740] bg-[#0d1525] p-6"
        >
          <div>
            <label className="mb-1 block text-sm font-medium text-[#7a90a8]">Tipo *</label>
            <select
              value={type}
              onChange={(e) => setType(e.target.value as SampleType)}
              required
              className="w-full rounded-md border border-[#243044] bg-[#131e30] px-3 py-2 text-sm text-white focus:border-[#3b7ef8] focus:outline-none"
            >
              <option value="">Selecione...</option>
              {TYPE_OPTIONS.map((option) => (
                <option key={option} value={option}>
                  {option}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="mb-1 block text-sm font-medium text-[#7a90a8]">Data de recebimento *</label>
            <input
              type="date"
              value={receivedAt}
              onChange={(e) => setReceivedAt(e.target.value)}
              required
              className="w-full rounded-md border border-[#243044] bg-[#131e30] px-3 py-2 text-sm text-white focus:border-[#3b7ef8] focus:outline-none"
            />
          </div>

          <div>
            <label className="mb-1 block text-sm font-medium text-[#7a90a8]">
              Responsável técnico <span className="text-[#4a6080]">(opcional)</span>
            </label>
            <input
              type="text"
              value={technicalResponsible}
              onChange={(e) => setTechnicalResponsible(e.target.value)}
              placeholder="Nome do responsável"
              className="w-full rounded-md border border-[#243044] bg-[#131e30] px-3 py-2 text-sm text-white placeholder-[#4a6080] focus:border-[#3b7ef8] focus:outline-none"
            />
            <p className="mt-1 text-xs text-[#7a90a8]">
              Pode ser preenchido depois, mas é necessário para iniciar a análise.
            </p>
          </div>

          {error && (
            <div className="rounded-md border border-red-700/40 bg-red-900/20 px-4 py-2 text-sm text-red-300">
              {error}
            </div>
          )}

          <button
            type="submit"
            disabled={submitting}
            className="rounded-md bg-[#3b7ef8] px-4 py-2 text-sm font-medium text-white hover:bg-[#2563eb] disabled:opacity-50"
          >
            {submitting ? "Cadastrando..." : "Cadastrar amostra"}
          </button>
        </form>
      </div>
    </main>
  );
}