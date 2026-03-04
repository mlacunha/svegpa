-- Criação da tabela tipos_orgao para suportar sincronização PWA
-- Execute no banco da VPS (sanveg)

CREATE TABLE IF NOT EXISTS `tipos_orgao` (
  `id`       varchar(36)  NOT NULL,
  `nome`     varchar(100) NOT NULL,
  `descricao` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dados iniciais (os 3 tipos hardcoded que o PWA usava antes)
INSERT IGNORE INTO `tipos_orgao` (`id`, `nome`, `descricao`) VALUES
  ('t1', 'Instituição de Defesa Estadual', NULL),
  ('t2', 'Ministério da Agricultura',      NULL),
  ('t3', 'Laboratório de Defesa',          NULL);
