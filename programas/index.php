<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
$page_title = "Programas";
include '../includes/header.php';

$database = new Database();
$db = $database->getConnection();

// Busca todos os programas
$stmt = $db->query("SELECT * FROM programas ORDER BY nome ASC");
$programas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="card">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Programas</h2>
        <a href="create.php" class="btn-primary">
            <i class="fas fa-plus mr-2"></i>Novo Programa
        </a>
    </div>
    
    <?php if (isset($_GET['success'])): ?>
    <div class="alert alert-success">
        <i class="fas fa-check-circle mr-2"></i>
        <?php echo $_GET['success'] == 'created' ? 'Programa criado com sucesso!' : ($_GET['success'] == 'updated' ? 'Programa atualizado com sucesso!' : 'Programa excluído com sucesso!'); ?>
    </div>
    <?php endif; ?>
    
    <div class="mb-4">
        <input type="text" id="search" placeholder="Buscar programa..." class="form-group w-full">
    </div>
    
    <div class="table-container">
        <table class="min-w-full bg-white border border-gray-200">
            <thead>
                <tr class="bg-gray-100">
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Código</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Nome</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Nomes Comuns</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Nome Científico</th>
                    <th class="py-3 px-4 text-left text-sm font-semibold text-gray-600">Ações</th>
                </tr>
            </thead>
            <tbody id="programas-table">
                <?php foreach ($programas as $programa): ?>
                <tr class="border-b hover:bg-gray-50 programa-row" data-nome="<?php echo strtolower($programa['nome']); ?>">
                    <td class="py-3 px-4"><?php echo htmlspecialchars($programa['codigo'] ?? '-'); ?></td>
                    <td class="py-3 px-4 font-medium">
                        <a href="edit.php?id=<?php echo htmlspecialchars($programa['id']); ?>" class="text-blue-600 hover:text-blue-800 hover:underline"><?php echo htmlspecialchars($programa['nome']); ?></a>
                    </td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($programa['nomes_comuns'] ?? '-'); ?></td>
                    <td class="py-3 px-4"><?php echo htmlspecialchars($programa['nome_cientifico'] ?? '-'); ?></td>
                    <td class="py-3 px-4">
                        <button type="button" class="inline-flex items-center justify-center px-2 py-1 rounded bg-gray-200 text-gray-700 hover:bg-gray-300 font-medium text-sm mr-3 btn-view-programa" data-id="<?php echo htmlspecialchars($programa['id']); ?>" title="Ver normas e hospedeiros">
                            Ver
                        </button>
                        <a href="edit.php?id=<?php echo $programa['id']; ?>" class="text-blue-600 hover:text-blue-800 mr-3" title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>
                        <a href="delete.php?id=<?php echo $programa['id']; ?>" class="text-red-600 hover:text-red-800" title="Excluir" onclick="return confirm('Tem certeza que deseja excluir este programa?')">
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
function escapeHtml(s) {
    if (!s) return '';
    var d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}

document.querySelectorAll('.btn-view-programa').forEach(function(btn) {
    btn.addEventListener('click', function() {
        var btn = this;
        var row = btn.closest('tr');
        var id = btn.getAttribute('data-id');
        var existing = document.querySelector('.programa-detail-row');
        if (existing) {
            existing.remove();
            if (existing.getAttribute('data-programa-id') === id) return;
        }
        var detailRow = document.createElement('tr');
        detailRow.className = 'programa-detail-row border-b bg-gray-50';
        detailRow.setAttribute('data-programa-id', id);
        detailRow.innerHTML = '<td colspan="5" class="p-0 align-top"><div class="p-4"><p class="text-gray-500">Carregando...</p></div></td>';
        row.parentNode.insertBefore(detailRow, row.nextSibling);
        btn.classList.add('opacity-50');
        fetch('view_data.php?id=' + encodeURIComponent(id))
            .then(function(r) { return r.json(); })
            .then(function(data) {
                btn.classList.remove('opacity-50');
                var html = '';
                if (data.error) {
                    html = '<p class="text-red-600 p-4">' + escapeHtml(data.error) + '</p>';
                } else {
                    html = '<div class="p-4 grid grid-cols-1 md:grid-cols-2 gap-6">';
                    html += '<div><h4 class="font-semibold text-gray-700 mb-2 pb-1 border-b"><i class="fas fa-balance-scale mr-2"></i>Normas vinculadas</h4>';
                    if (!data.normas || data.normas.length === 0) {
                        html += '<p class="text-gray-500 text-sm">Nenhuma norma vinculada.</p>';
                    } else {
                        html += '<table class="min-w-full text-sm"><thead><tr class="border-b"><th class="text-left py-2 font-medium text-gray-600">Norma</th><th class="text-left py-2 font-medium text-gray-600">Ementa</th></tr></thead><tbody>';
                        data.normas.forEach(function(n) {
                            html += '<tr class="border-b border-gray-100"><td class="py-2">' + escapeHtml(n.nome_norma);
                            if (n.url_publicacao) html += ' <a href="' + escapeHtml(n.url_publicacao) + '" target="_blank" rel="noopener" class="text-blue-600 text-xs"><i class="fas fa-external-link-alt"></i></a>';
                            html += '</td><td class="py-2 text-gray-600">' + escapeHtml((n.ementa || '').substring(0, 80)) + ((n.ementa || '').length > 80 ? '...' : '') + '</td></tr>';
                        });
                        html += '</tbody></table>';
                    }
                    html += '</div>';
                    html += '<div><h4 class="font-semibold text-gray-700 mb-2 pb-1 border-b"><i class="fas fa-tree mr-2"></i>Espécies hospedeiras</h4>';
                    if (!data.hospedeiros || data.hospedeiros.length === 0) {
                        html += '<p class="text-gray-500 text-sm">Nenhuma espécie hospedeira vinculada.</p>';
                    } else {
                        html += '<table class="min-w-full text-sm"><thead><tr class="border-b"><th class="text-left py-2 font-medium text-gray-600">Nomes comuns</th><th class="text-left py-2 font-medium text-gray-600">Nome científico</th></tr></thead><tbody>';
                        data.hospedeiros.forEach(function(h) {
                            html += '<tr class="border-b border-gray-100"><td class="py-2">' + escapeHtml(h.nomes_comuns || '-') + '</td><td class="py-2 text-gray-600 italic">' + escapeHtml(h.nome_cientifico || '-') + '</td></tr>';
                        });
                        html += '</tbody></table>';
                    }
                    html += '</div></div>';
                }
                detailRow.querySelector('td').innerHTML = html;
            })
            .catch(function() {
                btn.classList.remove('opacity-50');
                detailRow.querySelector('td').innerHTML = '<div class="p-4"><p class="text-red-600">Erro ao carregar dados.</p></div>';
            });
    });
});

document.getElementById('search').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('.programa-row');
    
    rows.forEach(row => {
        const nome = row.getAttribute('data-nome');
        const visible = nome.includes(searchTerm);
        row.style.display = visible ? '' : 'none';
        var next = row.nextElementSibling;
        if (next && next.classList.contains('programa-detail-row')) {
            next.style.display = visible ? '' : 'none';
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>