<?php
require_once '../config/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$id = isset($_GET['id']) ? sanitizeInput($_GET['id']) : null;
if (!$id) {
    echo json_encode(['error' => 'ID não informado']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT id, nome, codigo FROM programas WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$programa = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$programa) {
    echo json_encode(['error' => 'Programa não encontrado']);
    exit;
}

$stmt = $db->prepare("SELECT id, nome_norma, ementa, url_publicacao FROM normas WHERE id_programa = :id ORDER BY criado_em DESC");
$stmt->bindParam(':id', $id);
$stmt->execute();
$normas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT id, nomes_comuns, nome_cientifico FROM hospedeiros WHERE id_programa = :id ORDER BY nomes_comuns ASC");
$stmt->bindParam(':id', $id);
$stmt->execute();
$hospedeiros = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode([
    'programa' => $programa,
    'normas' => $normas,
    'hospedeiros' => $hospedeiros
]);
