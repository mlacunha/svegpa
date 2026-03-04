<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
$page_title = "Produtos";
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

$stmt = $db->query("
    SELECT p.*, pr.nome as propriedade_nome, pr.municipio as propriedade_municipio, pr.UF
    FROM produtos p
    LEFT JOIN propriedades pr ON p.id_propriedade = pr.id
    ORDER BY p.produto ASC
");
$produtos = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Produtos</h2>
        <a href="create.php" class="btn-primary">
            <i class="fas fa-plus mr-2"></i>Novo Produto
        </a>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle mr-2"></i>
        <?php echo $_GET['success'] == 'created' ? 'Produto criado com sucesso!' : ($_GET['success'] == 'updated' ? 'Produto atualizado com sucesso!' : 'Produto excluído com sucesso!'); ?>
    </div>
    <?php endif; ?>
    
    <div class="mb-4">
        <input type="text" id="search" placeholder="Buscar produto..." class="form-group w-full">
    </div>
    
    <div class="table-container">
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr class="bg-gray-100">
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Produto</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Propriedade</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Município / UF</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Ações</th>
                </tr>
            </thead>
            <tbody id="produtos-table">
                <?php foreach ($produtos as $produto): ?>
                <tr class="border-b hover:bg-gray-50 produto-row" data-search="<?php echo strtolower(htmlspecialchars($produto['produto'] . ' ' . ($produto['propriedade_nome'] ?? '') . ' ' . ($produto['propriedade_municipio'] ?? '') . ' ' . ($produto['UF'] ?? ''))); ?>">
                    <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($produto['produto']); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($produto['propriedade_nome'] ?? '-'); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars(($produto['propriedade_municipio'] ?? '-') . ' / ' . ($produto['UF'] ?? '-')); ?></td>
                    <td class="py-3 px-4">
                        <a href="edit.php?id=<?php echo htmlspecialchars($produto['id']); ?>" class="text-blue-600 hover:text-blue-800 mr-3" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="delete.php?id=<?php echo htmlspecialchars($produto['id']); ?>" class="text-red-600 hover:text-red-800" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este produto?')">
                            <i class="fas fa-trash"></i>
                        </a>
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
    const rows = document.querySelectorAll('.produto-row');
    
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
