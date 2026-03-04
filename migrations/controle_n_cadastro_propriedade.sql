-- Controle de numeração sequencial para n_cadastro de propriedades por UF.
-- Formato gerado: PA-000001 (6 dígitos).
DROP TABLE IF EXISTS controle_n_cadastro;
CREATE TABLE controle_n_cadastro (
  uf CHAR(2) NOT NULL PRIMARY KEY,
  seq INT NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
