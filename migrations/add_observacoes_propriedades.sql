-- Adiciona coluna observacoes na tabela propriedades
ALTER TABLE propriedades ADD COLUMN observacoes TEXT NULL AFTER longitude;
