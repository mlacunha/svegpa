# API REST Sanveg

Para uso em subdiretório (ex: `/sanveg/api/`), defina em `config.php`:
```php
define('API_BASE_PATH', '/sanveg');
```

API REST customizada para o sistema SVEG (Sistema de Vigilância Epidemiológica).

## Autenticação

### Login
```http
POST /api/auth/login
Content-Type: application/json

{
  "login": "usuario",
  "password": "senha"
}
```

Resposta:
```json
{
  "token": "eyJ...",
  "user": {
    "login": "usuario",
    "name": "Nome",
    "email": "email@exemplo.com"
  }
}
```

### Uso do token
Incluir no header de todas as requisições protegidas:
```
Authorization: Bearer <token>
```

## Endpoints CRUD

Todos os endpoints (exceto `/api/auth/login` e `/api/health`) exigem autenticação.

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| GET | `/api/programas` | Listar programas |
| GET | `/api/programas/{id}` | Buscar programa |
| POST | `/api/programas` | Criar programa |
| PUT | `/api/programas/{id}` | Atualizar programa |
| DELETE | `/api/programas/{id}` | Excluir programa |

Recursos disponíveis: `programas`, `propriedades`, `produtores`, `produtos`, `hospedeiros`, `orgaos`, `orgaos_tipos`, `cargos`, `estados`, `municipios`, `normas`, `unidades`, `termo_inspecao`, `dashboard_filtros`, `reg_atividade`, `reg_metas`.

Recursos especiais:
- **relatorio_mapa** – aceita `latitude`/`longitude` (gera `coordenada` POINT)
- **area_inspecionada** – PK: `id_termo_inspecao` + `id` → `/api/area-inspecionada/{id_ti}/{id}`
- **amostra_coletada** – PK: `id_termo_inspecao` + `id` → `/api/amostra-coletada/{id_ti}/{id}`

## Paginação
```
GET /api/programas?limit=50&offset=0
```

## Health check
```http
GET /api/health
```
