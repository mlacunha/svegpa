<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
$page_title = "Usuários";
requireAdmin();
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

$stmt = $db->query("
    SELECT u.*, o.nome as orgao_nome, o.sigla as orgao_sigla,
           c.nome as cargo_nome, c.sigla as cargo_sigla,
           un.nome as unidade_nome, f.nome as formacao_nome
    FROM sec_users u
    LEFT JOIN orgaos o ON u.orgao = o.id
    LEFT JOIN cargos c ON u.role = c.id
    LEFT JOIN unidades un ON u.unidade = un.id
    LEFT JOIN formacao f ON u.formacao = f.id
    ORDER BY COALESCE(NULLIF(u.name,''), u.login) ASC
");
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Usuários</h2>
        <a href="create.php" class="btn-primary">
            <i class="fas fa-plus mr-2"></i>Novo Usuário
        </a>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle mr-2"></i>
        <?php echo $_GET['success'] == 'created' ? 'Usuário criado com sucesso!' : ($_GET['success'] == 'updated' ? 'Usuário atualizado com sucesso!' : 'Usuário excluído com sucesso!'); ?>
    </div>
    <?php endif; ?>
    
    <div class="mb-4">
        <input type="text" id="search" placeholder="Buscar usuário (login, nome, email)..." class="form-control w-full">
    </div>
    
    <div class="table-container">
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr class="bg-gray-100">
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Login</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Nome</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Email</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Órgão</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Cargo</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Unidade</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Formação</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Ativo</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Ações</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($usuarios as $u): ?>
                <tr class="border-b hover:bg-gray-50 user-row" data-search="<?php echo strtolower(htmlspecialchars(($u['login'] ?? '') . ' ' . ($u['name'] ?? '') . ' ' . ($u['email'] ?? '') . ' ' . ($u['formacao_nome'] ?? ''))); ?>">
                    <td class="py-3 px-4 font-medium"><?php echo htmlspecialchars($u['login']); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($u['name'] ?? '-'); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($u['email'] ?? '-'); ?></td>
                    <td class="py-3 px-4"><?php echo !empty($u['orgao_sigla']) ? htmlspecialchars($u['orgao_sigla']) : '-'; ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($u['cargo_nome'] ?? $u['cargo_sigla'] ?? '-'); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($u['unidade_nome'] ?? '-'); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($u['formacao_nome'] ?? '-'); ?></td>
                    <td class="py-3 px-4"><?php echo ($u['active'] ?? '') === 'Y' ? 'Sim' : 'Não'; ?></td>
                    <td class="py-3 px-4">
                        <a href="edit.php?login=<?php echo urlencode($u['login']); ?>" class="text-blue-600 hover:text-blue-800 mr-3" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="delete.php?login=<?php echo urlencode($u['login']); ?>" class="text-red-600 hover:text-red-800" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este usuário?');">
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
    document.querySelectorAll('.user-row').forEach(row => {
        const searchText = row.getAttribute('data-search') || '';
        row.style.display = searchText.includes(searchTerm) ? '' : 'none';
    });
});
</script>

<?php include '../includes/footer.php'; ?>
