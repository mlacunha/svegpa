<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
$page_title = "Órgãos";
include '../includes/header.php';

$canEdit = isAdmin();
$database = new Database();
$db = $database->getConnection();

// Busca todos os órgãos com nome do tipo
$stmt = $db->query("
    SELECT o.*, ot.tipo as tipo_nome
    FROM orgaos o
    LEFT JOIN orgaos_tipos ot ON o.tipo = ot.id
    ORDER BY o.nome ASC
");
$orgaos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Órgãos</h2>
        <?php if ($canEdit): ?>
        <a href="create.php" class="btn-primary">
            <i class="fas fa-plus mr-2"></i>Novo Órgão
        </a>
        <?php else: ?>
        <span class="text-sm text-gray-500">Somente visualização</span>
        <?php endif; ?>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle mr-2"></i>
        <?php echo $_GET['success'] == 'created' ? 'Órgão criado com sucesso!' : ($_GET['success'] == 'updated' ? 'Órgão atualizado com sucesso!' : 'Órgão excluído com sucesso!'); ?>
    </div>
    <?php endif; ?>
    
    <div class="mb-4">
        <input type="text" id="search" placeholder="Buscar órgão..." class="form-group w-full">
    </div>
    
    <div class="table-container">
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr class="bg-gray-100">
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Sigla</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Nome</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Tipo</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">UF Sede</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Ações</th>
                </tr>
            </thead>
            <tbody id="orgaos-table">
                <?php foreach ($orgaos as $orgao): ?>
                <tr class="border-b hover:bg-gray-50 orgao-row" data-search="<?php echo strtolower(htmlspecialchars($orgao['sigla'] . ' ' . $orgao['nome'] . ' ' . ($orgao['tipo_nome'] ?? '') . ' ' . $orgao['UF_sede'])); ?>">
                    <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($orgao['sigla']); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($orgao['nome']); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($orgao['tipo_nome'] ?? '-'); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($orgao['UF_sede']); ?></td>
                    <td class="py-3 px-4">
                        <?php if ($canEdit): ?>
                        <a href="view.php?id=<?php echo htmlspecialchars($orgao['id']); ?>" class="text-gray-600 hover:text-gray-800 mr-3" title="Visualizar">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="edit.php?id=<?php echo htmlspecialchars($orgao['id']); ?>" class="text-blue-600 hover:text-blue-800 mr-3" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="delete.php?id=<?php echo htmlspecialchars($orgao['id']); ?>" class="text-red-600 hover:text-red-800" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este órgão?')">
                            <i class="fas fa-trash"></i>
                        </a>
                        <?php else: ?>
                        <a href="view.php?id=<?php echo htmlspecialchars($orgao['id']); ?>" class="text-gray-600 hover:text-gray-800" title="Visualizar">
                            <i class="fas fa-eye"></i>
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.getElementById('search').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('.orgao-row');
    
    rows.forEach(row => {
        const searchText = row.getAttribute('data-search');
        if (searchText && searchText.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
