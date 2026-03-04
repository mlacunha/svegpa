# Sincronização Local ↔ Web

Sincroniza as tabelas **produtores**, **propriedades**, **termo_inspecao** e **area_inspecionada** entre o banco local (sveg) e o banco web (produção). O fluxo principal é **local → web**: dados coletados no campo (app) sobem para o servidor.

**Pré-requisito:** A coluna `area_inspecionada.id` deve ser CHAR(36) UUID em ambos os bancos. Use `migrations/area_inspecionada_id_uuid.sql` se necessário.

## Configuração

- **Web:** `config/database.php` (host 209.50.227.136, db sanveg)
- **Local:** `config/database_local.php` (localhost:3306, db sveg, root, sem senha)

Variáveis de ambiente opcionais para o local:
- `DB_LOCAL_HOST` (padrão: 127.0.0.1)
- `DB_LOCAL_PORT` (padrão: 3306)
- `DB_LOCAL_NAME` (padrão: sveg)
- `DB_LOCAL_USER` (padrão: root)
- `DB_LOCAL_PASS` (padrão: vazio)

## Uso

```bash
# Local → Web (padrão)
php sync_web_local.php

# ou explicitamente
php sync_web_local.php local2web

# Web → Local
php sync_web_local.php web2local
```

## Comportamento

- Usa `INSERT ... ON DUPLICATE KEY UPDATE` para inserir ou atualizar por chave primária
- A tabela `area_inspecionada` tem trigger de id automático: o trigger é desativado durante a sync e reativado ao final
- Ordem respeitada (FKs): produtores → propriedades → termo_inspecao → area_inspecionada
