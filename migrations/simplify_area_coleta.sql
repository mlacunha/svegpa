-- Simplificação: amostra_coletada removida; coleta integrada em area_inspecionada
-- parte_coletada passa a ser filha de area_inspecionada (não mais de amostra_coletada)

-- 1. area_inspecionada: coleta_mostra → coleta_amostra (execute apenas se sua coluna for coleta_mostra)
--    Se sua coluna já for coleta_amostra, comente a linha abaixo
ALTER TABLE area_inspecionada 
  RENAME COLUMN coleta_mostra TO coleta_amostra;

ALTER TABLE area_inspecionada 
  ADD COLUMN identificacao_amostra VARCHAR(60) NULL AFTER obs,
  ADD COLUMN resultado VARCHAR(255) NULL AFTER identificacao_amostra,
  ADD COLUMN associado VARCHAR(120) NULL AFTER resultado;

-- 2. Remover tabela amostra_coletada (se existir)
DROP TABLE IF EXISTS parte_coletada;
DROP TABLE IF EXISTS amostra_coletada;

-- 3. Recriar parte_coletada como filha de area_inspecionada
CREATE TABLE parte_coletada (
  id_termo_inspecao CHAR(36) NOT NULL,
  id_area_inspecionada INT NOT NULL,
  id_material INT NOT NULL,
  PRIMARY KEY (id_termo_inspecao, id_area_inspecionada, id_material),
  FOREIGN KEY (id_material) REFERENCES materiais_coleta_amostra(id) ON DELETE CASCADE,
  FOREIGN KEY (id_termo_inspecao, id_area_inspecionada) REFERENCES area_inspecionada(id_termo_inspecao, id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
