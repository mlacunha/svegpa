-- Reset de senha: token e expiração
-- Execute em ambientes que ainda não possuem as colunas
ALTER TABLE sec_users ADD COLUMN pswd_reset_code VARCHAR(64) NULL;
ALTER TABLE sec_users ADD COLUMN pswd_reset_expires DATETIME NULL;
