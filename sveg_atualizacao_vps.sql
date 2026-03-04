-- Script de atualização do banco de dados do VPS para suportar as novidades do PWA (Sincronização Offline)

-- 1. Adicionando controles de sequência no cadastro de usuários
ALTER TABLE `users`
  ADD COLUMN `seq_tf` INT DEFAULT 0,
  ADD COLUMN `seq_tc` INT DEFAULT 0;

-- 2. Adicionando campos necessários no termo_inspecao (se ainda não existirem no VPS)
ALTER TABLE `termo_inspecao` 
  ADD COLUMN `data_amostragem` DATE DEFAULT NULL,
  ADD COLUMN `termo_coleta` VARCHAR(30) DEFAULT NULL,
  ADD COLUMN `id_auxiliar` VARCHAR(255) DEFAULT NULL;

-- 3. Aumentando o tamanho de campos que podem receber UUIDs ou Strings (Caso não tenham sido alterados)
ALTER TABLE `termo_inspecao` MODIFY COLUMN `termo_inspecao` VARCHAR(30) DEFAULT NULL;
ALTER TABLE `termo_inspecao` MODIFY COLUMN `id_usuario` VARCHAR(255) DEFAULT NULL;
ALTER TABLE `area_inspecionada` MODIFY COLUMN `id` CHAR(36) NOT NULL;
ALTER TABLE `area_inspecionada` MODIFY COLUMN `id_termo_inspecao` CHAR(36) NOT NULL;

-- Observação: Caso algum comando dê erro relatando que a coluna já existe, você pode ignorar e prosseguir para o próximo bloco.
