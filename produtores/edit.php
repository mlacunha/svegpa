<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
$page_title = "Editar Produtor";

$database = new Database();
$db = $database->getConnection();

$id = isset($_GET['id']) ? sanitizeInput($_GET['id']) : null;

if (!$id) {
    header('Location: index.php');
    exit;
}

$stmt = $db->prepare("SELECT * FROM produtores WHERE id = :id");
$stmt->bindParam(':id', $id);
$stmt->execute();
$prod = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$prod) {
    header('Location: index.php');
    exit;
}

$val = function($key, $default = '') use ($prod) {
    return isset($_POST[$key]) ? $_POST[$key] : ($prod[$key] ?? $default);
};

$estados = $db->query("SELECT id, nome, sigla FROM estados WHERE id NOT IN (99, 99999) ORDER BY sigla")->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    
    if (empty($nome)) {
        $error = "O campo nome é obrigatório.";
    } else {
        $n_cadastro = !empty($_POST['n_cadastro']) ? sanitizeInput($_POST['n_cadastro']) : null;
        $cpf_cnpj = !empty($_POST['cpf_cnpj']) ? sanitizeInput($_POST['cpf_cnpj']) : null;
        $RG_IE = !empty($_POST['RG_IE']) ? sanitizeInput($_POST['RG_IE']) : null;
        $CEP = !empty($_POST['CEP']) ? sanitizeInput($_POST['CEP']) : null;
        $endereco = !empty($_POST['endereco']) ? sanitizeInput($_POST['endereco']) : null;
        $bairro = !empty($_POST['bairro']) ? sanitizeInput($_POST['bairro']) : null;
        $municipio = !empty($_POST['municipio']) ? sanitizeInput($_POST['municipio']) : null;
        $uf = !empty($_POST['uf']) ? sanitizeInput($_POST['uf']) : null;
        $telefone = !empty($_POST['telefone']) ? sanitizeInput($_POST['telefone']) : null;
        $email = !empty($_POST['email']) ? sanitizeInput($_POST['email']) : null;
        
        try {
            $stmt = $db->prepare("UPDATE produtores SET n_cadastro = :n_cadastro, cpf_cnpj = :cpf_cnpj, RG_IE = :RG_IE, nome = :nome, CEP = :CEP, endereco = :endereco, bairro = :bairro, municipio = :municipio, uf = :uf, telefone = :telefone, email = :email WHERE id = :id");
            
            $stmt->bindValue(':n_cadastro', $n_cadastro);
            $stmt->bindValue(':cpf_cnpj', $cpf_cnpj);
            $stmt->bindValue(':RG_IE', $RG_IE);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindValue(':CEP', $CEP);
            $stmt->bindValue(':endereco', $endereco);
            $stmt->bindValue(':bairro', $bairro);
            $stmt->bindValue(':municipio', $municipio);
            $stmt->bindValue(':uf', $uf);
            $stmt->bindValue(':telefone', $telefone);
            $stmt->bindValue(':email', $email);
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
            <i class="fas fa-edit mr-2"></i>Editar Produtor
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
        <h3 class="text-lg font-semibold text-gray-700 border-b pb-2">Identificação</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="form-group md:col-span-1">
                <label for="nome">Nome *</label>
                <input type="text" id="nome" name="nome" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($val('nome')); ?>" required>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 md:col-span-1">
                <div class="form-group">
                    <label for="n_cadastro">N. Cadastro</label>
                    <input type="text" id="n_cadastro" name="n_cadastro" class="form-control" maxlength="50" value="<?php echo htmlspecialchars($val('n_cadastro')); ?>">
                </div>
                <div class="form-group">
                    <label for="cpf_cnpj">CPF/CNPJ</label>
                    <input type="text" id="cpf_cnpj" name="cpf_cnpj" class="form-control mask-cpf-cnpj" data-mask="cpf-cnpj" maxlength="18" value="<?php echo htmlspecialchars($val('cpf_cnpj')); ?>">
                </div>
                <div class="form-group">
                    <label for="RG_IE">RG/IE</label>
                    <input type="text" id="RG_IE" name="RG_IE" class="form-control" maxlength="20" value="<?php echo htmlspecialchars($val('RG_IE')); ?>">
                </div>
            </div>
        </div>
        
        <h3 class="text-lg font-semibold text-gray-700 border-b pb-2">Endereço</h3>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
            <div class="form-group md:col-span-1">
                <label for="CEP">CEP</label>
                <input type="text" id="CEP" name="CEP" class="form-control mask-cep" data-mask="cep" maxlength="9" value="<?php echo htmlspecialchars($val('CEP')); ?>">
            </div>
            <div class="form-group md:col-span-4">
                <label for="endereco">Endereço</label>
                <input type="text" id="endereco" name="endereco" class="form-control" maxlength="200" value="<?php echo htmlspecialchars($val('endereco')); ?>">
            </div>
            <div class="form-group md:col-span-2">
                <label for="bairro">Bairro</label>
                <input type="text" id="bairro" name="bairro" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($val('bairro')); ?>">
            </div>
            <div class="form-group md:col-span-2">
                <label for="municipio">Município</label>
                <input type="text" id="municipio" name="municipio" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($val('municipio')); ?>">
            </div>
            <div class="form-group md:col-span-1">
                <label for="uf">UF</label>
                <select id="uf" name="uf" class="form-control">
                    <option value="">Selecione</option>
                    <?php foreach ($estados as $e): ?>
                    <option value="<?php echo htmlspecialchars($e['sigla']); ?>" <?php echo ($val('uf') === $e['sigla']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($e['sigla'] . ' - ' . $e['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <h3 class="text-lg font-semibold text-gray-700 border-b pb-2">Contato</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="form-group">
                <label for="telefone">Telefone</label>
                <input type="text" id="telefone" name="telefone" class="form-control" maxlength="20" value="<?php echo htmlspecialchars($val('telefone')); ?>">
            </div>
            <div class="form-group">
                <label for="email">E-mail</label>
                <input type="email" id="email" name="email" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($val('email')); ?>">
            </div>
        </div>
        
        <div class="flex justify-end space-x-3 pt-4">
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
