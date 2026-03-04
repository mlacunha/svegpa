<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
$page_title = "Editar Produto";
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? sanitizeInput($_GET['id']) : null;

if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM produtos WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$produto = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produto) {
    header('Location: index.php');
    exit;
}

$stmt = $db->query("SELECT id, nome, municipio, UF FROM propriedades ORDER BY nome ASC");
$propriedades = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_propriedade = sanitizeInput($_POST['id_propriedade'] ?? '');
    $produto_val = sanitizeInput($_POST['produto'] ?? '');
    
    if (empty($id_propriedade)) {
        $error = "Selecione uma propriedade.";
    } elseif (empty($produto_val)) {
        $error = "O campo produto é obrigatório.";
    } else {
        try {
            $stmt = $db->prepare("UPDATE produtos SET id_propriedade = :id_propriedade, produto = :produto WHERE id = :id");
            $stmt->bindParam(':id_propriedade', $id_propriedade);
            $stmt->bindParam(':produto', $produto_val);
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
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-edit mr-2"></i>Editar Produto
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
            <label for="id_propriedade">Propriedade *</label>
            <select id="id_propriedade" name="id_propriedade" class="form-control" required>
                <option value="">-- Selecione uma propriedade --</option>
                <?php foreach ($propriedades as $prop): ?>
                <option value="<?php echo htmlspecialchars($prop['id']); ?>" <?php echo (isset($_POST['id_propriedade']) ? $_POST['id_propriedade'] : $produto['id_propriedade']) == $prop['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($prop['nome']); ?>
                    <?php if (!empty($prop['municipio']) || !empty($prop['UF'])): ?>
                    (<?php echo htmlspecialchars(trim(($prop['municipio'] ?? '') . ' - ' . ($prop['UF'] ?? ''), ' -')); ?>)
                    <?php endif; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="produto">Produto *</label>
            <input type="text" id="produto" name="produto" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($_POST['produto'] ?? $produto['produto']); ?>" required>
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
