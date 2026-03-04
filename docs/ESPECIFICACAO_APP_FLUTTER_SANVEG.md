# Especificação do Aplicativo Flutter SVEG Mobile

## Documento de Referência para .cursorrules

Este documento descreve as especificações do aplicativo móvel Flutter **SVEG Mobile** – coleta de dados em campo com sincronização offline-first para o sistema web Sanveg (Sistema de Vigilância Epidemiológica).

---

## 1. Visão Geral do Projeto

### 1.1 Objetivo
Aplicativo Flutter para **coleta de dados fitossanitários em campo**, operando em modo **offline-first**, com sincronização posterior com o banco de dados MySQL do sistema Sanveg.

### 1.2 Contexto
- **Backend:** Sistema web PHP (Sanveg) em `E:\ServidorLocal\Projetos\sanveg`
- **Banco:** MySQL `sanveg` – host configurável (atualmente 209.50.227.136)
- **Cenário:** Uso em zonas rurais/agrícolas – rede instável ou ausente
- **Fluxo:** Coleta offline → armazenamento local (SQLite/Drift) → sincronização quando houver conectividade

### 1.3 Requisitos Arquiteturais
- **Offline-First:** Todas as operações prioritariamente locais; sync em segundo plano
- **Persistência local:** Drift (SQLite) – schema espelho das entidades necessárias
- **Sincronização resiliente:** Fila de operações pendentes, retry, conflitos (last-write-wins ou merge manual)
- **Geolocalização:** Uso de latitude/longitude em levantamentos (GPS em campo)
- **Status fitossanitário:** NORMAL (azul), SUSPEITA (laranja), FOCO (vermelho)

---

## 2. Entidades e Modelo de Dados

### 2.1 Tabelas de Referência (Read/Sync – Catálogos)
Dados baixados do servidor para uso em formulários. Sincronização pull periódica.

| Tabela | Campos Principais | Uso no App |
|--------|-------------------|------------|
| **programas** | id (char36), codigo, nome, nomes_comuns, nome_cientifico | Seleção no levantamento |
| **propriedades** | id, nome, n_cadastro, municipio, UF, id_proprietario, latitude, longitude | Seleção de propriedade inspecionada |
| **produtores** | id, nome, n_cadastro, cpf_cnpj, municipio, uf | Proprietário da propriedade |
| **produtos** | id, id_propriedade, produto | Produtos por propriedade |
| **hospedeiros** | id, id_programa, nomes_comuns, nome_cientifico | Hospedeiros por programa |
| **orgaos** | id, sigla, nome, tipo, UF_sede | Órgão do fiscal |
| **orgaos_tipos** | id, tipo | Tipo de órgão |
| **cargos** | id, orgao, sigla, nome | Cargo do fiscal |
| **estados** | id, nome, sigla, regiao | Lista de UFs |
| **municipios** | id, nome, estado_id | Municípios (código IBGE) |

### 2.2 Tabelas de Coleta em Campo (Write/Sync)
Dados criados no app e enviados ao servidor.

#### **relatorio_mapa**
Registros de levantamento fitossanitário com coordenadas.

| Campo | Tipo | Obrigatório | Descrição |
|-------|------|-------------|-----------|
| id | int AUTO_INCREMENT | - | Gerado no servidor |
| id_programa | char(36) | Sim | FK programas |
| ano | char(4) | Sim | Ano do levantamento |
| data | varchar(20) | Sim | Data (YYYY-MM-DD) |
| trimestre | int | Não | 1-4 |
| orgao | varchar(36) | Não | FK orgaos |
| id_usuario | varchar(255) | Não | Login do fiscal |
| termo_inspecao | int | Não | Nº termo |
| termo_coleta | int | Não | Nº termo coleta |
| id_propriedade | char(36) | Não | FK propriedades |
| tipo_imovel | varchar(60) | Não | Comercial/Não Comercial |
| municipio | varchar(60) | Não | Nome município |
| cultura | varchar(30) | Não | Ex: Laranja, Mandioca |
| latitude | decimal(10,8) | Não | Coordenada |
| longitude | decimal(11,8) | Não | Coordenada |
| status | varchar(30) | Não | **NORMAL**, **SUSPEITA**, **FOCO** |

#### **termo_inspecao**
Termo de inspeção – agrupa inspeções em uma propriedade.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | char(36) | UUID – gerado no app |
| data_inspecao | date | Data da inspeção |
| data_amostragem | date | Data da amostragem (opcional) |
| termo_inspecao | varchar(30) | Sequencial/Matrícula/Ano (calculado por técnico/ano) |
| termo_coleta | varchar(30) | Sequencial/Matrícula/Ano (calculado por técnico/ano) |
| id_usuario | varchar(255) | Login do técnico responsável |
| id_auxiliar | varchar(255) | Login do auxiliar (opcional) |
| id_propriedade | char(36) | FK propriedades |

#### **area_inspecionada**
Área inspecionada vinculada ao termo.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | int | Sequencial por termo |
| id_termo_inspecao | char(36) | FK termo_inspecao |
| id_programa | char(36) | FK programas |
| tipo_area | varchar(40) | - |
| nome_local | varchar(255) | - |
| especie | varchar(40) | - |
| variedade | varchar(255) | - |
| numero_plantas | int | - |
| numero_inspecionadas | int | - |
| numero_suspeitas | int | - |
| obs | text | Observações |

#### **amostra_coletada**
Amostras com coordenadas de coleta.

| Campo | Tipo | Descrição |
|-------|------|-----------|
| id | int | Sequencial por termo |
| id_termo_inspecao | char(36) | FK termo_inspecao |
| identificacao_coleta | varchar(30) | - |
| id_area_inspecionada | int | FK area_inspecionada |
| latitude | decimal(10,8) | - |
| longitude | decimal(11,8) | - |

### 2.3 Controle Sequencial (termo_inspecao / termo_coleta)
Tabela **controle_sequencial**: `login`, `ano`, `seq_ti`, `seq_tc`. Usada para numerar termos por usuário/ano. Formato: **Sequencial/Matrícula/Ano** (ex: 3/admin/2026). Matrícula = COALESCE(matricula, login) de sec_users. O app pode gerar IDs locais e o servidor ajusta na sincronização.

### 2.4 Autenticação
- **sec_users**: login, pswd (hash), name, email, orgao, role, unidade, matricula, phone
- **sec_users_api**: alternativa para API (login, pswd, name, email, active)
- O backend PHP **não possui API REST documentada**. Será necessário implementar endpoints para:
  - Login / token
  - Download de catálogos (programas, propriedades, produtores, etc.)
  - Upload de relatorio_mapa, termo_inspecao, area_inspecionada, amostra_coletada

---

## 3. Status Fitossanitário

| Valor | Cor (UI) | Hex | Uso |
|-------|----------|-----|-----|
| NORMAL | Azul | #2563eb | Sem suspeita |
| SUSPEITA | Laranja | #f97316 | Suspeita de praga/doença |
| FOCO | Vermelho | #dc2626 | Foco confirmado |

---

## 4. Fluxos de Coleta em Campo

### 4.1 Levantamento Rápido (relatorio_mapa)
1. Selecionar Programa
2. Selecionar Propriedade (ou digitar município)
3. Informar cultura, tipo imóvel
4. Capturar ou informar latitude/longitude
5. Definir status: NORMAL / SUSPEITA / FOCO
6. Salvar localmente → enfileirar para sync

### 4.2 Inspeção Detalhada (termo_inspecao)
1. Criar termo (propriedade, data)
2. Adicionar áreas inspecionadas (espécie, variedade, nº plantas, etc.)
3. Adicionar amostras coletadas (identificacao_coleta, parte, lat/long)
4. Salvar localmente → enfileirar para sync

---

## 5. Estratégia de Sincronização

### 5.1 Pull (Download)
- **Catálogos:** programados (ex.: a cada 24h ou ao abrir app com rede)
- **Endpoints sugeridos:** `GET /api/programas`, `GET /api/propriedades`, etc.
- Armazenar em tabelas locais Drift com `updated_at` para delta sync (se o backend suportar)

### 5.2 Push (Upload)
- Fila de operações: INSERT/UPDATE de `relatorio_mapa`, `termo_inspecao`, `area_inspecionada`, `amostra_coletada`
- Cada registro local: flag `synced` (bool), `sync_error` (string opcional)
- Ao conectar: enviar pendentes; em conflito: last-write-wins ou notificar usuário
- IDs: UUIDs no app para entidades com PK char(36); `id` auto-increment no servidor para `relatorio_mapa`

### 5.3 Resiliência
- Retry com backoff exponencial
- Armazenar fila em SQLite mesmo após reinício
- Indicador visual de "pendentes não sincronizados"
- Sync manual (botão) + sync automático quando rede disponível

---

## 6. Especificações Flutter / .cursorrules

### 6.1 Padrões de Código
```
- Linguagem: Dart 3.x, Flutter 3.x
- Estado: Preferir Riverpod ou Bloc; evitar setState em árvores profundas
- Nomenclatura: Colors (C maiúsculo), Icons (I maiúsculo)
- Arquitetura: Clean Architecture (Domain, Data, Presentation)
- Estado visual: Preferir ControlState em vez de MaterialState quando aplicável
```

### 6.2 Persistência Local
```
- Drift (SQLite) para todos os dados offline
- Schema: espelho das tabelas MySQL relevantes + colunas de controle (synced, created_at, updated_at)
- Migrations versionadas
```

### 6.3 Georreferenciamento
```
- Pacote: geolocator ou geocoding para captura de coordenadas
- Sempre registrar latitude/longitude em levantamentos
- Permitir edição manual de coordenadas
```

### 6.4 UI/UX
```
- Material Design 3
- Suporte a tema claro/escuro
- Feedback claro de operação offline vs online
- Formulários com validação inline
- Listas com busca e filtro
```

### 6.5 Conectividade
```
- connectivity_plus para detectar rede
- Dio ou http para chamadas API com timeout e retry
- Interceptors para token e logging
```

---

## 7. API REST Necessária (Backend PHP)

O sistema Sanveg atual é web-only. Para o app Flutter, será necessário criar:

### 7.1 Autenticação
- `POST /api/auth/login` → { login, password } → { token, user }
- Headers: `Authorization: Bearer {token}`

### 7.2 Endpoints de Leitura (GET)
- `/api/programas`
- `/api/propriedades`
- `/api/produtores`
- `/api/produtos`
- `/api/hospedeiros`
- `/api/orgaos`
- `/api/orgaos_tipos`
- `/api/cargos`
- `/api/estados`
- `/api/municipios`

### 7.3 Endpoints de Escrita (POST/PUT)
- `POST /api/relatorio-mapa` – criar registro de levantamento
- `POST /api/termo-inspecao` – criar termo
- `POST /api/area-inspecionada` – criar área
- `POST /api/amostra-coletada` – criar amostra

### 7.4 Formato Sugerido
- JSON para request/response
- Código HTTP semântico (200, 201, 400, 401, 404, 500)
- Mensagens de erro em `{ "error": "..." }`

---

## 8. Estrutura de Pastas Sugerida (Flutter)

```
lib/
├── main.dart
├── app/
│   └── app.dart
├── core/
│   ├── database/        # Drift tables, DAOs
│   ├── api/             # Client, endpoints
│   ├── sync/            # Sync engine, queue
│   └── utils/
├── domain/
│   ├── entities/
│   └── repositories/
├── data/
│   ├── models/
│   ├── repositories_impl/
│   └── datasources/
└── presentation/
    ├── screens/
    ├── widgets/
    └── providers/
```

---

## 9. Resumo para .cursorrules

Ao criar o arquivo `.cursorrules` no novo projeto Flutter, incluir:

- Referência a este documento como fonte da especificação
- Backend: Sanveg PHP/MySQL; APIs REST a ser implementadas
- Entidades: programas, propriedades, produtores, produtos, hospedeiros, orgaos, cargos, estados, municipios; relatorio_mapa, termo_inspecao, area_inspecionada, amostra_coletada
- Status: NORMAL (azul), SUSPEITA (laranja), FOCO (vermelho)
- Offline-first com Drift e fila de sincronização
- Geolocalização obrigatória em levantamentos
- Arquitetura: Clean Architecture, Riverpod/Bloc
- Cenário: zonas rurais, rede instável

---

*Documento gerado a partir da análise do projeto Sanveg em E:\ServidorLocal\Projetos\sanveg*
*Data: 2026-02-11*
