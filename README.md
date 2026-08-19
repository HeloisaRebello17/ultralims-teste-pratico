# UltraLIMS — Teste Prático (Desenvolvedor(a) Estagiário(a))

Mini-módulo de Gestão de Amostras, desenvolvido como teste técnico para a vaga de Desenvolvedor(a) Estagiário(a) na Ultralims.

**Candidata:** Heloísa Rebello Cabral

---

## Sobre o projeto

A aplicação permite cadastrar, listar (com filtro por status e tipo), consultar e atualizar o status de amostras laboratoriais, respeitando as regras de negócio de transição de status definidas no enunciado.

- **Backend:** PHP 8.3 + Slim Framework, organizado em Clean Architecture (Domain, Application, Infrastructure), com persistência em MySQL.
- **Frontend:** Next.js (App Router) + TypeScript + Tailwind CSS.
- **Testes:** PHPUnit (backend) e Jest (frontend).
- **Infraestrutura:** Docker Compose, orquestrando backend, frontend e banco de dados.

---

## Como rodar o projeto localmente

### Pré-requisitos

- [Docker](https://www.docker.com/) e Docker Compose instalados

Não é necessário ter PHP, Node ou MySQL instalados na máquina, tudo roda em containers.

### Subindo o projeto

Na raiz do repositório:

```bash
docker compose up --build
```

Para rodar em segundo plano (sem travar o terminal com os logs):

```bash
docker compose up -d --build
```

Isso sobe três containers:

| Serviço | Porta local | Descrição |
|---|---|---|
| `ultralims-frontend` | `3000` | Aplicação Next.js |
| `ultralims-backend` | `8081` | API PHP/Slim |
| `ultralims-db` | `3307` | MySQL 8.0 |

Na primeira inicialização, o banco de dados já é criado automaticamente com o schema necessário (tabela `samples`).
*O build é necessário somente na primeira inicialização.

### Acessando

- **Frontend:** [http://localhost:3000](http://localhost:3000) (redireciona automaticamente para `/samples`)
- **API:** [http://localhost:8081/samples](http://localhost:8081/samples)

### Parando o projeto

```bash
docker compose down
```

Para apagar também os dados do banco (reset completo):

```bash
docker compose down -v
```

### Rodando frontend ou backend fora do Docker (opcional)

Caso prefira rodar o frontend localmente (fora do container) durante o desenvolvimento:

```bash
docker compose stop frontend   # evita conflito de porta 3000
cd frontend
cp .env.example .env.local     # ajuste a URL da API se necessário
npm install
npm run dev
```

---

## Como executar os testes automatizados

### Backend (PHPUnit)

Cobre as regras de negócio 2, 3, 4 e 5 (seção 2.2 do enunciado) na entidade `Sample`, além dos casos de uso da camada Application (com um repositório fake em memória, sem depender do banco).

Com os containers rodando:

```bash
docker compose exec backend vendor/bin/phpunit tests/
```

Ou, se preferir rodar localmente (com PHP 8.3+ e Composer instalados):

```bash
cd backend
composer install
vendor/bin/phpunit tests/
```

**Resultado esperado:** 34 testes, todos passando.

### Frontend (Jest)

Cobre o cliente HTTP (`src/lib/api.ts`): construção de query string nos filtros, payload correto em cada requisição e propagação de mensagens de erro de negócio vindas da API.

```bash
cd frontend
npm install
npm test
```

**Resultado esperado:** 8 testes, todos passando.

---

## Endpoints da API

| Método | Rota | Descrição |
|---|---|---|
| `POST` | `/samples` | Cadastra uma nova amostra |
| `GET` | `/samples` | Lista amostras (filtros opcionais: `?status=` e `?type=`) |
| `GET` | `/samples/{id}` | Consulta uma amostra específica |
| `PATCH` | `/samples/{id}/status` | Atualiza o status (`action`: `start_analysis`, `conclude` ou `reject`) |
| `PATCH` | `/samples/{id}/technical-responsible` | Define/atualiza o responsável técnico |

Valores aceitos para `type`: `Água`, `Solo`, `Ar`, `Efluente`.
Valores possíveis para `status`: `Recebida`, `EmAnalise`, `Concluida`, `Rejeitada`.

---

## Decisões técnicas

### Arquitetura

O backend segue Clean Architecture com três camadas bem isoladas:

- **Domain** (`src/Domain`): a entidade `Sample` concentra toda a regra de negócio (transições de status, validações). Nenhuma outra camada duplica essas regras, o caso de uso `UpdateSampleStatus`, por exemplo, apenas localiza a amostra e delega a transição para o método correspondente na entidade.
- **Application** (`src/Application`): casos de uso (`CreateSample`, `ListSamples`, `GetSample`, `UpdateSampleStatus`, `SetSampleTechnicalResponsible`) que dependem apenas da interface `SampleRepositoryInterface`, sem conhecer MySQL ou HTTP.
- **Infrastructure** (`src/Infrastructure`): implementação real do repositório (`MySqlSampleRepository`, via PDO), controller HTTP e bootstrap da aplicação (injeção de dependência manual, sem container de terceiros, suficiente para o escopo do projeto).

### Nomenclatura: código em inglês, comentários em português

Optei por escrever classes, métodos e variáveis em inglês (`Sample`, `startAnalysis()`, `SampleStatus`), mas manter os comentários explicativos e as mensagens de erro voltadas ao usuário em português, já que o domínio de negócio (laboratório, amostras) e o time da Ultralims são de língua portuguesa. Os *valores* dos enums de status seguem exatamente o vocabulário do enunciado (`Recebida`, `EmAnalise`, `Concluida`, `Rejeitada`), inclusive sem acentuação, para bater com a especificação da seção 2.1.

### Named constructor para restauração de entidade

A entidade `Sample` tem duas formas de ser instanciada: `new Sample(...)`, usada para **criar** uma amostra nova (sempre parte do status `Recebida`, conforme a regra 1), e `Sample::restore(...)`, um named constructor usado apenas pelo repositório para **reconstruir** uma amostra já existente a partir de uma linha do banco, preservando o status e a data de conclusão originais. Essa separação evita que o construtor principal precise de um parâmetro extra "para uso interno apenas", mantendo sua assinatura simples para quem cria amostras novas.

### Geração do código da amostra

O código (`{PREFIXO}-{ANO}-{SEQUENCIAL}`, seção 2.3) é montado no caso de uso `CreateSample`, que pergunta ao repositório qual o próximo sequencial disponível para aquele prefixo e ano (`nextSequentialForYear`). O sequencial reinicia a cada ano, conforme especificado, validado tanto em teste automatizado quanto manualmente.

### CORS

Como o frontend (porta 3000) e o backend (porta 8081) rodam em origens diferentes, foi adicionado um middleware CORS simples no `public/index.php`, liberando as requisições do navegador para a API.

### Campo "responsável técnico" definido após a criação

O enunciado torna `responsavel_tecnico` opcional na criação, mas obrigatório para iniciar a análise (regra 2). Para que uma amostra cadastrada sem responsável não ficasse presa permanentemente no status `Recebida`, foi adicionado um caso de uso e endpoint extra (`SetSampleTechnicalResponsible`, `PATCH /samples/{id}/technical-responsible`) que permite defini-lo posteriormente, refletido na interface como um link "Definir" ao lado do campo vazio na listagem.

### Testes da camada Application com repositório fake

Além dos 14 testes de regra de negócio na entidade (exigência mínima do enunciado), a camada Application também é coberta por 19 testes usando um repositório fake em memória (`SampleRepositoryFake`), permitindo validar a orquestração dos casos de uso, incluindo geração de código e filtros de listagem, sem depender de um banco de dados real.

---

## Estrutura de pastas

```
ultralims-teste-pratico/
├── docker-compose.yml
├── docker/
│   ├── Dockerfile              # imagem do backend
│   ├── Dockerfile.frontend     # imagem do frontend
│   └── sql/
│       └── schema.sql
├── backend/
│   ├── public/
│   │   └── index.php           # ponto de entrada da API
│   ├── src/
│   │   ├── Domain/
│   │   ├── Application/
│   │   └── Infrastructure/
│   └── tests/
└── frontend/
    └── src/
        ├── app/
        │   └── samples/
        │       ├── page.tsx         # listagem, filtros, ações
        │       └── createSample/
        │           └── page.tsx     # formulário de cadastro
        └── lib/
            ├── api.ts                # cliente HTTP
            └── types.ts
```