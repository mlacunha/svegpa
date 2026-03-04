-- Simplificação do módulo Órgãos: remover url_logo e servidor_logo
-- O campo logo passa a armazenar apenas o caminho relativo da imagem (uploads/orgaos_logos/...)
-- Execute após aplicar as alterações no módulo Órgãos.

ALTER TABLE orgaos DROP COLUMN url_logo;
ALTER TABLE orgaos DROP COLUMN servidor_logo;
