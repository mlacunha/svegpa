-- ============================================================
-- Script: Adiciona campos de controle anual do sequencial de termos
-- Compatível com MySQL 5.7+ e MySQL 8+
-- Execute no phpMyAdmin do VPS
-- ============================================================

-- Remove colunas se já existirem (para evitar erro) e recria
-- NOTA: Ignore erros de "Can't DROP ... check that column exists" se colunas ainda não existem

-- Adiciona seq_tf_ano (sequencial do Termo de Inspeção por ano)
ALTER TABLE `users` ADD COLUMN `seq_tf_ano` INT NULL DEFAULT NULL;

-- Adiciona seq_tc_ano (sequencial do Termo de Coleta por ano)
ALTER TABLE `users` ADD COLUMN `seq_tc_ano` INT NULL DEFAULT NULL;

-- Verificar resultado
SELECT id, nome, matricula, seq_tf, seq_tf_ano, seq_tc, seq_tc_ano FROM users LIMIT 5;
