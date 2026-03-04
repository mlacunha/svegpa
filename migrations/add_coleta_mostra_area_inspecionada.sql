-- Adiciona coleta_mostra (boolean) em area_inspecionada
-- Exibido quando Nº Suspeitas > 0; ao marcar, preenche data_amostragem e termo_coleta do termo
ALTER TABLE area_inspecionada ADD COLUMN coleta_mostra TINYINT(1) NOT NULL DEFAULT 0 AFTER numero_suspeitas;
