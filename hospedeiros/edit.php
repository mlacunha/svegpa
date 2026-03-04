<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
$page_title = "Editar Hospedeiro";

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? sanitizeInput($_GET['id']) : null;

if (!$id) {
    header('Location: index.php');
    exit;
}

// Buscar hospedeiro
$stmt = $db->prepare("SELECT * FROM hospedeiros WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$hospedeiro = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$hospedeiro) {
    header('Location: index.php');
    exit;
}

// Buscar programas para o select
$stmt = $db->query("SELECT id, nome, codigo FROM programas ORDER BY nome ASC");
$programas = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_programa = sanitizeInput($_POST['id_programa'] ?? '');
    $nomes_comuns = sanitizeInput($_POST['nomes_comuns'] ?? '');
    $nome_cientifico = sanitizeInput($_POST['nome_cientifico'] ?? '');
    
    if (empty($id_programa)) {
        $error = "Selecione um programa.";
    } elseif (empty($nomes_comuns) || empty($nome_cientifico)) {
        $error = "Nomes comuns e nome científico são obrigatórios.";
    } else {
        try {
            $stmt = $db->prepare("UPDATE hospedeiros SET id_programa = :id_programa, nomes_comuns = :nomes_comuns, nome_cientifico = :nome_cientifico WHERE id = :id");
            
            $stmt->bindParam(':id_programa', $id_programa);
            $stmt->bindParam(':nomes_comuns', $nomes_comuns);
            $stmt->bindParam(':nome_cientifico', $nome_cientifico);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                header('Location: index.php?success=updated');
                exit;
            }
        } catch (PDOException $e) {
            $error = "Erro ao atualizar: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-edit mr-2"></i>Editar Hospedeiro
        </h2>
        <a href="index.php" class="btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Voltar
        </a>
    </div>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-error">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" class="space-y-6">
        <div class="form-group">
            <label for="id_programa">Programa *</label>
            <select id="id_programa" name="id_programa" class="form-control" required>
                <option value="">-- Selecione um programa --</option>
                <?php foreach ($programas as $programa): ?>
                <option value="<?php echo htmlspecialchars($programa['id']); ?>" <?php echo (isset($_POST['id_programa']) ? $_POST['id_programa'] : $hospedeiro['id_programa']) == $programa['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($programa['nome']); ?>
                    <?php if (!empty($programa['codigo'])): ?> (<?php echo htmlspecialchars($programa['codigo']); ?>)<?php endif; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="nomes_comuns">Nomes Comuns *</label>
            <input type="text" id="nomes_comuns" name="nomes_comuns" class="form-control" value="<?php echo htmlspecialchars($_POST['nomes_comuns'] ?? $hospedeiro['nomes_comuns']); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="nome_cientifico">Nome Científico *</label>
            <input type="text" id="nome_cientifico" name="nome_cientifico" class="form-control" value="<?php echo htmlspecialchars($_POST['nome_cientifico'] ?? $hospedeiro['nome_cientifico']); ?>" required>
        </div>
        
        <div class="flex justify-end space-x-3">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i>Atualizar
            </button>
            <a href="index.php" class="btn-secondary">
                <i class="fas fa-times mr-2"></i>Cancelar
            </a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
