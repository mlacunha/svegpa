# Proposta de Conversão — SanvegSPA

## Coleta de Dados em Sanidade Vegetal — PWA Offline-First

**Documento:** Proposta de trabalho por etapas  
**Versão:** 1.0  
**Data:** 23/02/2026  

---

## 1. Contexto e Objetivos

### 1.1 Situação Atual
- **MVP funcional** em PHP + MySQL (monolito tradicional)
- **API REST** existente em PHP (JWT, CRUD para principais entidades)
- **Frontend** server-side com Tailwind CSS e JavaScript vanilla
- **15+ módulos CRUD** (programas, propriedades, produtores, termo_inspecao, etc.)
- **Mapa interativo** (Leaflet) no dashboard
- **Sem PWA** — não há manifest, service worker ou suporte offline
- **Persistência** apenas no MySQL (sem IndexedDB/localStorage)

### 1.2 Objetivos da Conversão
| Objetivo | Descrição |
|----------|-----------|
| **SPA moderna** | Single Page Application responsiva e fluida |
| **PWA offline-first** | Uso em campo (zonas rurais) com rede instável |
| **API robusta** | FastAPI para performance e migração futura de banco |
| **Visual moderno** | Layouts semelhantes aos atuais, porém fluidos (Stitches ou similar) |
| **Leve e responsivo** | Desktop, notebook e dispositivos móveis |

---

## 2. Stack Tecnológica Proposta

### 2.1 Backend — FastAPI
| Aspecto | Justificativa |
|---------|---------------|
| **Performance** | ASGI assíncrono, um dos frameworks Python mais rápidos |
| **Migração de banco** | SQLAlchemy 2.0 + alembic facilitam MySQL → PostgreSQL → SQLite |
| **Documentação** | OpenAPI/Swagger automático |
| **Validação** | Pydantic nativo |
| **JWT** | python-jose ou PyJWT bem integrados |

### 2.2 Frontend — React + Vite + Stitches
| Componente | Uso |
|------------|-----|
| **React 18** | UI componente reutilizável |
| **Vite** | Build rápido, HMR, tree-shaking |
| **Stitches** | CSS-in-JS com design tokens, visual fluido e consistente |
| **React Router** | Roteamento SPA |
| **TanStack Query** | Cache, sincronização e estado servidor |
| **Zustand ou Jotai** | Estado global leve |
| **Leaflet** | Mapas (manter) |
| **Workbox** | Service worker e estratégias de cache |

### 2.3 Persistência e Sincronização
| Camada | Tecnologia |
|--------|------------|
| **Servidor** | MySQL (inicial) → suportar PostgreSQL/SQLite futuramente |
| **Cliente offline** | IndexedDB via Dexie.js ou idb |
| **Sync** | Fila de operações + Background Sync API |
| **ORM** | SQLAlchemy 2.0 (backend) |

---

## 3. Proposta de Trabalho por Etapas

### Etapa 1 — API FastAPI (Fundação)
**Objetivo:** Substituir a API PHP por FastAPI mantendo compatibilidade com o frontend existente.

**Entregas:**
1. **Projeto FastAPI** com estrutura modular:
   ```
   api/
   ├── app/
   │   ├── main.py
   │   ├── config.py
   │   ├── database.py
   │   ├── models/          # SQLAlchemy
   │   ├── schemas/         # Pydantic
   │   ├── routers/
   │   │   ├── auth.py
   │   │   ├── programas.py
   │   │   ├── propriedades.py
   │   │   └── ...
   │   └── services/
   └── alembic/            # Migrações
   ```
2. **Endpoints** espelhando a API atual:
   - `POST /api/auth/login` → JWT
   - CRUD para: programas, propriedades, produtores, produtos, hospedeiros, órgaos, cargos, municípios, normas, unidades, termo_inspecao, relatorio_mapa, area_inspecionada, amostra_coletada
3. **Autenticação JWT** compatível com o formato atual
4. **Documentação OpenAPI** em `/docs`
5. **Variáveis de ambiente** para DB (MySQL inicial)
6. **Testes** unitários básicos

**Critério de conclusão:** Todos os endpoints da API PHP atendidos pela FastAPI; frontend PHP pode opcionalmente consumir a nova API em modo paralelo.

**Estimativa:** 2–3 semanas

---

### Etapa 2 — SPA Base (Shell e Navegação)
**Objetivo:** Criar o shell da SPA com layout fluido e navegação.

**Entregas:**
1. **Projeto Vite + React + TypeScript**
2. **Stitches** configurado com:
   - Design tokens (cores, espaçamento, tipografia)
   - Tema claro/escuro opcional
   - Componentes base (Button, Card, Input, etc.)
3. **Layout principal:**
   - Sidebar colapsável (desktop) / drawer (mobile)
   - Header com título dinâmico e perfil do usuário
   - Área de conteúdo fluida
4. **React Router** com rotas base:
   - `/login`
   - `/dashboard`
   - `/programas`, `/propriedades`, etc. (estrutura preparada)
5. **Autenticação cliente:**
   - Armazenar token JWT (httpOnly cookie ou localStorage)
   - HOC ou rotas protegidas
   - Redirecionamento para login quando não autenticado
6. **Integração com API** via fetch/axios + TanStack Query

**Critério de conclusão:** Login, dashboard em branco e navegação lateral funcionando; design consistente com Stitches.

**Estimativa:** 2 semanas

---

### Etapa 3 — Módulos CRUD e Páginas Principais
**Objetivo:** Reproduzir as telas do MVP com layout semelhante e visual moderno.

**Ordem sugerida (prioridade):**
1. **Dashboard** — cards de estatísticas, mapa Leaflet
2. **Catálogos simples** — Programas, Hospedeiros, Normas, Órgãos, Tipos de Órgãos, Unidades, Municípios, Cargos
3. **Levantamentos** — Produtores, Propriedades, Produtos
4. **Termo de Inspeção** — formulário principal
5. **Áreas Inspecionadas** — subformulário do termo
6. **Amostragem** — amostras coletadas
7. **Relatório Mapa** — integração com mapa
8. **Usuários e Config** — admin

**Padrão por módulo:**
- Página de listagem com filtros, busca e paginação
- Formulários create/edit com validação
- Componentes reutilizáveis (Select, Modal, Tabela, etc.)

**Critério de conclusão:** Todas as telas do MVP reproduzidas e funcionais na SPA.

**Estimativa:** 4–6 semanas (em paralelo após base estável)

---

### Etapa 4 — PWA e Offline-First
**Objetivo:** Habilitar uso offline e instalação como app.

**Entregas:**
1. **manifest.json:**
   - Nome, ícones (vários tamanhos)
   - `display: standalone` ou `minimal-ui`
   - `start_url`, `scope`
   - Tema e cores de fundo
2. **Service Worker (Workbox):**
   - Cache de assets estáticos (estratégia CacheFirst)
   - Cache de API para catálogos (stale-while-revalidate)
   - Estratégia NetworkFirst para dados dinâmicos
3. **IndexedDB (Dexie.js):**
   - Schemas para: programas, propriedades, produtores, etc.
   - Sincronização pull (baixar catálogos quando online)
4. **Fila de sincronização:**
   - Operações de escrita (create/update) enfileiradas quando offline
   - Background Sync ou retry manual ao voltar online
   - Indicador visual de “dados pendentes”
5. **Detecção de conectividade** e feedback na UI

**Critério de conclusão:** App instalável, uso básico offline com sincronização funcional.

**Estimativa:** 2–3 semanas

---

### Etapa 5 — Refino e Otimização
**Objetivo:** Performance, acessibilidade e polimento.

**Entregas:**
1. **Performance:**
   - Lazy loading de rotas
   - Otimização de bundles
   - Compressão de imagens e assets
2. **Acessibilidade:**
   - ARIA, foco, contraste
   - Navegação por teclado
3. **i18n:** Textos em português BR (já previsto; conferir consistência)
4. **Testes:**
   - E2E (Playwright ou Cypress) para fluxos críticos
   - Testes de API (pytest)

**Estimativa:** 1–2 semanas

---

## 4. Cronograma Resumido

| Etapa | Descrição | Duração |
|-------|-----------|---------|
| 1 | API FastAPI | 2–3 semanas |
| 2 | SPA Base (Shell, Stitches, Navegação) | 2 semanas |
| 3 | Módulos CRUD e Páginas | 4–6 semanas |
| 4 | PWA e Offline-First | 2–3 semanas |
| 5 | Refino e Otimização | 1–2 semanas |
| **Total** | | **11–16 semanas** |

**Paralelismo:** Etapa 2 pode iniciar em paralelo com Etapa 1 usando mocks ou a API PHP. Etapa 3 pode ser dividida por módulos em sprints paralelos.

---

## 5. Estrutura Final de Pastas (Sugestão)

```
sanvegSPA/
├── api/                    # FastAPI (substitui api/*.php)
│   ├── app/
│   │   ├── main.py
│   │   ├── config.py
│   │   ├── database.py
│   │   ├── models/
│   │   ├── schemas/
│   │   ├── routers/
│   │   └── services/
│   ├── alembic/
│   ├── requirements.txt
│   └── Dockerfile
├── web/                    # SPA React (novo)
│   ├── src/
│   │   ├── components/
│   │   ├── pages/
│   │   ├── hooks/
│   │   ├── lib/
│   │   ├── styles/
│   │   └── main.tsx
│   ├── public/
│   │   ├── manifest.json
│   │   └── sw.js (gerado pelo Workbox)
│   ├── package.json
│   └── vite.config.ts
├── legacy/                 # PHP antigo (manter temporariamente)
│   ├── includes/
│   ├── programas/
│   └── ...
├── docs/
├── migrations/             # SQL legado (referência)
└── README.md
```

---

## 6. Riscos e Mitigações

| Risco | Mitigação |
|-------|-----------|
| Incompatibilidade de dados MySQL | Usar SQLAlchemy com schema espelhado; testes de migração |
| Regressão de funcionalidades | Manter PHP em paralelo durante transição; testes E2E |
| Performance offline | Limitar catálogos em cache; priorizar dados essenciais |
| Complexidade da sincronização | Começar com sync simples (last-write-wins); evoluir conflitos depois |

---

## 7. Próximos Passos Imediatos

1. Validar esta proposta (ajustes de escopo, prioridades, prazos).
2. Configurar repositório: branch `feature/fastapi` e `feature/spa`.
3. Iniciar **Etapa 1** — scaffold FastAPI + primeiro endpoint (auth).
4. Documentar contrato da API (OpenAPI) como referência para o frontend.

---

*Documento elaborado com base na análise do projeto sanvegSPA.*
