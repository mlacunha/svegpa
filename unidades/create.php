<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
$page_title = "Nova Unidade";
if (!isAdmin()) { header('Location: index.php?msg=acesso_negado'); exit; }

$database = new Database();
$db = $database->getConnection();

$orgaos = $db->query("SELECT id, nome, sigla FROM orgaos ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$estados = $db->query("SELECT id, nome, sigla FROM estados ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$municipios = $db->query("SELECT m.id, m.nome, m.estado_id, e.sigla FROM municipios m JOIN estados e ON m.estado_id = e.id ORDER BY e.sigla, m.nome ASC")->fetchAll(PDO::FETCH_ASSOC);

$error = null;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $orgao = sanitizeInput($_POST['orgao'] ?? '');
    $nome = sanitizeInput($_POST['nome'] ?? '');
    $municipio = isset($_POST['municipio']) && $_POST['municipio'] !== '' ? trim(sanitizeInput($_POST['municipio'])) : '';
    $uf = isset($_POST['uf']) && $_POST['uf'] !== '' ? trim(sanitizeInput($_POST['uf'])) : '';
    
    if (empty($orgao)) {
        $error = "Selecione um órgão.";
    } elseif (empty($nome)) {
        $error = "O campo nome é obrigatório.";
    } else {
        try {
            $stmt = $db->prepare("INSERT INTO unidades (id, orgao, nome, municipio, uf) VALUES (UUID(), :orgao, :nome, :municipio, :uf)");
            $stmt->execute([
                ':orgao' => $orgao,
                ':nome' => $nome,
                ':municipio' => $municipio !== '' ? (string)$municipio : null,
                ':uf' => $uf ?: null,
            ]);
            header('Location: index.php?success=created');
            exit;
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
            <i class="fas fa-plus-circle mr-2"></i>Nova Unidade
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
            <label for="orgao">Órgão *</label>
            <select id="orgao" name="orgao" class="form-control" required>
                <option value="">-- Selecione o órgão --</option>
                <?php foreach ($orgaos as $org): ?>
                <option value="<?php echo htmlspecialchars($org['id']); ?>" <?php echo (isset($_POST['orgao']) && $_POST['orgao'] === $org['id']) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($org['nome']); ?>
                    <?php if (!empty($org['sigla'])): ?> (<?php echo htmlspecialchars($org['sigla']); ?>)<?php endif; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label for="nome">Nome *</label>
            <input type="text" id="nome" name="nome" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="form-group">
                <label for="uf">UF</label>
                <select id="uf" name="uf" class="form-control">
                    <option value="">-- Selecione a UF --</option>
                    <?php foreach ($estados as $est): ?>
                    <option value="<?php echo htmlspecialchars($est['sigla']); ?>" data-estado-id="<?php echo (int)$est['id']; ?>" <?php echo (isset($_POST['uf']) && $_POST['uf'] === $est['sigla']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($est['sigla']); ?> - <?php echo htmlspecialchars($est['nome']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="municipio">Município</label>
                <div class="relative" id="municipio-combobox">
                    <input type="text" id="municipio-search" class="form-control" placeholder="Digite para buscar..." autocomplete="off">
                    <input type="hidden" id="municipio" name="municipio" value="<?php echo htmlspecialchars($_POST['municipio'] ?? ''); ?>">
                    <ul id="municipio-list" class="absolute z-10 mt-1 w-full bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-auto hidden"></ul>
                </div>
            </div>
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

<script>
(function() {
    const municipios = <?php echo json_encode($municipios); ?>;
    const ufSelect = document.getElementById('uf');
    const municipioSearch = document.getElementById('municipio-search');
    const municipioHidden = document.getElementById('municipio');
    const municipioList = document.getElementById('municipio-list');

    function getEstadoIdBySigla(sigla) {
        for (let i = 0; i < ufSelect.options.length; i++) {
            if (ufSelect.options[i].value === sigla) {
                return parseInt(ufSelect.options[i].getAttribute('data-estado-id') || 0, 10) || null;
            }
        }
        return null;
    }

    function renderMunicipios(estadoId, query) {
        municipioList.innerHTML = '';
        municipioList.classList.add('hidden');
        const q = (query || '').toLowerCase().trim();
        const filtered = municipios.filter(function(m) {
            if (estadoId && m.estado_id != estadoId) return false;
            return !q || m.nome.toLowerCase().indexOf(q) >= 0 || (m.sigla && m.sigla.toLowerCase().indexOf(q) >= 0);
        });
        if (filtered.length === 0) {
            municipioList.innerHTML = '<li class="py-2 px-3 text-gray-500 text-sm">Nenhum município encontrado</li>';
            municipioList.classList.remove('hidden');
            return;
        }
        filtered.forEach(function(m) {
            const li = document.createElement('li');
            li.className = 'py-2 px-3 cursor-pointer hover:bg-blue-50 border-b border-gray-100 last:border-0';
            li.textContent = m.nome + ' (' + m.sigla + ')';
            li.dataset.id = m.id;
            li.dataset.nome = m.nome;
            li.addEventListener('click', function() {
                municipioHidden.value = m.id;
                municipioSearch.value = m.nome + ' (' + m.sigla + ')';
                municipioSearch.dataset.id = m.id;
                if (m.sigla) { ufSelect.value = m.sigla; }
                municipioList.classList.add('hidden');
            });
            municipioList.appendChild(li);
        });
        municipioList.classList.remove('hidden');
    }

    ufSelect.addEventListener('change', function() {
        const estadoId = getEstadoIdBySigla(this.value);
        municipioHidden.value = '';
        municipioSearch.value = '';
        municipioSearch.dataset.id = '';
        renderMunicipios(estadoId, municipioSearch.value);
    });

    municipioSearch.addEventListener('focus', function() {
        const estadoId = getEstadoIdBySigla(ufSelect.value);
        renderMunicipios(estadoId, this.value);
    });
    municipioSearch.addEventListener('input', function() {
        const estadoId = getEstadoIdBySigla(ufSelect.value);
        renderMunicipios(estadoId, this.value);
    });
    municipioSearch.addEventListener('blur', function() {
        setTimeout(function() {
            municipioList.classList.add('hidden');
            if (municipioHidden.value && municipioSearch.dataset.id != municipioHidden.value) {
                const m = municipios.find(function(x) { return x.id == municipioHidden.value; });
                municipioSearch.value = m ? m.nome + ' (' + m.sigla + ')' : '';
            }
        }, 200);
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#municipio-combobox')) municipioList.classList.add('hidden');
    });

    // Pré-preenche UF e município ao editar (se voltar com erro)
    <?php if (!empty($_POST['municipio']) && !empty($_POST['uf'])): ?>
    var m = municipios.find(function(x) { return x.id == <?php echo json_encode($_POST['municipio']); ?>; });
    if (m) { municipioSearch.value = m.nome + ' (' + m.sigla + ')'; municipioSearch.dataset.id = m.id; }
    <?php endif; ?>
})();
</script>
<?php include '../includes/footer.php'; ?>
