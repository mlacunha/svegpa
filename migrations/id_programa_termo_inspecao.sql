-- id_programa: de area_inspecionada para termo_inspecao (registro pai)
-- Execute em ordem.

-- 1. Adicionar id_programa em termo_inspecao
ALTER TABLE termo_inspecao ADD COLUMN id_programa CHAR(36) NULL AFTER id_propriedade;

-- 2. Migrar dados existentes (copiar da primeira área de cada termo)
UPDATE termo_inspecao t
SET t.id_programa = (
    SELECT a.id_programa FROM area_inspecionada a
    WHERE a.id_termo_inspecao = t.id
    ORDER BY a.id ASC
    LIMIT 1
)
WHERE EXISTS (
    SELECT 1 FROM area_inspecionada a WHERE a.id_termo_inspecao = t.id
);

-- 3. Remover id_programa de area_inspecionada
ALTER TABLE area_inspecionada DROP COLUMN id_programa;
