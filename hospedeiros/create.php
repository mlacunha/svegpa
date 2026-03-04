<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
$page_title = "Novo Hospedeiro";

$database = new Database();
$db = $database->getConnection();

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
            $stmt = $db->prepare("INSERT INTO hospedeiros (id, id_programa, nomes_comuns, nome_cientifico) VALUES (UUID(), :id_programa, :nomes_comuns, :nome_cientifico)");
            
            $stmt->bindParam(':id_programa', $id_programa);
            $stmt->bindParam(':nomes_comuns', $nomes_comuns);
            $stmt->bindParam(':nome_cientifico', $nome_cientifico);
            
            if ($stmt->execute()) {
                $return = $_POST['return_to'] ?? $_GET['return_to'] ?? null;
                if ($return && preg_match('#^\.\./programas/edit\.php\?id=[a-zA-Z0-9._-]+$#', $return)) {
                    header('Location: ' . $return . '&success=hospedeiro_created');
                } else {
                    header('Location: index.php?success=created');
                }
                exit;
            }
        } catch (PDOException $e) {
            $error = "Erro ao salvar: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-plus-circle mr-2"></i>Novo Hospedeiro
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
    
    <?php $return_to = isset($_GET['id_programa']) ? '../programas/edit.php?id=' . urlencode($_GET['id_programa']) : ''; ?>
    <form method="POST" class="space-y-6">
        <?php if ($return_to): ?><input type="hidden" name="return_to" value="<?php echo htmlspecialchars($return_to); ?>"><?php endif; ?>
        <div class="form-group">
            <label for="id_programa">Programa *</label>
            <select id="id_programa" name="id_programa" class="form-control" required>
                <option value="">-- Selecione um programa --</option>
                <?php $sel_prog = $_POST['id_programa'] ?? $_GET['id_programa'] ?? null; ?>
                <?php foreach ($programas as $programa): ?>
                <option value="<?php echo htmlspecialchars($programa['id']); ?>" <?php echo ($sel_prog && $sel_prog === $programa['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($programa['nome']); ?>
                    <?php if (!empty($programa['codigo'])): ?> (<?php echo htmlspecialchars($programa['codigo']); ?>)<?php endif; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="nomes_comuns">Nomes Comuns *</label>
            <input type="text" id="nomes_comuns" name="nomes_comuns" class="form-control" value="<?php echo htmlspecialchars($_POST['nomes_comuns'] ?? ''); ?>" required>
        </div>
        
        <div class="form-group">
            <label for="nome_cientifico">Nome Científico *</label>
            <input type="text" id="nome_cientifico" name="nome_cientifico" class="form-control" value="<?php echo htmlspecialchars($_POST['nome_cientifico'] ?? ''); ?>" required>
        </div>
        
        <div class="flex justify-end space-x-3">
            <button type="submit" class="btn-primary">
                <i class="fas fa-save mr-2"></i>Salvar
            </button>
            <a href="index.php" class="btn-secondary">
                <i class="fas fa-times mr-2"></i>Cancelar
            </a>
        </div>
    </form>
</div>

<?php include '../includes/footer.php'; ?>
