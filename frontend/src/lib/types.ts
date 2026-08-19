export type SampleType = "Água" | "Solo" | "Ar" | "Efluente";

export type SampleStatus = "Recebida" | "EmAnalise" | "Concluida" | "Rejeitada";

export type SampleStatusAction = "start_analysis" | "conclude" | "reject";

export interface Sample {
  id: string;
  code: string;
  type: SampleType;
  status: SampleStatus;
  technicalResponsible: string | null;
  receivedAt: string;
  concludedAt: string | null;
}

export interface ApiError {
  error: string;
}