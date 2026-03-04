-- Migration: Alterações na tabela termo_inspecao
-- Aplicar apenas em bancos com estrutura antiga (data, ficha_inspecao int, termo_coleta int).
--
-- Alterações:
-- - data -> data_inspecao (date), data_amostragem (date) facultativa
-- - ficha_inspecao -> termo_inspecao varchar(30) (renomeado)
-- - termo_coleta -> varchar(30), calculado como Sequencial/Matricula/Ano
-- - id_auxiliar int NULL facultativo (ajudante na inspeção/amostragem)

-- 1. Adicionar novas colunas de data
ALTER TABLE termo_inspecao ADD COLUMN data_inspecao DATE NULL AFTER id;
ALTER TABLE termo_inspecao ADD COLUMN data_amostragem DATE NULL AFTER data_inspecao;
UPDATE termo_inspecao SET data_inspecao = DATE(data) WHERE data IS NOT NULL;
ALTER TABLE termo_inspecao DROP COLUMN data;

-- 2. Renomear ficha_inspecao -> termo_inspecao e alterar para varchar(30)
ALTER TABLE termo_inspecao CHANGE COLUMN ficha_inspecao termo_inspecao VARCHAR(30) NULL;

-- 3. Alterar termo_coleta para varchar(30)
ALTER TABLE termo_inspecao MODIFY COLUMN termo_coleta VARCHAR(30) NULL;

-- 4. Adicionar id_auxiliar (login do usuário auxiliar, FK implícita para sec_users)
-- Se o banco já tiver id_auxiliar INT, use: ALTER TABLE termo_inspecao MODIFY id_auxiliar VARCHAR(255) NULL;
ALTER TABLE termo_inspecao ADD COLUMN id_auxiliar VARCHAR(255) NULL AFTER id_usuario;
