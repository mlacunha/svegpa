-- Migração para alinhar tabelas auxiliares com o PWA
-- Compatível com MySQL 5.7 / MariaDB 10.x
-- Execute no banco da VPS (sanveg)

-- 1. orgaos: adicionar coluna cnpj (se não existir)
SET @dbname = DATABASE();
SET @tbl = 'orgaos';
SET @col = 'cnpj';
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tbl AND COLUMN_NAME = @col) = 0,
    'ALTER TABLE `orgaos` ADD COLUMN `cnpj` varchar(20) DEFAULT NULL',
    'SELECT ''coluna cnpj ja existe em orgaos'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- 2. unidades: ampliar municipio de varchar(36) para varchar(100)
ALTER TABLE `unidades`
  MODIFY COLUMN `municipio` varchar(100) DEFAULT NULL;

-- 3. cargos: adicionar coluna descricao (se não existir)
SET @tbl = 'cargos';
SET @col = 'descricao';
SET @sql = IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
     WHERE TABLE_SCHEMA = @dbname AND TABLE_NAME = @tbl AND COLUMN_NAME = @col) = 0,
    'ALTER TABLE `cargos` ADD COLUMN `descricao` varchar(500) DEFAULT NULL',
    'SELECT ''coluna descricao ja existe em cargos'''
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

