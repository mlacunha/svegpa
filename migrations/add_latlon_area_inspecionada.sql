-- Adiciona latitude e longitude em area_inspecionada (se ainda não existirem)
-- Necessário para vw_relatorio_mapa_dashboard_union.
-- Execute antes de vw_relatorio_mapa_dashboard_union.sql.
-- PULE este arquivo se as colunas já existirem (erro "Duplicate column name").
-- Para verificar: DESCRIBE area_inspecionada;

ALTER TABLE area_inspecionada ADD COLUMN latitude DECIMAL(10,8) NULL AFTER numero_suspeitas;
ALTER TABLE area_inspecionada ADD COLUMN longitude DECIMAL(11,8) NULL AFTER latitude;
