import { createSample, listSamples, setTechnicalResponsible, updateSampleStatus } from "./api";

function mockResponse(body: unknown, ok: boolean): Response {
  return {
    ok,
    json: async () => body,
  } as Response;
}

describe("api client", () => {
  beforeEach(() => {
    global.fetch = jest.fn();
  });

  afterEach(() => {
    jest.resetAllMocks();
  });

  describe("listSamples", () => {
    it("chama a API sem query string quando nenhum filtro é informado", async () => {
      (global.fetch as jest.Mock).mockResolvedValue(mockResponse([], true));

      await listSamples();

      const calledUrl = (global.fetch as jest.Mock).mock.calls[0][0] as string;
      expect(calledUrl).toMatch(/\/samples$/);
    });

    it("inclui status e type na query string quando filtros são informados", async () => {
      (global.fetch as jest.Mock).mockResolvedValue(mockResponse([], true));

      await listSamples({ status: "Recebida", type: "Água" });

      const calledUrl = (global.fetch as jest.Mock).mock.calls[0][0] as string;
      expect(calledUrl).toContain("status=Recebida");
      expect(calledUrl).toContain("type=" + encodeURIComponent("Água"));
    });

    it("retorna a lista de amostras decodificada do JSON", async () => {
      const samples = [{ id: "1", code: "CAND047-2026-0001" }];
      (global.fetch as jest.Mock).mockResolvedValue(mockResponse(samples, true));

      const result = await listSamples();

      expect(result).toEqual(samples);
    });
  });

  describe("createSample", () => {
    it("envia POST com o payload correto", async () => {
      (global.fetch as jest.Mock).mockResolvedValue(mockResponse({ id: "1" }, true));

      await createSample({ type: "Água", receivedAt: "2026-01-10" });

      const [url, options] = (global.fetch as jest.Mock).mock.calls[0];
      expect(url).toMatch(/\/samples$/);
      expect(options.method).toBe("POST");
      expect(JSON.parse(options.body)).toEqual({ type: "Água", receivedAt: "2026-01-10" });
    });

    it("lança um erro com a mensagem da API quando a resposta não é OK", async () => {
      (global.fetch as jest.Mock).mockResolvedValue(
        mockResponse({ error: "Valor inválido para o tipo da amostra." }, false)
      );

      await expect(createSample({ type: "Água", receivedAt: "2026-01-10" })).rejects.toThrow(
        "Valor inválido para o tipo da amostra."
      );
    });
  });

  describe("updateSampleStatus", () => {
    it("envia PATCH com action e concludedAt no corpo", async () => {
      (global.fetch as jest.Mock).mockResolvedValue(mockResponse({ id: "1" }, true));

      await updateSampleStatus("1", "conclude", "2026-01-20");

      const [url, options] = (global.fetch as jest.Mock).mock.calls[0];
      expect(url).toMatch(/\/samples\/1\/status$/);
      expect(options.method).toBe("PATCH");
      expect(JSON.parse(options.body)).toEqual({ action: "conclude", concludedAt: "2026-01-20" });
    });

    it("propaga o erro de regra de negócio retornado pela API", async () => {
      (global.fetch as jest.Mock).mockResolvedValue(
        mockResponse({ error: "Não é possível iniciar a análise sem um responsável técnico definido." }, false)
      );

      await expect(updateSampleStatus("1", "start_analysis")).rejects.toThrow(
        "Não é possível iniciar a análise sem um responsável técnico definido."
      );
    });
  });

  describe("setTechnicalResponsible", () => {
    it("envia PATCH para a rota de responsável técnico", async () => {
      (global.fetch as jest.Mock).mockResolvedValue(mockResponse({ id: "1" }, true));

      await setTechnicalResponsible("1", "Fulano da Silva");

      const [url, options] = (global.fetch as jest.Mock).mock.calls[0];
      expect(url).toMatch(/\/samples\/1\/technical-responsible$/);
      expect(JSON.parse(options.body)).toEqual({ technicalResponsible: "Fulano da Silva" });
    });
  });
});