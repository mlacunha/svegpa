<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
$page_title = "Unidades";
include '../includes/header.php';

$canEdit = isAdmin();
$database = new Database();
$db = $database->getConnection();

$stmt = $db->query("
    SELECT u.*, o.nome as orgao_nome, o.sigla as orgao_sigla, m.nome as municipio_nome
    FROM unidades u
    LEFT JOIN orgaos o ON u.orgao = o.id
    LEFT JOIN municipios m ON CAST(m.id AS CHAR) COLLATE utf8mb4_bin = u.municipio COLLATE utf8mb4_bin
    ORDER BY u.nome ASC
");
$unidades = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Unidades</h2>
        <?php if ($canEdit): ?>
        <a href="create.php" class="btn-primary">
            <i class="fas fa-plus mr-2"></i>Nova Unidade
        </a>
        <?php else: ?>
        <span class="text-sm text-gray-500">Somente visualização</span>
        <?php endif; ?>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle mr-2"></i>
        <?php echo $_GET['success'] == 'created' ? 'Unidade criada com sucesso!' : ($_GET['success'] == 'updated' ? 'Unidade atualizada com sucesso!' : 'Unidade excluída com sucesso!'); ?>
    </div>
    <?php endif; ?>
    
    <div class="mb-4">
        <input type="text" id="search" placeholder="Buscar unidade..." class="form-control w-full">
    </div>
    
    <div class="table-container">
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr class="bg-gray-100">
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Nome</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Órgão</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">UF</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Município</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Ações</th>
                </tr>
            </thead>
            <tbody id="unidades-table">
                <?php foreach ($unidades as $un): ?>
                <tr class="border-b hover:bg-gray-50 unidade-row" data-search="<?php echo strtolower(htmlspecialchars(($un['nome'] ?? '') . ' ' . ($un['orgao_nome'] ?? '') . ' ' . ($un['orgao_sigla'] ?? '') . ' ' . ($un['municipio_nome'] ?? $un['municipio'] ?? '') . ' ' . ($un['uf'] ?? ''))); ?>">
                    <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($un['nome'] ?? '-'); ?></td>
                    <td class="py-3 px-4">
                        <span class="font-medium"><?php echo htmlspecialchars($un['orgao_nome'] ?? '-'); ?></span>
                        <?php if (!empty($un['orgao_sigla'])): ?>
                        <span class="text-gray-500">(<?php echo htmlspecialchars($un['orgao_sigla']); ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($un['uf'] ?? '-'); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($un['municipio_nome'] ?? $un['municipio'] ?? '-'); ?></td>
                    <td class="py-3 px-4">
                        <?php if ($canEdit): ?>
                        <a href="view.php?id=<?php echo htmlspecialchars($un['id']); ?>" class="text-gray-600 hover:text-gray-800 mr-3" title="Visualizar">
                            <i class="fas fa-eye"></i>
                        </a>
                        <a href="edit.php?id=<?php echo htmlspecialchars($un['id']); ?>" class="text-blue-600 hover:text-blue-800 mr-3" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="delete.php?id=<?php echo htmlspecialchars($un['id']); ?>" class="text-red-600 hover:text-red-800" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir esta unidade?')">
                            <i class="fas fa-trash"></i>
                        </a>
                        <?php else: ?>
                        <a href="view.php?id=<?php echo htmlspecialchars($un['id']); ?>" class="text-gray-600 hover:text-gray-800" title="Visualizar">
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
    const rows = document.querySelectorAll('.unidade-row');
    rows.forEach(row => {
        const searchText = row.getAttribute('data-search');
        row.style.display = (searchText && searchText.includes(searchTerm)) ? '' : 'none';
    });
});
</script>

<?php include '../includes/footer.php'; ?>
