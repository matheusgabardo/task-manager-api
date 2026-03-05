# PRD — Task Manager App

## 1. Visão Geral do Projeto

### Contexto

`task-manager-app` é uma mini-aplicação de gestão de tarefas por projeto.

### Objetivo da Aplicação

Permitir que utilizadores organizem trabalho em **Projetos**, cada um contendo **Tarefas** com status, prioridade e data de entrega. A aplicação expõe uma API RESTful consumida por um SPA Vue 3.

### Âmbito (Scope)

| Incluído | Excluído |
|---|---|
| CRUD de projetos e tarefas | Múltiplos utilizadores / multi-tenant |
| Filtros por status e prioridade | Deploy em produção |
| Drag-and-drop para mudar status | Permissões granulares (roles) |
| Testes (PHPUnit + Vitest) | Upload de ficheiros |

### Stack Tecnológica

| Camada | Tecnologia |
|---|---|
| Backend | PHP 8.4, Laravel 12 |
| Base de dados | MySQL 8+ |
| Frontend | Vue 3 (Composition API), TypeScript |
| Estilos | Tailwind CSS 4 |
| Estado | Pinia |
| Routing | Vue Router 4 |
| Testes Backend | PHPUnit (Feature Tests) |
| Testes Frontend | Vitest |
| Ambiente Local | php artisan serve + npm run dev |

---

## 2. Arquitetura Técnica

### 2.1 Visão Geral da Arquitetura

```
┌─────────────────────────────────────────────┐
│                 Vue 3 SPA                   │
│  Vue Router │ Pinia │ Composables │ TypeScript│
└────────────────────┬────────────────────────┘
                     │ HTTP (JSON API)
┌────────────────────▼────────────────────────┐
│              Laravel 12 API                 │
│  Routes → Middleware → Controller           │
│  FormRequest → Resource → Model            │
└────────────────────┬────────────────────────┘
                     │ Eloquent ORM
┌────────────────────▼────────────────────────┐
│               MySQL 8+                      │
│            projects │ tasks                 │
└─────────────────────────────────────────────┘
```

### 2.2 Backend — Laravel 12

#### Fluxo de uma Request

```
Request → FormRequest (validação) → Controller → Action → Model → Resource → Response
```

O **Controller** não contém lógica de negócio — recebe o request e delega à Action correta.

A **Action** executa uma única operação atómica. É reutilizável e testável de forma isolada.

#### Estrutura de Diretórios Relevante

```
app/
├── Actions/
│   ├── Projects/
│   │   └── CreateProjectAction.php
│   └── Tasks/
│       ├── CreateTaskAction.php
│       ├── UpdateTaskAction.php
│       └── DeleteTaskAction.php
├── Http/
│   ├── Controllers/
│   │   ├── ProjectController.php
│   │   └── TaskController.php
│   ├── Requests/
│   │   ├── StoreProjectRequest.php
│   │   ├── StoreTaskRequest.php
│   │   └── UpdateTaskRequest.php
│   └── Resources/
│       ├── ProjectResource.php
│       ├── ProjectCollection.php
│       ├── TaskResource.php
│       └── TaskCollection.php
├── Models/
│   ├── Project.php
│   └── Task.php
database/
├── migrations/
├── seeders/
│   ├── DatabaseSeeder.php
│   ├── ProjectSeeder.php
│   └── TaskSeeder.php
tests/
└── Feature/
    ├── ProjectTest.php
    └── TaskTest.php
```

#### Exemplo de uso — fluxo completo

```php
// TaskController.php — delega diretamente à Action
public function store(StoreTaskRequest $request, Project $project)
{
    $task = (new CreateTaskAction)->handle($project, $request->validated());
    return new TaskResource($task);
}

// CreateTaskAction.php — só sabe criar uma tarefa
class CreateTaskAction
{
    public function handle(Project $project, array $data): Task
    {
        return $project->tasks()->create($data);
    }
}
```

#### Modelos e Base de Dados

**Tabela `projects`**

| Campo | Tipo | Detalhes |
|---|---|---|
| id | BIGINT UNSIGNED | PK, auto-increment |
| name | VARCHAR(255) | NOT NULL |
| description | TEXT | NULLABLE |
| status | ENUM('active','archived') | DEFAULT 'active' |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

**Tabela `tasks`**

| Campo | Tipo | Detalhes |
|---|---|---|
| id | BIGINT UNSIGNED | PK, auto-increment |
| project_id | BIGINT UNSIGNED | FK → projects.id |
| title | VARCHAR(255) | NOT NULL |
| description | TEXT | NULLABLE |
| status | ENUM('todo','in_progress','done') | DEFAULT 'todo' |
| priority | ENUM('low','medium','high') | DEFAULT 'medium' |
| due_date | DATE | NULLABLE |
| deleted_at | TIMESTAMP | Soft Delete |
| created_at | TIMESTAMP | |
| updated_at | TIMESTAMP | |

#### Modelo `Project`

- `hasMany(Task::class)`
- `withCount('tasks')` nos listings
- Cast de `status` para enum nativo PHP 8.1+

#### Modelo `Task`

- `belongsTo(Project::class)`
- `SoftDeletes` trait
- **Scope `scopeOverdue()`**: filtra tarefas onde `due_date < today()` e `status != 'done'`
- **Scope `scopeFilter()`**: aplica filtros de `status` e `priority` passados via query string

#### Middleware e Segurança

- Rate limiting: `60 requests/minuto` por IP (via `throttle:api`)
- CORS configurado para aceitar requests do frontend (`localhost:5173`)

### 2.3 Frontend — Vue 3 + TypeScript

#### Estrutura de Diretórios

```
src/
├── components/
│   ├── TaskCard.vue          # Componente reutilizável (props tipadas)
│   ├── ProjectCard.vue
│   ├── TaskBoard.vue         # Drag-and-drop (vue-draggable-plus)
│   ├── CreateProjectModal.vue
│   └── CreateTaskModal.vue
├── composables/
│   ├── useProjects.ts
│   └── useTasks.ts
├── stores/
│   ├── projectStore.ts       # Pinia
│   └── taskStore.ts
├── views/
│   ├── ProjectsView.vue      # Lista de projetos
│   └── ProjectDetailView.vue # Detalhe + tarefas
├── router/
│   └── index.ts
├── services/
│   └── api.ts                # Axios instance + interceptors
└── types/
    └── index.ts              # Interfaces TypeScript
```

#### Interfaces TypeScript

```typescript
// types/index.ts

export interface Project {
  id: number
  name: string
  description: string | null
  status: 'active' | 'archived'
  tasks_count: number
  created_at: string
}

export interface Task {
  id: number
  project_id: number
  title: string
  description: string | null
  status: 'todo' | 'in_progress' | 'done'
  priority: 'low' | 'medium' | 'high'
  due_date: string | null
  is_overdue: boolean
  created_at: string
}

export interface TaskFilters {
  status?: Task['status']
  priority?: Task['priority']
}

export interface PaginatedResponse<T> {
  data: T[]
  meta: {
    next_cursor: string | null
    prev_cursor: string | null
  }
}
```

#### Rotas Vue Router

| Path | View | Descrição |
|---|---|---|
| `/` | `ProjectsView` | Lista todos os projetos |
| `/projects/:id` | `ProjectDetailView` | Detalhe do projeto e suas tarefas |

#### Composables

**`useProjects.ts`**
- `fetchProjects()` — carrega lista com `tasks_count`
- `createProject(data)` — POST com optimistic update
- `state`: `projects`, `loading`, `error`

**`useTasks.ts`**
- `fetchTasks(projectId, filters)` — com debounce nos filtros (300ms)
- `createTask(projectId, data)`
- `updateTaskStatus(taskId, status)` — com optimistic update
- `deleteTask(taskId)`
- `state`: `tasks`, `loading`, `error`

#### Drag-and-Drop

Biblioteca: **`vue-draggable-plus`** (wrapper moderno do SortableJS para Vue 3).

- Colunas Kanban: `Todo` | `In Progress` | `Done`
- Ao soltar um card numa coluna diferente → `updateTaskStatus()` com optimistic update
- Em caso de erro na API → reverte para o estado anterior (rollback no store Pinia)

---

## 3. Endpoints da API

> Base URL: `/api`

---

### `GET /api/projects`

Lista todos os projetos com contagem de tarefas.

**Query params:** nenhum

**Response `200`:**
```json
{
  "data": [
    {
      "id": 1,
      "name": "Website Redesign",
      "description": "Redesign completo do site institucional",
      "status": "active",
      "tasks_count": 12,
      "created_at": "2025-01-15T10:00:00Z"
    }
  ],
  "meta": {
    "next_cursor": "eyJpZCI6MTB9",
    "prev_cursor": null
  }
}
```

---

### `POST /api/projects`

Cria um novo projeto.

**Body:**
```json
{
  "name": "Website Redesign",
  "description": "Redesign completo do site institucional",
  "status": "active"
}
```

**Validação (`StoreProjectRequest`):**
- `name`: required, string, max:255
- `description`: nullable, string, max:2000
- `status`: nullable, in:active,archived (default: active)

**Response `201`:**
```json
{
  "data": {
    "id": 1,
    "name": "Website Redesign",
    "description": "Redesign completo do site institucional",
    "status": "active",
    "tasks_count": 0,
    "created_at": "2025-01-15T10:00:00Z"
  }
}
```

---

### `GET /api/projects/{id}/tasks`

Lista tarefas de um projeto, com filtros opcionais e paginação por cursor.

**Query params:**

| Param | Tipo | Exemplo |
|---|---|---|
| `status` | string | `?status=todo` |
| `priority` | string | `?priority=high` |
| `cursor` | string | `?cursor=eyJpZCI6MTB9` |

**Response `200`:**
```json
{
  "data": [
    {
      "id": 1,
      "project_id": 1,
      "title": "Criar wireframes",
      "description": "Wireframes de todas as páginas principais",
      "status": "in_progress",
      "priority": "high",
      "due_date": "2025-02-01",
      "is_overdue": false,
      "created_at": "2025-01-15T10:00:00Z"
    }
  ],
  "meta": {
    "next_cursor": "eyJpZCI6MjB9",
    "prev_cursor": null
  }
}
```

**Response `404`** — projeto não encontrado.

---

### `POST /api/projects/{id}/tasks`

Cria uma tarefa num projeto.

**Body:**
```json
{
  "title": "Criar wireframes",
  "description": "Wireframes de todas as páginas principais",
  "status": "todo",
  "priority": "high",
  "due_date": "2025-02-01"
}
```

**Validação (`StoreTaskRequest`):**
- `title`: required, string, max:255
- `description`: nullable, string, max:5000
- `status`: nullable, in:todo,in_progress,done (default: todo)
- `priority`: nullable, in:low,medium,high (default: medium)
- `due_date`: nullable, date, after_or_equal:today

**Response `201`:** TaskResource completo.

---

### `PATCH /api/tasks/{id}`

Atualiza status e/ou prioridade de uma tarefa.

**Body (todos opcionais, pelo menos um obrigatório):**
```json
{
  "status": "in_progress",
  "priority": "medium"
}
```

**Validação (`UpdateTaskRequest`):**
- `status`: sometimes, in:todo,in_progress,done
- `priority`: sometimes, in:low,medium,high
- Regra custom: pelo menos um dos dois campos deve estar presente

**Response `200`:** TaskResource atualizado.

---

### `DELETE /api/tasks/{id}`

Soft delete de uma tarefa (não remove da base de dados).

**Response `204`:** No content.

**Nota:** Tarefas com soft delete podem ser restauradas futuramente. O campo `deleted_at` é preenchido automaticamente pelo Eloquent.

---

## 4. Decisões Técnicas e Trade-offs

### 4.1 Actions em vez de lógica no Controller (sem Services)

**Decisão:** Camada `app/Actions/` com uma classe por operação. Services foram omitidos deliberadamente.

**Responsabilidades:**
- **Controller** — recebe o request HTTP, delega à Action, devolve o Resource. Não conhece regras de negócio.
- **Action** (`CreateTaskAction`, `UpdateTaskAction`, etc.) — executa uma única operação atómica. Reutilizável em qualquer contexto (Controller, Job, comando Artisan, teste).

**Razão para omitir Services:** As operações deste projeto são atómicas — criar uma tarefa é só criar uma tarefa, sem orquestração de múltiplos passos ou modelos. Um Service seria um wrapper vazio que apenas delegaria para a Action, adicionando complexidade sem valor real. Services seriam introduzidos quando surgisse lógica de negócio que justificasse orquestração (ex: arquivar projeto + cancelar tarefas + notificar membros).

**Razão para usar Actions:** Controllers gordos são o problema mais comum em Laravel. Com Actions, cada operação tem uma única responsabilidade (SRP), é testável de forma isolada e reutilizável sem depender do ciclo HTTP.

---

### 4.2 Cursor Pagination em vez de Offset

**Decisão:** `Task::cursorPaginate()` do Laravel.

**Razão:** Tecnicamente superior ao offset para conjuntos de dados que crescem. O offset tem o problema do "skipping" — se um registo é inserido enquanto o utilizador pagina, registos são saltados. O cursor é determinístico.

**Trade-off:** Não permite saltar para uma página específica (ex: "ir para página 5"). Para tarefas de projeto, este cenário não é relevante.

---

### 4.3 `vue-draggable-plus` para Drag-and-Drop

**Decisão:** `vue-draggable-plus` em vez de `vuedraggable` (legado) ou implementação nativa HTML5.

**Razão:** `vuedraggable` (v4) tem problemas conhecidos com Vue 3. `vue-draggable-plus` é o fork moderno mantido ativamente, com suporte nativo a Composition API e TypeScript.

**Trade-off:** Biblioteca extra (~10kb gzip). Alternativa seria usar a API nativa de Drag & Drop do browser, mas a experiência seria inferior (sem animações smooth).

---

### 4.4 Optimistic Updates no Status das Tarefas

**Decisão:** Ao mover um card no board, o estado Pinia é atualizado imediatamente — o request à API acontece em background. Em caso de erro, o estado é revertido.

**Razão:** Elimina o lag percetível entre a ação do utilizador e a atualização visual. A latência de rede tornaria o drag-and-drop "pesado" sem esta abordagem.

**Trade-off:** Estado temporariamente inconsistente entre cliente e servidor durante o request. Mitigado pelo rollback em caso de erro e feedback visual (toast de erro).

---

### 4.5 TypeScript no Frontend

**Decisão:** TypeScript em vez de JSDoc.

**Razão:** TypeScript oferece melhor DX (autocompletion, type checking em tempo de compilação), é a escolha padrão em projetos Vue 3 modernos, e demonstra melhor competência técnica num teste.

**Trade-off:** Configuração inicial mais complexa (tsconfig, tipos para bibliotecas). Mitigado pelo `create-vue` que gera tudo automaticamente.

---

### 4.6 Soft Deletes nas Tarefas

**Decisão:** `SoftDeletes` trait do Eloquent nas tarefas.

**Razão:** Requisito obrigatório do teste. Permite auditoria e possível restauro. O campo `deleted_at` é preenchido em vez de o registo ser removido.

**Implicação:** Os scopes `overdue()` e `filter()` devem excluir registos com `deleted_at IS NOT NULL` automaticamente (Eloquent faz isto por default com o trait ativo).

---

### 4.7 API Resources para Formatação de Respostas

**Decisão:** Usar `JsonResource` e `ResourceCollection` em vez de retornar modelos diretamente.

**Razão:** Controlo explícito sobre quais campos são expostos. Sem Resources, um `return $task` expõe todos os campos incluindo timestamps internos e campos sensíveis. Resources também facilitam adicionar campos computados como `is_overdue`.

---

### 4.8 Debounce nos Filtros de Tarefas

**Decisão:** Debounce de **300ms** nos inputs de filtro (status/prioridade).

**Razão:** Evita um request por cada keystroke. Com debounce, a chamada à API só acontece 300ms após o utilizador parar de interagir. Para dropdowns (não texto livre), o debounce é menos crítico mas mantém o padrão consistente.

**Implementação:** `watchDebounced` do `@vueuse/core` ou implementação manual com `setTimeout`.

---

## 5. Plano de Implementação / Fases

> Estimativa pensada para desenvolvimento sequencial e validação incremental.

---

### Fase 1 — Setup e Fundações

**Backend:**
- [ ] Criar projeto Laravel 12 com `composer create-project`
- [ ] Configurar `.env` e `.env.example`
- [ ] Criar migrations (`projects`, `tasks`)
- [ ] Criar modelos com relações, casts e scopes
- [ ] Criar Seeders com dados realistas (Faker)

**Frontend:**
- [ ] Criar projeto Vue 3 + TypeScript + Tailwind 4 com `create-vue`
- [ ] Configurar Vue Router (2 rotas)
- [ ] Configurar Pinia
- [ ] Criar instância Axios com interceptors de erro

**Critério de conclusão:** `php artisan migrate --seed` sem erros. `npm run dev` arranca.

---

### Fase 2 — API Backend

- [ ] `ProjectController`: `index`, `store`
- [ ] `TaskController`: `index` (com filtros + cursor pagination), `store`, `update`, `destroy`
- [ ] `FormRequests`: `StoreProjectRequest`, `StoreTaskRequest`, `UpdateTaskRequest`
- [ ] `API Resources`: `ProjectResource`, `TaskResource` (com `is_overdue` calculado)
- [ ] Rate limiting configurado (`throttle:api`)

**Critério de conclusão:** Todos os endpoints testáveis via Postman/Insomnia.

---

### Fase 3 — Testes Backend (PHPUnit)

- [ ] `ProjectTest`: listar projetos, criar projeto válido, criar projeto inválido (validação)
- [ ] `TaskTest`: listar tarefas com filtros, criar tarefa, atualizar status, soft delete
- [ ] Mínimo 3 endpoints cobertos (requisito)
- [ ] Usar `RefreshDatabase` trait

**Critério de conclusão:** `php artisan test` passa todos os testes.

---

### Fase 4 — Frontend: Lista de Projetos

- [ ] `useProjects.ts` composable
- [ ] `projectStore.ts` (Pinia)
- [ ] `ProjectsView.vue` com cards
- [ ] `ProjectCard.vue` componente
- [ ] `CreateProjectModal.vue` com formulário
- [ ] Loading states, erros e feedback de sucesso (toasts)
- [ ] Layout responsivo (mobile-first)

**Critério de conclusão:** Consegue listar e criar projetos com feedback visual.

---

### Fase 5 — Frontend: Detalhe do Projeto + Tarefas

- [ ] `useTasks.ts` composable (com debounce nos filtros)
- [ ] `taskStore.ts` (Pinia)
- [ ] `ProjectDetailView.vue`
- [ ] `TaskCard.vue` com props tipadas em TypeScript
- [ ] `TaskBoard.vue` com colunas Kanban (Todo / In Progress / Done)
- [ ] Drag-and-drop com `vue-draggable-plus`
- [ ] Optimistic updates + rollback em erro
- [ ] Indicação visual de tarefas em atraso (badge/cor)
- [ ] Filtros por status e prioridade (com debounce)
- [ ] `CreateTaskModal.vue`
- [ ] Transições e animações entre estados

**Critério de conclusão:** Consegue gerir tarefas completo com drag-and-drop funcional.

---

### Fase 6 — Testes Frontend (Vitest)

- [ ] Testes para `useProjects.ts` ou `useTasks.ts` (requisito: pelo menos um composable)
- [ ] Mock de chamadas Axios
- [ ] Testar: fetch, create, optimistic update, rollback em erro

**Critério de conclusão:** `npm run test` passa.

---

### Fase 7 — Polimento e README

- [ ] Revisão de UI/UX e responsividade
- [ ] Rever todos os loading states e mensagens de erro
- [ ] Escrever `README.md` com:
  - Instruções de instalação (passo a passo)
  - Decisões técnicas relevantes
  - O que ficou por implementar (e porquê)
- [ ] Rever `.env.example` com todas as variáveis
- [ ] Teste final: clone fresh + `migrate --seed` + `npm run dev`

---

### Resumo das Fases

| Fase | Descrição | Dependências |
|---|---|---|
| 1 | Setup e Fundações | — |
| 2 | API Backend | Fase 1 |
| 3 | Testes Backend | Fase 2 |
| 4 | Frontend: Projetos | Fase 2 |
| 5 | Frontend: Tarefas | Fase 4 |
| 6 | Testes Frontend | Fase 5 |
| 7 | Polimento + README | Todas |

---

## Apêndice — O que pode ficar fora de scope (e porquê)

| Funcionalidade | Motivo para excluir |
|---|---|
| Deploy em produção | Não exigido pelo teste |
| Multi-utilizador / partilha de projetos | Não especificado nos requisitos |
| Restauro de tarefas eliminadas (soft delete) | Soft delete implementado, UI de restauro é extra |
| Filtro por `overdue` na UI | Scope existe no backend, exposição na UI é bonus |
| Testes E2E (Cypress/Playwright) | Não exigido; PHPUnit + Vitest cobrem o requisito |