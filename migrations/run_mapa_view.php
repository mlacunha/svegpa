<?php
/**
 * Aplica migrações do mapa do dashboard: lat/lon em area_inspecionada + view.
 * Execute: php migrations/run_mapa_view.php
 */
require_once __DIR__ . '/../config/database.php';
header('Content-Type: text/plain; charset=utf-8');

$db = (new Database())->getConnection();
if (!$db) {
    die("Erro: conexão com banco falhou.\n");
}

echo "1. Verificando colunas latitude/longitude em area_inspecionada...\n";
$cols = $db->query("SHOW COLUMNS FROM area_inspecionada LIKE 'latitude'")->fetchAll(PDO::FETCH_COLUMN);
if (empty($cols)) {
    try {
        $db->exec("ALTER TABLE area_inspecionada ADD COLUMN latitude DECIMAL(10,8) NULL");
        $db->exec("ALTER TABLE area_inspecionada ADD COLUMN longitude DECIMAL(11,8) NULL");
        echo "   Colunas latitude e longitude adicionadas.\n";
    } catch (PDOException $e) {
        die("   Erro: " . $e->getMessage() . "\n");
    }
} else {
    echo "   Colunas já existem.\n";
}

echo "2. Recriando view vw_relatorio_mapa_dashboard...\n";
$sql = file_get_contents(__DIR__ . '/vw_relatorio_mapa_dashboard_union.sql');
$sql = preg_replace('/^--.*$/m', '', $sql);
$stmts = array_filter(array_map('trim', explode(';', $sql)));
foreach ($stmts as $stmt) {
    if (empty($stmt)) continue;
    try {
        $db->exec($stmt);
        if (stripos($stmt, 'CREATE VIEW') !== false) {
            echo "   View criada com sucesso.\n";
        }
    } catch (PDOException $e) {
        die("   Erro: " . $e->getMessage() . "\n");
    }
}

echo "3. Verificando dados no mapa...\n";
try {
    $stmt = $db->query("SELECT COUNT(*) as c FROM vw_relatorio_mapa_dashboard WHERE latitude IS NOT NULL AND longitude IS NOT NULL");
    $n = $stmt->fetch(PDO::FETCH_ASSOC)['c'];
    echo "   Registros com coordenadas: $n\n";
} catch (PDOException $e) {
    echo "   Aviso: " . $e->getMessage() . "\n";
}

echo "Concluído. Atualize o dashboard.\n";
