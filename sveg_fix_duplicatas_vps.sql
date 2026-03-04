-- ============================================================
-- Script de emergência: limpa duplicatas e garante PRIMARY KEY
-- Execute no phpMyAdmin do VPS antes de subir a nova API
-- ============================================================

-- 1. Limpar duplicatas em termo_inspecao (mantém o mais recente)
DELETE t1 FROM termo_inspecao t1
INNER JOIN termo_inspecao t2
WHERE t1.id = t2.id AND t1.criado_em < t2.criado_em;

-- 2. Limpar duplicatas em area_inspecionada (mantém o mais recente)
DELETE a1 FROM area_inspecionada a1
INNER JOIN area_inspecionada a2
WHERE a1.id = a2.id AND a1.data_criacao < a2.data_criacao;

-- 3. Limpar registros com IDs inválidos (gerados por Date.now())
DELETE FROM area_inspecionada WHERE CHAR_LENGTH(id) < 30;
DELETE FROM termo_inspecao WHERE CHAR_LENGTH(id) < 30;

-- 4. Verificar resultado
SELECT 'termo_inspecao' as tabela, COUNT(*) as total FROM termo_inspecao
UNION ALL
SELECT 'area_inspecionada' as tabela, COUNT(*) as total FROM area_inspecionada;
