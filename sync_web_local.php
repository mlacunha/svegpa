<?php
/**
 * Sincroniza produtores, propriedades, termo_inspecao, area_inspecionada
 * entre o banco local (sveg @ localhost) e o banco web (produção).
 *
 * Uso: php sync_web_local.php [direção]
 *   local2web (padrão): local -> web (coleta feita no campo sobe para o servidor)
 *   web2local: web -> local
 *
 * Local: localhost:3306, sveg, root, sem senha
 * Web: config/database.php (209.50.227.136, sanveg)
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/database_local.php';

$direction = strtolower($argv[1] ?? 'local2web');
$web2local = ($direction === 'web2local' || $direction === 'web2');

$webDb = (new Database())->getConnection();
$localDb = (new DatabaseLocal())->getConnection();

if (!$webDb || !$localDb) {
    fwrite(STDERR, "Erro: não foi possível conectar a um ou ambos os bancos.\n");
    exit(1);
}

$source = $web2local ? $webDb : $localDb;
$target = $web2local ? $localDb : $webDb;
$srcLabel = $web2local ? 'web' : 'local';
$tgtLabel = $web2local ? 'local' : 'web';

$tables = ['produtores', 'propriedades', 'termo_inspecao', 'area_inspecionada'];
$triggers = [
    'area_inspecionada' => 'before_insert_area_inspecionada',
];

echo "=== Sincronização SVEG ({$srcLabel} -> {$tgtLabel}) ===\n\n";

foreach ($tables as $table) {
    echo "Tabela: {$table} ... ";
    try {
        $colsSrc = $source->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        $colsTgt = $target->query("SHOW COLUMNS FROM `{$table}`")->fetchAll(PDO::FETCH_ASSOC);
        $colsSrcNames = array_flip(array_column($colsSrc, 'Field'));
        $colsTgtOrder = array_column($colsTgt, 'Field');
        $colNames = array_values(array_filter($colsTgtOrder, function ($c) use ($colsSrcNames) {
            return isset($colsSrcNames[$c]);
        }));
        if (empty($colNames)) {
            echo "Nenhuma coluna comum (esquemas diferentes).\n";
            continue;
        }
        $colList = '`' . implode('`,`', $colNames) . '`';
        $placeholders = implode(',', array_fill(0, count($colNames), '?'));
        $updates = [];
        foreach ($colNames as $c) {
            $updates[] = "`{$c}` = VALUES(`{$c}`)";
        }
        $updateClause = implode(', ', $updates);

        if (isset($triggers[$table])) {
            $target->exec("DROP TRIGGER IF EXISTS `{$triggers[$table]}`");
        }

        $rows = $source->query("SELECT {$colList} FROM `{$table}`")->fetchAll(PDO::FETCH_NUM);
        $stmt = $target->prepare("INSERT INTO `{$table}` ({$colList}) VALUES ({$placeholders}) ON DUPLICATE KEY UPDATE {$updateClause}");

        $colTypes = [];
        foreach ($colsSrc as $c) {
            if (in_array($c['Field'], $colNames)) {
                $t = strtolower($c['Type']);
                $colTypes[$c['Field']] = (strpos($t, 'int') !== false && strpos($t, 'decimal') === false && strpos($t, 'point') === false) ? 'int' : 'str';
            }
        }
        $colTypesOrdered = [];
        foreach ($colNames as $cn) {
            $colTypesOrdered[] = $colTypes[$cn] ?? 'str';
        }

        $count = 0;
        foreach ($rows as $row) {
            $rowTyped = [];
            foreach ($colNames as $i => $col) {
                $val = $row[$i] ?? null;
                if ($val === null) {
                    $rowTyped[] = null;
                } elseif (($colTypesOrdered[$i] ?? 'str') === 'int' && is_numeric($val)) {
                    $rowTyped[] = (int) $val;
                } else {
                    $rowTyped[] = $val;
                }
            }
            $stmt->execute($rowTyped);
            $count++;
        }

        if (isset($triggers[$table])) {
            $target->exec("CREATE TRIGGER `{$triggers[$table]}` BEFORE INSERT ON `{$table}` FOR EACH ROW BEGIN IF NEW.id IS NULL OR TRIM(NEW.id)='' THEN SET NEW.id=UUID(); END IF; END");
        }

        echo "{$count} registros.\n";
    } catch (PDOException $e) {
        echo "ERRO: " . $e->getMessage() . "\n";
    }
}

echo "\nConcluído.\n";
