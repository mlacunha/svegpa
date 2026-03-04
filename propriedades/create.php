<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
$page_title = "Nova Propriedade";

$database = new Database();
$db = $database->getConnection();

// Garante que a coluna observacoes existe (migração silenciosa)
try {
    $db->query("SELECT observacoes FROM propriedades LIMIT 1");
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Unknown column') !== false) {
        $db->exec("ALTER TABLE propriedades ADD COLUMN observacoes TEXT NULL AFTER longitude");
    } else throw $e;
}

$stmt = $db->query("SELECT id, nome FROM produtores ORDER BY nome ASC");
$produtores = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
        $UF = !empty($_POST['UF']) ? sanitizeInput($_POST['UF']) : null;
        $area_total = isset($_POST['area_total']) && $_POST['area_total'] !== '' ? floatval($_POST['area_total']) : null;
        $destino_producao = !empty($_POST['destino_producao']) ? sanitizeInput($_POST['destino_producao']) : null;
        $id_proprietario = !empty($_POST['id_proprietario']) ? sanitizeInput($_POST['id_proprietario']) : null;
        $classificacao = !empty($_POST['classificacao']) ? sanitizeInput($_POST['classificacao']) : null;
        $producao_familiar = !empty($_POST['producao_familiar']) ? sanitizeInput($_POST['producao_familiar']) : null;
        $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? floatval($_POST['latitude']) : null;
        $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? floatval($_POST['longitude']) : null;
        $observacoes = !empty($_POST['observacoes']) ? sanitizeInput($_POST['observacoes']) : null;
        $produtos = isset($_POST['produtos']) && is_array($_POST['produtos']) ? $_POST['produtos'] : [];
        $produtos = array_filter(array_map('trim', array_map('sanitizeInput', $produtos)));
        
        if (empty($n_cadastro) && $UF) {
            $gerado = gerarProximoNCadastro($db, $UF);
            if ($gerado) $n_cadastro = $gerado;
        }
        
        try {
            $db->beginTransaction();
            $id_propriedade = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xfff)|0x4000, mt_rand(0,0x3fff)|0x8000, mt_rand(0,0xffff), mt_rand(0,0xffff), mt_rand(0,0xffff));
            $stmt = $db->prepare("INSERT INTO propriedades (id, n_cadastro, cpf_cnpj, RG_IE, nome, CEP, endereco, bairro, municipio, UF, area_total, destino_producao, id_proprietario, classificacao, producao_familiar, latitude, longitude, observacoes) VALUES (:id, :n_cadastro, :cpf_cnpj, :RG_IE, :nome, :CEP, :endereco, :bairro, :municipio, :UF, :area_total, :destino_producao, :id_proprietario, :classificacao, :producao_familiar, :latitude, :longitude, :observacoes)");
            
            $stmt->bindValue(':id', $id_propriedade);
            $stmt->bindValue(':n_cadastro', $n_cadastro);
            $stmt->bindValue(':cpf_cnpj', $cpf_cnpj);
            $stmt->bindValue(':RG_IE', $RG_IE);
            $stmt->bindParam(':nome', $nome);
            $stmt->bindValue(':CEP', $CEP);
            $stmt->bindValue(':endereco', $endereco);
            $stmt->bindValue(':bairro', $bairro);
            $stmt->bindValue(':municipio', $municipio);
            $stmt->bindValue(':UF', $UF);
            $stmt->bindValue(':area_total', $area_total);
            $stmt->bindValue(':destino_producao', $destino_producao);
            $stmt->bindValue(':id_proprietario', $id_proprietario);
            $stmt->bindValue(':classificacao', $classificacao);
            $stmt->bindValue(':producao_familiar', $producao_familiar);
            $stmt->bindValue(':latitude', $latitude);
            $stmt->bindValue(':longitude', $longitude);
            $stmt->bindValue(':observacoes', $observacoes);
            
            $stmt->execute();
            
            if ($id_propriedade && !empty($produtos)) {
                $stmtProd = $db->prepare("INSERT INTO produtos (id, id_propriedade, produto) VALUES (UUID(), ?, ?)");
                foreach ($produtos as $p) {
                    if ($p !== '') {
                        $stmtProd->execute([$id_propriedade, $p]);
                    }
                }
            }
            
            $db->commit();
            header('Location: index.php?success=created');
            exit;
        } catch (PDOException $e) {
            if ($db->inTransaction()) $db->rollBack();
            $error = "Erro ao salvar: " . $e->getMessage();
        }
    }
}

include '../includes/header.php';
?>

<div class="card">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-2xl font-bold text-gray-800">
            <i class="fas fa-plus-circle mr-2"></i>Nova Propriedade
        </h2>
        <a href="index.php" class="btn-secondary">
            <i class="fas fa-arrow-left mr-2"></i>Voltar
        </a>
    </div>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-error mb-4">
        <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
    </div>
    <?php endif; ?>
    
    <form method="POST" id="formPropriedade" class="prop-form-compact space-y-3">
        <h3 class="text-base font-semibold text-gray-700 border-b pb-1">Identificação</h3>
        <div class="grid grid-cols-1 md:grid-cols-[2fr_1fr_1fr_1fr] gap-3">
            <div class="form-group md:col-span-1">
                <label for="nome">Nome *</label>
                <input type="text" id="nome" name="nome" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required>
            </div>
            <div class="form-group md:col-span-1">
                <label for="n_cadastro">N. Cadastro</label>
                <input type="text" id="n_cadastro" name="n_cadastro" class="form-control" maxlength="50" value="<?php echo htmlspecialchars($_POST['n_cadastro'] ?? ''); ?>">
            </div>
            <div class="form-group md:col-span-1">
                <label for="cpf_cnpj">CPF/CNPJ</label>
                <input type="text" id="cpf_cnpj" name="cpf_cnpj" class="form-control mask-cpf-cnpj" data-mask="cpf-cnpj" maxlength="18" value="<?php echo htmlspecialchars($_POST['cpf_cnpj'] ?? ''); ?>">
            </div>
            <div class="form-group md:col-span-1">
                <label for="RG_IE">RG/IE</label>
                <input type="text" id="RG_IE" name="RG_IE" class="form-control" maxlength="20" value="<?php echo htmlspecialchars($_POST['RG_IE'] ?? ''); ?>">
            </div>
        </div>
        
        <h3 class="text-base font-semibold text-gray-700 border-b pb-1">Endereço</h3>
        <div class="grid grid-cols-1 md:grid-cols-[2fr_8fr_4fr_4fr_2fr] gap-3">
            <div class="form-group">
                <label for="CEP">CEP</label>
                <input type="text" id="CEP" name="CEP" class="form-control mask-cep" data-mask="cep" maxlength="9" value="<?php echo htmlspecialchars($_POST['CEP'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="endereco">Endereço</label>
                <input type="text" id="endereco" name="endereco" class="form-control" maxlength="200" value="<?php echo htmlspecialchars($_POST['endereco'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="bairro">Bairro</label>
                <input type="text" id="bairro" name="bairro" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($_POST['bairro'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="municipio">Município</label>
                <input type="text" id="municipio" name="municipio" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($_POST['municipio'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="UF">UF</label>
                <select id="UF" name="UF" class="form-control">
                    <?php $ufSel = $_POST['UF'] ?? 'PA'; ?>
                    <?php foreach ($estados as $e): ?>
                    <option value="<?php echo htmlspecialchars($e['sigla']); ?>" <?php echo $ufSel === $e['sigla'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($e['sigla'] . ' - ' . $e['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        
        <h3 class="text-base font-semibold text-gray-700 border-b pb-1">Dados da Propriedade</h3>
        <div class="grid grid-cols-1 md:grid-cols-[1fr_2fr_2fr_3fr_2fr] gap-3">
            <div class="form-group">
                <label for="area_total">Área Total (ha)</label>
                <input type="number" id="area_total" name="area_total" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['area_total'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label for="destino_producao">Destino Produção</label>
                <input type="text" id="destino_producao" name="destino_producao" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($_POST['destino_producao'] ?? ''); ?>">
            </div>
            <div class="form-group form-group-checkbox flex items-center">
                <label class="cursor-pointer">
                    <input type="hidden" name="producao_familiar" value="Não">
                    <input type="checkbox" id="producao_familiar" name="producao_familiar" value="Sim" style="margin-right: 8px; vertical-align: middle;" <?php echo (isset($_POST['producao_familiar']) && $_POST['producao_familiar'] === 'Sim') ? 'checked' : ''; ?>>
                    <span>Produção Familiar</span>
                </label>
            </div>
            <div class="form-group">
                <label for="id_proprietario">Proprietário</label>
                <select id="id_proprietario" name="id_proprietario" class="form-control">
                    <option value="">-- Selecione o proprietário --</option>
                    <?php foreach ($produtores as $prod): ?>
                    <option value="<?php echo htmlspecialchars($prod['id']); ?>" <?php echo (isset($_POST['id_proprietario']) && $_POST['id_proprietario'] === $prod['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($prod['nome']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="classificacao">Classificação</label>
                <select id="classificacao" name="classificacao" class="form-control">
                    <option value="">-- Selecione --</option>
                    <option value="Proprietario" <?php echo (isset($_POST['classificacao']) && $_POST['classificacao'] === 'Proprietario') ? 'selected' : ''; ?>>Proprietário</option>
                    <option value="Arrendatario" <?php echo (isset($_POST['classificacao']) && $_POST['classificacao'] === 'Arrendatario') ? 'selected' : ''; ?>>Arrendatário</option>
                    <option value="Posse" <?php echo (isset($_POST['classificacao']) && $_POST['classificacao'] === 'Posse') ? 'selected' : ''; ?>>Posse</option>
                    <option value="Outros" <?php echo (isset($_POST['classificacao']) && $_POST['classificacao'] === 'Outros') ? 'selected' : ''; ?>>Outros</option>
                </select>
            </div>
        </div>
        
        <h3 class="text-base font-semibold text-gray-700 border-b pb-1">Coordenadas e Observações</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
                <div class="flex gap-2 items-end flex-wrap">
                    <div class="form-group flex-1 min-w-0">
                        <label for="latitude">Latitude</label>
                        <input type="text" id="latitude" name="latitude" class="form-control mask-coord" data-mask="coord" placeholder="-90.12345678" value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>">
                    </div>
                    <div class="form-group flex-1 min-w-0">
                        <label for="longitude">Longitude</label>
                        <input type="text" id="longitude" name="longitude" class="form-control mask-coord" data-mask="coord" placeholder="-180.12345678" value="<?php echo htmlspecialchars($_POST['longitude'] ?? ''); ?>">
                    </div>
                </div>
                <div class="flex gap-2">
                    <button type="button" id="coordConverterBtn" class="btn-secondary whitespace-nowrap text-sm">
                        <i class="fas fa-exchange-alt mr-2"></i>Converter
                    </button>
                    <button type="button" id="coordMapaBtn" class="btn-secondary whitespace-nowrap text-sm">
                        <i class="fas fa-map mr-2"></i>MAPA
                    </button>
                </div>
                <div class="form-group">
                    <label for="observacoes">Observações</label>
                    <textarea id="observacoes" name="observacoes" class="form-control" rows="4"><?php echo htmlspecialchars($_POST['observacoes'] ?? ''); ?></textarea>
                </div>
            </div>
            
            <div>
                <div class="flex justify-between items-center mb-2">
                    <label class="text-sm font-semibold text-gray-700">Produtos</label>
                    <button type="button" id="btnExibirProdutos" class="btn-secondary text-sm">
                        <i class="fas fa-eye mr-2"></i>Exibir
                    </button>
                </div>
                <div id="produtosFormExpand" class="hidden mb-2">
                    <div class="flex gap-2 mb-2">
                        <input type="text" id="novoProduto" class="form-control flex-1" placeholder="Nome do produto" maxlength="100">
                        <button type="button" id="btnAddProduto" class="btn-primary text-sm">
                            <i class="fas fa-plus mr-2"></i>Adicionar
                        </button>
                    </div>
                </div>
                <div id="produtosLista" class="border border-gray-200 rounded overflow-auto max-h-40">
                    <table class="min-w-full text-sm">
                        <thead><tr class="bg-gray-100"><th class="py-1 px-2 text-left">Produto</th><th class="py-1 px-2 w-10"></th></tr></thead>
                        <tbody id="produtosTableBody">
                            <?php $produtosIniciais = isset($_POST['produtos']) && is_array($_POST['produtos']) ? array_filter(array_map('trim', $_POST['produtos'])) : []; ?>
                            <?php if (empty($produtosIniciais)): ?>
                            <tr><td colspan="2" class="py-3 px-2 text-center text-gray-500 text-sm">Nenhum produto. Clique em "Exibir" para adicionar.</td></tr>
                            <?php else: ?>
                            <?php foreach ($produtosIniciais as $p): ?>
                            <tr class="border-t hover:bg-gray-50"><td class="py-1 px-2"><?php echo htmlspecialchars($p); ?></td><td class="py-1 px-2"><button type="button" class="text-red-600 hover:text-red-800" onclick="window.removerProdutoByVal(this.getAttribute('data-val'))" data-val="<?php echo htmlspecialchars($p); ?>"><i class="fas fa-trash"></i></button></td></tr>
                            <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <div id="produtosHiddenContainer">
                    <?php foreach ($produtosIniciais as $p): ?>
                    <input type="hidden" name="produtos[]" value="<?php echo htmlspecialchars($p); ?>">
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="flex justify-end space-x-3 pt-3">
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
    var expand = document.getElementById('produtosFormExpand');
    var btnExibir = document.getElementById('btnExibirProdutos');
    var novoInput = document.getElementById('novoProduto');
    var btnAdd = document.getElementById('btnAddProduto');
    var tbody = document.getElementById('produtosTableBody');
    var hiddenContainer = document.getElementById('produtosHiddenContainer');
    var produtos = [].slice.call(document.querySelectorAll('#produtosHiddenContainer input[name="produtos[]"]')).map(function(inp) { return inp.value; });
    
    function syncHidden() {
        hiddenContainer.innerHTML = '';
        produtos.forEach(function(p) {
            var inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'produtos[]';
            inp.value = p;
            hiddenContainer.appendChild(inp);
        });
    }
    function render() {
        if (produtos.length === 0) {
            tbody.innerHTML = '<tr><td colspan="2" class="py-3 px-2 text-center text-gray-500 text-sm">Nenhum produto na lista.</td></tr>';
        } else {
            tbody.innerHTML = produtos.map(function(p, i) {
                return '<tr class="border-t hover:bg-gray-50"><td class="py-1 px-2">' + (p.replace(/</g,'&lt;')) + '</td><td class="py-1 px-2"><button type="button" class="text-red-600 hover:text-red-800" data-i="' + i + '" onclick="window.removerProduto(' + i + ')"><i class="fas fa-trash"></i></button></td></tr>';
            }).join('');
        }
        syncHidden();
    }
    window.removerProduto = function(i) {
        produtos.splice(i, 1);
        render();
    };
    window.removerProdutoByVal = function(v) {
        var i = produtos.indexOf(v);
        if (i >= 0) { produtos.splice(i, 1); render(); }
    };
    
    if (btnExibir) btnExibir.addEventListener('click', function() {
        if (expand.classList.contains('hidden')) {
            expand.classList.remove('hidden');
            btnExibir.innerHTML = '<i class="fas fa-eye-slash mr-2"></i>Ocultar';
        } else {
            expand.classList.add('hidden');
            btnExibir.innerHTML = '<i class="fas fa-eye mr-2"></i>Exibir';
        }
    });
    if (btnAdd && novoInput) btnAdd.addEventListener('click', function() {
        var v = novoInput.value.trim();
        if (v) { produtos.push(v); novoInput.value = ''; render(); }
    });
    if (novoInput) novoInput.addEventListener('keypress', function(e) {
        if (e.key === 'Enter') { e.preventDefault(); btnAdd.click(); }
    });
})();
</script>

<?php include '../includes/coord_converter.php'; ?>
<?php include '../includes/coord_map_modal.php'; ?>
<?php include '../includes/footer.php'; ?>
