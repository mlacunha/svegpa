-- vw_relatorio_mapa_dashboard: unifica relatorio_mapa (legado) com termo_inspecao + area_inspecionada + propriedade
-- Permite filtro por programa, ano, trimestre, município.
-- Requer: area_inspecionada com latitude, longitude (adicionar se faltar: ALTER TABLE area_inspecionada ADD COLUMN latitude DECIMAL(10,8) NULL, ADD COLUMN longitude DECIMAL(11,8) NULL;)

DROP VIEW IF EXISTS vw_relatorio_mapa_dashboard;

CREATE SQL SECURITY INVOKER VIEW vw_relatorio_mapa_dashboard AS
-- Parte 1: dados legados (relatorio_mapa)
SELECT
    r.id,
    CONVERT(r.id_programa USING utf8mb4) COLLATE utf8mb4_unicode_ci AS id_programa,
    CONVERT(r.ano USING utf8mb4) COLLATE utf8mb4_unicode_ci AS ano,
    CAST(r.data AS DATE) AS data_formatada,
    r.trimestre,
    CONVERT(r.orgao USING utf8mb4) COLLATE utf8mb4_unicode_ci AS orgao,
    CONVERT(r.id_usuario USING utf8mb4) COLLATE utf8mb4_unicode_ci AS id_usuario,
    r.termo_inspecao,
    r.termo_coleta,
    CONVERT(r.id_propriedade USING utf8mb4) COLLATE utf8mb4_unicode_ci AS id_propriedade,
    CONVERT(r.tipo_imovel USING utf8mb4) COLLATE utf8mb4_unicode_ci AS tipo_imovel,
    CONVERT(r.municipio USING utf8mb4) COLLATE utf8mb4_unicode_ci AS municipio,
    CONVERT(r.cultura USING utf8mb4) COLLATE utf8mb4_unicode_ci AS cultura,
    r.latitude,
    r.longitude,
    NULL AS coordenada,
    CONVERT(r.status USING utf8mb4) COLLATE utf8mb4_unicode_ci AS status
FROM relatorio_mapa r

UNION ALL

-- Parte 2: termo_inspecao + area_inspecionada + propriedade (dados novos)
SELECT
    (1000000000 + (CRC32(CONCAT(t.id, '-', a.id)) & 2147483647)) AS id,
    CONVERT(t.id_programa USING utf8mb4) COLLATE utf8mb4_unicode_ci AS id_programa,
    CONVERT(CAST(YEAR(COALESCE(t.data_inspecao, t.data_amostragem, t.criado_em)) AS CHAR(4)) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS ano,
    DATE(COALESCE(t.data_inspecao, t.data_amostragem, t.criado_em)) AS data_formatada,
    CEIL(MONTH(COALESCE(t.data_inspecao, t.data_amostragem, t.criado_em)) / 3) AS trimestre,
    CONVERT(u.orgao USING utf8mb4) COLLATE utf8mb4_unicode_ci AS orgao,
    CONVERT(t.id_usuario USING utf8mb4) COLLATE utf8mb4_unicode_ci AS id_usuario,
    NULL AS termo_inspecao,
    NULL AS termo_coleta,
    CONVERT(t.id_propriedade USING utf8mb4) COLLATE utf8mb4_unicode_ci AS id_propriedade,
    CONVERT(COALESCE(a.tipo_area, p.classificacao) USING utf8mb4) COLLATE utf8mb4_unicode_ci AS tipo_imovel,
    CONVERT(p.municipio USING utf8mb4) COLLATE utf8mb4_unicode_ci AS municipio,
    CONVERT(a.especie USING utf8mb4) COLLATE utf8mb4_unicode_ci AS cultura,
    COALESCE(a.latitude, p.latitude) AS latitude,
    COALESCE(a.longitude, p.longitude) AS longitude,
    CASE
        WHEN (COALESCE(a.latitude, p.latitude) IS NOT NULL AND COALESCE(a.longitude, p.longitude) IS NOT NULL)
        THEN ST_GeomFromText(CONCAT('SRID=4326;POINT(', COALESCE(a.longitude, p.longitude), ' ', COALESCE(a.latitude, p.latitude), ')'))
        ELSE NULL
    END AS coordenada,
    CONVERT(CASE WHEN COALESCE(a.numero_suspeitas, 0) > 0 THEN 'SUSPEITA' ELSE 'NORMAL' END USING utf8mb4) COLLATE utf8mb4_unicode_ci AS status
FROM termo_inspecao t
INNER JOIN area_inspecionada a ON a.id_termo_inspecao = t.id
LEFT JOIN propriedades p ON t.id_propriedade = p.id
LEFT JOIN sec_users u ON t.id_usuario = u.login
WHERE COALESCE(a.latitude, p.latitude) IS NOT NULL
  AND COALESCE(a.longitude, p.longitude) IS NOT NULL;
