<?php
session_start();
header('Content-Type: application/json');
require_once '../config/database.php';
require_once '../includes/functions.php';

$input = json_decode((string)file_get_contents('php://input'), true) ?: [];
$id_termo = $input['id_termo_inspecao'] ?? $_POST['id_termo_inspecao'] ?? null;

if (!$id_termo) {
    http_response_code(400);
    echo json_encode(['error' => 'id_termo_inspecao obrigatório']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("SELECT id_usuario, termo_coleta, data_amostragem FROM termo_inspecao WHERE id = ?");
    $stmt->execute([$id_termo]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) {
        http_response_code(404);
        echo json_encode(['error' => 'Termo não encontrado']);
        exit;
    }

    $updates = ["data_amostragem = CURDATE()"];
    $params = [];

    if (empty(trim($row['termo_coleta'] ?? ''))) {
        $id_usuario = $row['id_usuario'] ?? null;
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

    $stmt = $db->prepare("SELECT data_amostragem, termo_coleta FROM termo_inspecao WHERE id = ?");
    $stmt->execute([$id_termo]);
    $updated = $stmt->fetch(PDO::FETCH_ASSOC);

    echo json_encode(['ok' => true, 'data_amostragem' => $updated['data_amostragem'] ?? null, 'termo_coleta' => $updated['termo_coleta'] ?? null]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
