-- area_inspecionada.id: INT -> CHAR(36) UUID
-- Necessário para sincronização local/web e para o aplicativo.
-- Execute apenas se a coluna ainda for INT (já alterado na web pelo usuário).

-- 1. Alterar tipo (pule se já for char(36))
-- ALTER TABLE area_inspecionada MODIFY COLUMN id CHAR(36) NOT NULL;

-- 2. Trigger: gera UUID quando id estiver vazio (fallback; o app geralmente envia o id)
DROP TRIGGER IF EXISTS before_insert_area_inspecionada;
CREATE TRIGGER before_insert_area_inspecionada BEFORE INSERT ON area_inspecionada FOR EACH ROW
BEGIN
    IF NEW.id IS NULL OR TRIM(NEW.id) = '' THEN
        SET NEW.id = UUID();
    END IF;
END;
