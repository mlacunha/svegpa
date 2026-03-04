-- amostra_coletada passa a ser filha de area_inspecionada (não mais de termo_inspecao)
-- Remove parte_coletada (legado); partes vêm de parte_coletada + materiais_coleta_amostra

-- 1. Tabela materiais_coleta_amostra (catálogo de partes coletáveis)
CREATE TABLE IF NOT EXISTS materiais_coleta_amostra (
  id INT NOT NULL AUTO_INCREMENT,
  nome VARCHAR(120) NOT NULL,
  PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dados iniciais (ignore se já existir)
INSERT IGNORE INTO materiais_coleta_amostra (id, nome) VALUES (1,'Folha'), (2,'Caule'), (3,'Raiz'), (4,'Fruto'), (5,'Flor'), (6,'Semente'), (7,'Outros');

-- 2. Adicionar id_area_inspecionada em amostra_coletada (omitir se já existir)
ALTER TABLE amostra_coletada ADD COLUMN id_area_inspecionada INT NULL AFTER id_termo_inspecao;
UPDATE amostra_coletada SET id_area_inspecionada = 1 WHERE id_area_inspecionada IS NULL;
ALTER TABLE amostra_coletada MODIFY id_area_inspecionada INT NOT NULL;

-- 3. Remover parte_coletada (legado) - comentar se coluna não existir
ALTER TABLE amostra_coletada DROP COLUMN parte_coletada;

-- 4. Tabela parte_coletada (amostra -> material: N:N)
CREATE TABLE IF NOT EXISTS parte_coletada (
  id_termo_inspecao CHAR(36) NOT NULL,
  id_amostra_coletada INT NOT NULL,
  id_material INT NOT NULL,
  PRIMARY KEY (id_termo_inspecao, id_amostra_coletada, id_material),
  FOREIGN KEY (id_material) REFERENCES materiais_coleta_amostra(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- 5. Ajustar trigger amostra: id sequencial por área
DROP TRIGGER IF EXISTS before_insert_amostra_coletada;
DELIMITER ;;
CREATE TRIGGER before_insert_amostra_coletada BEFORE INSERT ON amostra_coletada FOR EACH ROW
BEGIN
    SELECT COALESCE(MAX(id), 0) + 1 INTO @next_id
    FROM amostra_coletada
    WHERE id_termo_inspecao = NEW.id_termo_inspecao AND id_area_inspecionada = NEW.id_area_inspecionada;
    SET NEW.id = @next_id;
END;;
DELIMITER ;
