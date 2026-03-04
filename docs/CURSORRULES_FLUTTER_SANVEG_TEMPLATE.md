# Template .cursorrules para o projeto Flutter SVEG Mobile
# Copie o conteúdo abaixo para o arquivo .cursorrules na raiz do novo projeto Flutter

---
Regras de Desenvolvimento - SVEG Mobile (Flutter Offline-First)

## Contexto do Projeto
- **App:** SVEG Mobile – coleta de dados fitossanitários em campo
- **Backend:** Sistema Sanveg PHP/MySQL (E:\ServidorLocal\Projetos\sanveg)
- **Cenário:** Uso em zonas rurais – rede instável ou ausente
- **Especificação completa:** Consultar docs/ESPECIFICACAO_APP_FLUTTER_SANVEG.md

## 1. Padrões Flutter/Dart
- **Estado visual:** Preferir ControlState em vez de MaterialState.
- **Nomenclatura:** Sempre Colors (C maiúsculo) e Icons (I maiúsculo).
- **Arquitetura:** Clean Architecture (Domain, Data, Presentation).
- **Offline-First:** Drift (SQLite) para persistência local; padrões de sync resilientes.
- **Modularidade:** Widgets reutilizáveis; evitar árvores muito profundas.

## 2. Entidades Principais
- **Catálogos (sync pull):** programas, propriedades, produtores, produtos, hospedeiros, orgaos, orgaos_tipos, cargos, estados, municipios
- **Coleta (sync push):** relatorio_mapa, termo_inspecao, area_inspecionada, amostra_coletada

## 3. Status Fitossanitário
- **NORMAL** → Azul (#2563eb)
- **SUSPEITA** → Laranja (#f97316)
- **FOCO** → Vermelho (#dc2626)

## 4. Persistência e Sincronização
- Schema Drift espelhando tabelas MySQL relevantes
- Colunas de controle: synced (bool), sync_error (string), created_at, updated_at
- Fila de operações pendentes em SQLite
- Retry com backoff; indicador de pendentes na UI

## 5. Geolocalização
- Registrar latitude/longitude em levantamentos (relatorio_mapa, amostra_coletada)
- Pacotes sugeridos: geolocator, geocoding
- Permitir edição manual de coordenadas

## 6. APIs REST (Backend a implementar)
- Auth: POST /api/auth/login
- Catálogos: GET /api/{programas|propriedades|produtores|...}
- Escrita: POST /api/relatorio-mapa, /termo-inspecao, /area-inspecionada, /amostra-coletada

## 7. Comportamento
- Modularidade: não gerar arquivos gigantes; refatorar em módulos menores
- Contexto: Saúde agrícola e vigilância epidemiológica vegetal
- Ceticismo: se o prompt for vago, pedir clarificação antes de gerar código
- Evitar explicações óbvias – foco em implementação e justificativas técnicas
