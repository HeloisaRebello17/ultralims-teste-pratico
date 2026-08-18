import { ApiError, Sample, SampleStatus, SampleStatusAction, SampleType } from "./types";

const API_URL = process.env.NEXT_PUBLIC_API_URL ?? "http://localhost:8081";

async function handleResponse<T>(response: Response): Promise<T> {
  const data = await response.json();

  if (!response.ok) {
    const apiError = data as ApiError;
    throw new Error(apiError.error ?? "Erro inesperado ao comunicar com a API.");
  }

  return data as T;
}

export interface ListSamplesFilters {
  status?: SampleStatus;
  type?: SampleType;
}

export async function listSamples(filters: ListSamplesFilters = {}): Promise<Sample[]> {
  const params = new URLSearchParams();
  if (filters.status) params.set("status", filters.status);
  if (filters.type) params.set("type", filters.type);

  const query = params.toString();
  const response = await fetch(`${API_URL}/samples${query ? `?${query}` : ""}`, {
    cache: "no-store",
  });

  return handleResponse<Sample[]>(response);
}

export async function getSample(id: string): Promise<Sample> {
  const response = await fetch(`${API_URL}/samples/${id}`, { cache: "no-store" });

  return handleResponse<Sample>(response);
}

export interface CreateSamplePayload {
  type: SampleType;
  receivedAt: string;
  technicalResponsible?: string;
}

export async function createSample(payload: CreateSamplePayload): Promise<Sample> {
  const response = await fetch(`${API_URL}/samples`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(payload),
  });

  return handleResponse<Sample>(response);
}

export async function updateSampleStatus(
  id: string,
  action: SampleStatusAction,
  concludedAt?: string
): Promise<Sample> {
  const response = await fetch(`${API_URL}/samples/${id}/status`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ action, concludedAt }),
  });

  return handleResponse<Sample>(response);
}

export async function setTechnicalResponsible(id: string, technicalResponsible: string): Promise<Sample> {
  const response = await fetch(`${API_URL}/samples/${id}/technical-responsible`, {
    method: "PATCH",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ technicalResponsible }),
  });

  return handleResponse<Sample>(response);
}