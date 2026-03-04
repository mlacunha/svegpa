<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
$page_title = "Editar Norma";

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? sanitizeInput($_GET['id']) : null;
if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM normas WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$norma = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$norma) {
    header('Location: index.php');
    exit;
}

$programas = $db->query("SELECT id, nome FROM programas ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);

$val = function($key, $default = '') use ($norma) {
    return isset($_POST[$key]) ? $_POST[$key] : ($norma[$key] ?? $default);
};

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_programa = sanitizeInput($_POST['id_programa'] ?? '');
    $nome_norma = sanitizeInput($_POST['nome_norma'] ?? '');
    $ementa = sanitizeInput($_POST['ementa'] ?? '');
    $url_publicacao = !empty($_POST['url_publicacao']) ? sanitizeInput($_POST['url_publicacao']) : null;
    
    if (empty($id_programa) || empty($nome_norma) || empty($ementa)) {
        $error = "Programa, Nome da Norma e Ementa são obrigatórios.";
    } else {
        try {
            $stmt = $db->prepare("UPDATE normas SET id_programa = :id_programa, nome_norma = :nome_norma, ementa = :ementa, url_publicacao = :url_publicacao WHERE id = :id");
            $stmt->bindParam(':id_programa', $id_programa);
            $stmt->bindParam(':nome_norma', $nome_norma);
            $stmt->bindParam(':ementa', $ementa);
            $stmt->bindValue(':url_publicacao', $url_publicacao);
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
            <i class="fas fa-edit mr-2"></i>Editar Norma
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
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="form-group">
                <label for="id_programa">Programa *</label>
                <select id="id_programa" name="id_programa" class="form-control" required>
                    <option value="">-- Selecione o programa --</option>
                    <?php foreach ($programas as $p): ?>
                    <option value="<?php echo htmlspecialchars($p['id']); ?>" <?php echo $val('id_programa') === $p['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($p['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="nome_norma">Nome da Norma *</label>
                <input type="text" id="nome_norma" name="nome_norma" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($val('nome_norma')); ?>" required>
            </div>
        </div>
        <div class="form-group">
            <label for="ementa">Ementa *</label>
            <textarea id="ementa" name="ementa" class="form-control" rows="4" required><?php echo htmlspecialchars($val('ementa')); ?></textarea>
        </div>
        <div class="form-group">
            <label for="url_publicacao">URL da Publicação</label>
            <input type="url" id="url_publicacao" name="url_publicacao" class="form-control" maxlength="512" value="<?php echo htmlspecialchars($val('url_publicacao')); ?>" placeholder="https://...">
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
