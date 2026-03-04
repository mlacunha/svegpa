<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

$id_termo = isset($_POST['id_termo_inspecao']) ? sanitizeInput($_POST['id_termo_inspecao']) : null;
$area_id = isset($_POST['area_id']) && $_POST['area_id'] !== '' ? trim(sanitizeInput($_POST['area_id'])) : null;
$is_edit = $area_id !== null && $area_id !== '';

if (!$id_termo) {
    header('Location: index.php');
    exit;
}

$database = new Database();
$db = $database->getConnection();

$stmt_termo = $db->prepare("SELECT id_programa FROM termo_inspecao WHERE id = ?");
$stmt_termo->execute([$id_termo]);
$termo_row = $stmt_termo->fetch(PDO::FETCH_ASSOC);
$id_programa = !empty($termo_row['id_programa']) ? trim($termo_row['id_programa']) : null;

// Fallback: usar id_programa do formulário (quando termo não foi salvo após alterar o programa, ou termo antigo sem id_programa)
if (!$id_programa && !empty($_POST['id_programa'])) {
    $id_programa = trim(sanitizeInput($_POST['id_programa']));
    if ($id_programa) {
        $db->prepare("UPDATE termo_inspecao SET id_programa = ? WHERE id = ?")->execute([$id_programa, $id_termo]);
    }
}

$numero_plantas_raw = $_POST['numero_plantas'] ?? '';
$numero_plantas = (isset($_POST['numero_plantas']) && $numero_plantas_raw !== '' && $numero_plantas_raw !== null) ? (int)$numero_plantas_raw : null;
$numero_inspecionadas_raw = $_POST['numero_inspecionadas'] ?? '';
$numero_inspecionadas = (isset($_POST['numero_inspecionadas']) && $numero_inspecionadas_raw !== '' && $numero_inspecionadas_raw !== null) ? (int)$numero_inspecionadas_raw : null;

$latitude_raw = isset($_POST['latitude']) ? trim($_POST['latitude']) : '';
$longitude_raw = isset($_POST['longitude']) ? trim($_POST['longitude']) : '';

$missing = [];
if (!$id_programa) $missing[] = 'programa (defina o Programa no termo de inspeção)';
if ($numero_plantas === null) $missing[] = 'numero_plantas';
if ($numero_inspecionadas === null) $missing[] = 'numero_inspecionadas';
if ($latitude_raw === '' || $longitude_raw === '') $missing[] = 'coordenadas';

if ($missing) {
    $q = 'edit.php?id=' . urlencode($id_termo) . '&error=area&missing=' . urlencode(implode(',', $missing));
    header('Location: ' . $q);
    exit;
}

$tipo_area = !empty($_POST['tipo_area']) ? sanitizeInput($_POST['tipo_area']) : null;
$nome_local = !empty($_POST['nome_local']) ? sanitizeInput($_POST['nome_local']) : null;
$especie = !empty($_POST['especie']) ? sanitizeInput($_POST['especie']) : null;
$variedade = !empty($_POST['variedade']) ? sanitizeInput($_POST['variedade']) : null;
$material_multiplicacao = !empty($_POST['material_multiplicacao']) ? sanitizeInput($_POST['material_multiplicacao']) : null;
$origem = !empty($_POST['origem']) ? sanitizeInput($_POST['origem']) : null;
$idade_plantio = isset($_POST['idade_plantio']) && $_POST['idade_plantio'] !== '' ? floatval(str_replace(',', '.', $_POST['idade_plantio'])) : null;
$area_plantada = isset($_POST['area_plantada']) && $_POST['area_plantada'] !== '' ? floatval(str_replace(',', '.', $_POST['area_plantada'])) : null;
$numero_suspeitas = isset($_POST['numero_suspeitas']) && $_POST['numero_suspeitas'] !== '' ? (int)$_POST['numero_suspeitas'] : null;
$coletar_mostra = isset($_POST['coletar_mostra']) && $_POST['coletar_mostra'] === '1' ? 1 : 0;
$obs = !empty($_POST['obs']) ? sanitizeInput($_POST['obs']) : '';
$identificacao_amostra = !empty($_POST['identificacao_amostra']) ? sanitizeInput($_POST['identificacao_amostra']) : null;
$resultado = $coletar_mostra ? 'Suspeita' : 'Normal';
$associado = !empty($_POST['associado']) ? sanitizeInput($_POST['associado']) : null;
$latitude = floatval(str_replace(',', '.', $latitude_raw));
$longitude = floatval(str_replace(',', '.', $longitude_raw));
$partes_bool = ['raiz'=>0,'caule'=>0,'peciolo'=>0,'folha'=>0,'flor'=>0,'fruto'=>0,'semente'=>0];
foreach (array_keys($partes_bool) as $fn) {
    $partes_bool[$fn] = isset($_POST[$fn]) && $_POST[$fn] === '1' ? 1 : 0;
}

if ($especie && $id_programa) {
    $stmt_h = $db->prepare("SELECT 1 FROM hospedeiros WHERE id_programa = ? AND nome_cientifico = ?");
    $stmt_h->execute([$id_programa, $especie]);
    if (!$stmt_h->fetch()) {
        header('Location: edit.php?id=' . urlencode($id_termo) . '&error=area&missing=especie');
        exit;
    }
}

if ($coletar_mostra) {
    $ident_ok = !empty(trim($_POST['identificacao_amostra'] ?? ''));
    $partes_ok = array_sum($partes_bool) > 0;
    $data_amostragem_ok = !empty(trim($_POST['data_amostragem'] ?? ''));
    if (!$ident_ok || !$partes_ok || !$data_amostragem_ok) {
        $err_parts = [];
        if (!$ident_ok) $err_parts[] = 'identificacao_amostra';
        if (!$partes_ok) $err_parts[] = 'partes_coletadas';
        if (!$data_amostragem_ok) $err_parts[] = 'data_amostragem';
        header('Location: edit.php?id=' . urlencode($id_termo) . '&error=area&missing=' . urlencode(implode(',', $err_parts)));
        exit;
    }
}

try {
    if ($is_edit) {
        $stmt = $db->prepare("UPDATE area_inspecionada SET tipo_area=:tipo_area,nome_local=:nome_local,especie=:especie,variedade=:variedade,material_multiplicacao=:material_multiplicacao,origem=:origem,idade_plantio=:idade_plantio,area_plantada=:area_plantada,numero_plantas=:numero_plantas,numero_inspecionadas=:numero_inspecionadas,numero_suspeitas=:numero_suspeitas,coletar_mostra=:coletar_mostra,obs=:obs,identificacao_amostra=:identificacao_amostra,resultado=:resultado,associado=:associado,latitude=:latitude,longitude=:longitude,raiz=:raiz,caule=:caule,peciolo=:peciolo,folha=:folha,flor=:flor,fruto=:fruto,semente=:semente WHERE id_termo_inspecao=:id_termo AND id=:aid");
        $stmt->bindValue(':tipo_area', $tipo_area);
        $stmt->bindValue(':nome_local', $nome_local);
        $stmt->bindValue(':especie', $especie);
        $stmt->bindValue(':variedade', $variedade);
        $stmt->bindValue(':material_multiplicacao', $material_multiplicacao);
        $stmt->bindValue(':origem', $origem);
        $stmt->bindValue(':idade_plantio', $idade_plantio);
        $stmt->bindValue(':area_plantada', $area_plantada);
        $stmt->bindValue(':numero_plantas', $numero_plantas);
        $stmt->bindValue(':numero_inspecionadas', $numero_inspecionadas);
        $stmt->bindValue(':numero_suspeitas', $numero_suspeitas);
        $stmt->bindValue(':coletar_mostra', $coletar_mostra);
        $stmt->bindValue(':obs', $obs);
        $stmt->bindValue(':identificacao_amostra', $identificacao_amostra);
        $stmt->bindValue(':resultado', $resultado);
        $stmt->bindValue(':associado', $associado);
        $stmt->bindValue(':latitude', $latitude);
        $stmt->bindValue(':longitude', $longitude);
        foreach ($partes_bool as $k => $v) { $stmt->bindValue(':'.$k, $v); }
        $stmt->bindValue(':id_termo', $id_termo);
        $stmt->bindValue(':aid', $area_id);
        $stmt->execute();
    } else {
        $area_uuid = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
        $stmt = $db->prepare("INSERT INTO area_inspecionada (id,id_termo_inspecao,tipo_area,nome_local,especie,variedade,material_multiplicacao,origem,idade_plantio,area_plantada,numero_plantas,numero_inspecionadas,numero_suspeitas,coletar_mostra,obs,identificacao_amostra,resultado,associado,latitude,longitude,raiz,caule,peciolo,folha,flor,fruto,semente) VALUES (:id,:id_termo,:tipo_area,:nome_local,:especie,:variedade,:material_multiplicacao,:origem,:idade_plantio,:area_plantada,:numero_plantas,:numero_inspecionadas,:numero_suspeitas,:coletar_mostra,:obs,:identificacao_amostra,:resultado,:associado,:latitude,:longitude,:raiz,:caule,:peciolo,:folha,:flor,:fruto,:semente)");
        $stmt->bindValue(':id', $area_uuid);
        $stmt->bindValue(':id_termo', $id_termo);
        $stmt->bindValue(':tipo_area', $tipo_area);
        $stmt->bindValue(':nome_local', $nome_local);
        $stmt->bindValue(':especie', $especie);
        $stmt->bindValue(':variedade', $variedade);
        $stmt->bindValue(':material_multiplicacao', $material_multiplicacao);
        $stmt->bindValue(':origem', $origem);
        $stmt->bindValue(':idade_plantio', $idade_plantio);
        $stmt->bindValue(':area_plantada', $area_plantada);
        $stmt->bindValue(':numero_plantas', $numero_plantas);
        $stmt->bindValue(':numero_inspecionadas', $numero_inspecionadas);
        $stmt->bindValue(':numero_suspeitas', $numero_suspeitas ?? 0);
        $stmt->bindValue(':coletar_mostra', $coletar_mostra);
        $stmt->bindValue(':obs', $obs);
        $stmt->bindValue(':identificacao_amostra', $identificacao_amostra);
        $stmt->bindValue(':resultado', $resultado);
        $stmt->bindValue(':associado', $associado);
        $stmt->bindValue(':latitude', $latitude);
        $stmt->bindValue(':longitude', $longitude);
        foreach ($partes_bool as $k => $v) { $stmt->bindValue(':'.$k, $v); }
        $stmt->execute();
    }

    if ($coletar_mostra) {
        $t = $db->prepare("SELECT id_usuario, termo_coleta FROM termo_inspecao WHERE id = ?");
        $t->execute([$id_termo]);
        $termoRow = $t->fetch(PDO::FETCH_ASSOC);
        if ($termoRow) {
            $data_amostragem = trim($_POST['data_amostragem'] ?? '');
            $updates = ["data_amostragem = " . ($data_amostragem ? $db->quote($data_amostragem) : 'CURDATE()')];
            $params = [];
            if (empty(trim($termoRow['termo_coleta'] ?? ''))) {
                $id_usuario = $termoRow['id_usuario'] ?? null;
                if ($id_usuario) {
                    $matStmt = $db->prepare("SELECT COALESCE(NULLIF(TRIM(matricula),''), login) as matricula FROM sec_users WHERE login = ?");
                    $matStmt->execute([$id_usuario]);
                    $matricula = $matStmt->fetchColumn() ?: $id_usuario;
                    $ano = date('Y');
                    $db->prepare("INSERT INTO controle_sequencial (login, ano, seq_ti, seq_tc) VALUES (?, ?, 0, 0) ON DUPLICATE KEY UPDATE login = login")
                        ->execute([$id_usuario, $ano]);
                    $db->prepare("UPDATE controle_sequencial SET seq_tc = seq_tc + 1 WHERE login = ? AND ano = ?")
                        ->execute([$id_usuario, $ano]);
                    $seqStmt = $db->prepare("SELECT seq_tc FROM controle_sequencial WHERE login = ? AND ano = ?");
                    $seqStmt->execute([$id_usuario, $ano]);
                    $seq_tc = (int)$seqStmt->fetchColumn();
                    $termo_coleta_val = $seq_tc . '/' . $matricula . '/' . $ano;
                    $updates[] = "termo_coleta = ?";
                    $params[] = $termo_coleta_val;
                }
            }
            $params[] = $id_termo;
            $db->prepare("UPDATE termo_inspecao SET " . implode(', ', $updates) . " WHERE id = ?")->execute($params);
        }
    }
} catch (PDOException $e) {
    header('Location: edit.php?id=' . urlencode($id_termo) . '&error=area&msg=' . urlencode($e->getMessage()));
    exit;
}

$redirect = 'edit.php?id=' . urlencode($id_termo) . '&success=area_saved';
header('Location: ' . $redirect);
exit;
