CREATE TABLE IF NOT EXISTS `config_email` (
  `id` INT NOT NULL DEFAULT 1,
  `smtp_host` VARCHAR(255) NOT NULL,
  `smtp_port` INT NOT NULL DEFAULT 587,
  `smtp_user` VARCHAR(255) NOT NULL,
  `smtp_pass` TEXT NOT NULL,
  `smtp_secure` ENUM('ssl', 'tls', 'none') DEFAULT 'tls',
  `from_email` VARCHAR(255) NOT NULL,
  `from_name` VARCHAR(255) NOT NULL,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `chk_single_row` CHECK (`id` = 1)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `config_email` (`id`, `smtp_host`, `smtp_port`, `smtp_user`, `smtp_pass`, `smtp_secure`, `from_email`, `from_name`) 
VALUES (1, 'smtp.exemplo.com', 587, 'contato@exemplo.com', 'sua_senha_aqui', 'tls', 'contato@exemplo.com', 'Meu App PHP')
ON DUPLICATE KEY UPDATE `id` = `id`;
