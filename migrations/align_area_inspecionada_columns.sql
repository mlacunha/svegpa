-- Alinha colunas de area_inspecionada ao código (coleta_amostra→coleta_mostra, observacao→obs)
-- Execute se sua tabela tiver coleta_amostra e observacao em vez de coleta_mostra e obs.
-- MySQL 8.0.3+ necessário para RENAME COLUMN.

-- 1. coleta_amostra → coleta_mostra (código usa coleta_mostra)
ALTER TABLE area_inspecionada RENAME COLUMN coleta_amostra TO coleta_mostra;

-- 2. observacao → obs (código usa obs)
ALTER TABLE area_inspecionada RENAME COLUMN observacao TO obs;
