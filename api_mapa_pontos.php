<?php
/**
 * API: pontos do mapa com filtros (ano, trimestre, programa, municipio).
 * Retorna JSON para mapa_fullscreen.php e mapas.
 */
header('Content-Type: application/json; charset=utf-8');
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/auth.php';
requireLogin();

$ano = isset($_GET['ano']) ? trim($_GET['ano']) : '';
$trimestre = isset($_GET['trimestre']) ? (int)$_GET['trimestre'] : 0;
$id_programa = isset($_GET['programa']) ? trim($_GET['programa']) : '';
$municipio = isset($_GET['municipio']) ? trim($_GET['municipio']) : '';

$db = (new Database())->getConnection();
if (!$db) {
    echo json_encode(['pontos' => [], 'erro' => 'Conexão falhou']);
    exit;
}

$where = ["v.latitude IS NOT NULL", "v.longitude IS NOT NULL"];
$params = [];
if ($ano !== '') {
    $where[] = "v.ano = ?";
    $params[] = $ano;
}
if ($trimestre > 0) {
    $where[] = "v.trimestre = ?";
    $params[] = $trimestre;
}
if ($id_programa !== '') {
    $where[] = "v.id_programa = ?";
    $params[] = $id_programa;
}
if ($municipio !== '') {
    $where[] = "TRIM(v.municipio) = TRIM(?)";
    $params[] = $municipio;
}

$sql = "
    SELECT v.latitude, v.longitude, v.status, v.municipio, v.cultura, v.tipo_imovel, v.data_formatada,
           v.id_programa, COALESCE(p.nome, 'Sem programa') as programa_nome
    FROM vw_relatorio_mapa_dashboard v
    LEFT JOIN programas p ON v.id_programa = p.id
    WHERE " . implode(' AND ', $where);

try {
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $pontos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($pontos as &$p) {
        if (isset($p['data_formatada']) && $p['data_formatada']) {
            $p['data_formatada'] = date('d/m/Y', strtotime($p['data_formatada']));
        }
    }
    echo json_encode(['pontos' => $pontos]);
} catch (PDOException $e) {
    $pontos = [];
    try {
        $sql = "
            SELECT r.latitude, r.longitude, r.status, r.municipio, r.cultura, r.tipo_imovel,
                   CAST(r.data AS DATE) AS data_formatada, r.id_programa,
                   COALESCE(p.nome, 'Sem programa') as programa_nome
            FROM relatorio_mapa r
            LEFT JOIN programas p ON r.id_programa = p.id
            WHERE r.latitude IS NOT NULL AND r.longitude IS NOT NULL";
        $add = [];
        $p2 = [];
        if ($ano !== '') { $add[] = "r.ano = ?"; $p2[] = $ano; }
        if ($trimestre > 0) { $add[] = "r.trimestre = ?"; $p2[] = $trimestre; }
        if ($id_programa !== '') { $add[] = "r.id_programa = ?"; $p2[] = $id_programa; }
        if ($municipio !== '') { $add[] = "TRIM(r.municipio) = TRIM(?)"; $p2[] = $municipio; }
        if (!empty($add)) { $sql .= " AND " . implode(' AND ', $add); }
        $stmt = $db->prepare($sql);
        $stmt->execute($p2);
        $pontos = array_merge($pontos, $stmt->fetchAll(PDO::FETCH_ASSOC));
    } catch (PDOException $e2) {}
    try {
        $sql = "
            SELECT COALESCE(a.latitude, pr.latitude) AS latitude, COALESCE(a.longitude, pr.longitude) AS longitude,
                   CASE WHEN COALESCE(a.numero_suspeitas, 0) > 0 THEN 'SUSPEITA' ELSE 'NORMAL' END AS status,
                   pr.municipio, a.especie AS cultura, COALESCE(a.tipo_area, pr.classificacao) AS tipo_imovel,
                   DATE(COALESCE(t.data_inspecao, t.data_amostragem, t.criado_em)) AS data_formatada,
                   t.id_programa, COALESCE(p.nome, 'Sem programa') as programa_nome
            FROM termo_inspecao t
            INNER JOIN area_inspecionada a ON a.id_termo_inspecao = t.id
            LEFT JOIN propriedades pr ON t.id_propriedade = pr.id
            LEFT JOIN programas p ON t.id_programa = p.id
            WHERE (a.latitude IS NOT NULL OR pr.latitude IS NOT NULL)
              AND (a.longitude IS NOT NULL OR pr.longitude IS NOT NULL)";
        $add = []; $p2 = [];
        if ($ano !== '') { $add[] = "YEAR(COALESCE(t.data_inspecao, t.data_amostragem, t.criado_em)) = ?"; $p2[] = (int)$ano; }
        if ($trimestre > 0) { $add[] = "CEIL(MONTH(COALESCE(t.data_inspecao, t.data_amostragem, t.criado_em)) / 3) = ?"; $p2[] = $trimestre; }
        if ($id_programa !== '') { $add[] = "t.id_programa = ?"; $p2[] = $id_programa; }
        if ($municipio !== '') { $add[] = "TRIM(pr.municipio) = TRIM(?)"; $p2[] = $municipio; }
        if (!empty($add)) { $sql .= " AND " . implode(' AND ', $add); }
        $stmt = $db->prepare($sql);
        $stmt->execute($p2);
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
            if ($r['data_formatada']) $r['data_formatada'] = date('d/m/Y', strtotime($r['data_formatada']));
            $pontos[] = $r;
        }
    } catch (PDOException $e3) {}
    echo json_encode(['pontos' => $pontos]);
}
