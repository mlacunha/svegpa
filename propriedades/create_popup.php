<?php
/**
 * Cadastro de propriedade em popup (iframe).
 * Após salvar com sucesso, envia postMessage ao parent e exibe confirmação.
 */
require_once '../config/database.php';
require_once '../includes/functions.php';

$database = new Database();
$db = $database->getConnection();

$stmt = $db->query("SELECT id, nome FROM produtores ORDER BY nome ASC");
$produtores = $stmt->fetchAll(PDO::FETCH_ASSOC);
$estados = $db->query("SELECT id, nome, sigla FROM estados WHERE id NOT IN (99, 99999) ORDER BY sigla")->fetchAll(PDO::FETCH_ASSOC);

$saved = false;
$saved_id = null;
$saved_nome = null;
$saved_n_cadastro = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nome = sanitizeInput($_POST['nome'] ?? '');
    
    if (empty($nome)) {
        $error = "O campo nome é obrigatório.";
    } else {
        $n_cadastro = !empty($_POST['n_cadastro']) ? sanitizeInput($_POST['n_cadastro']) : null;
        $UF = !empty($_POST['UF']) ? sanitizeInput($_POST['UF']) : null;
        if (empty($n_cadastro) && $UF) {
            $gerado = gerarProximoNCadastro($db, $UF);
            if ($gerado) $n_cadastro = $gerado;
        }
        $cpf_cnpj = !empty($_POST['cpf_cnpj']) ? sanitizeInput($_POST['cpf_cnpj']) : null;
        $RG_IE = !empty($_POST['RG_IE']) ? sanitizeInput($_POST['RG_IE']) : null;
        $CEP = !empty($_POST['CEP']) ? sanitizeInput($_POST['CEP']) : null;
        $endereco = !empty($_POST['endereco']) ? sanitizeInput($_POST['endereco']) : null;
        $bairro = !empty($_POST['bairro']) ? sanitizeInput($_POST['bairro']) : null;
        $municipio = !empty($_POST['municipio']) ? sanitizeInput($_POST['municipio']) : null;
        $area_total = isset($_POST['area_total']) && $_POST['area_total'] !== '' ? floatval($_POST['area_total']) : null;
        $destino_producao = !empty($_POST['destino_producao']) ? sanitizeInput($_POST['destino_producao']) : null;
        $id_proprietario = !empty($_POST['id_proprietario']) ? sanitizeInput($_POST['id_proprietario']) : null;
        $classificacao = !empty($_POST['classificacao']) ? sanitizeInput($_POST['classificacao']) : null;
        $producao_familiar = !empty($_POST['producao_familiar']) ? sanitizeInput($_POST['producao_familiar']) : null;
        $latitude = isset($_POST['latitude']) && $_POST['latitude'] !== '' ? floatval($_POST['latitude']) : null;
        $longitude = isset($_POST['longitude']) && $_POST['longitude'] !== '' ? floatval($_POST['longitude']) : null;
        
        try {
            $stmt = $db->prepare("INSERT INTO propriedades (id, n_cadastro, cpf_cnpj, RG_IE, nome, CEP, endereco, bairro, municipio, UF, area_total, destino_producao, id_proprietario, classificacao, producao_familiar, latitude, longitude) VALUES (UUID(), :n_cadastro, :cpf_cnpj, :RG_IE, :nome, :CEP, :endereco, :bairro, :municipio, :UF, :area_total, :destino_producao, :id_proprietario, :classificacao, :producao_familiar, :latitude, :longitude)");
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
            
            if ($stmt->execute()) {
                $q = $db->prepare("SELECT id, n_cadastro, nome FROM propriedades WHERE nome = :nome ORDER BY criado_em DESC LIMIT 1");
                $q->bindParam(':nome', $nome);
                $q->execute();
                $novo = $q->fetch(PDO::FETCH_ASSOC);
                $saved = true;
                $saved_id = $novo['id'] ?? null;
                $saved_nome = $novo['nome'] ?? $nome;
                $saved_n_cadastro = trim($novo['n_cadastro'] ?? '');
            }
        } catch (PDOException $e) {
            $error = "Erro ao salvar: " . $e->getMessage();
        }
    }
}

if ($saved) {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Salvo</title></head><body>';
    echo '<p style="padding:20px;color:green;"><strong>Propriedade cadastrada com sucesso!</strong></p>';
    echo '<script>try{window.parent.postMessage({type:"propriedade_created",id:"' . addslashes($saved_id) . '",nome:' . json_encode($saved_nome) . ',n_cadastro:' . json_encode($saved_n_cadastro ?? '') . '},"*");}catch(e){}</script>';
    echo '</body></html>';
    exit;
}

$script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/';
$script_dir = str_replace('\\', '/', dirname($script_name));
$segments = array_values(array_filter(explode('/', trim($script_dir, '/'))));
if (end($segments) === 'propriedades') array_pop($segments);
$base_path = (count($segments) > 0) ? '/' . implode('/', $segments) . '/' : '/';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nova Propriedade</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>css/style.css">
</head>
<body class="bg-gray-100 p-4">
<div class="card max-w-4xl mx-auto">
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-gray-800"><i class="fas fa-plus-circle mr-2"></i>Nova Propriedade</h2>
    </div>
    
    <?php if (isset($error)): ?>
    <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <form method="POST" class="space-y-4">
        <h3 class="text-sm font-semibold text-gray-700 border-b pb-1">Identificação</h3>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="form-group"><label for="nome">Nome *</label><input type="text" id="nome" name="nome" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($_POST['nome'] ?? ''); ?>" required></div>
            <div class="form-group"><label for="n_cadastro">N. Cadastro</label><input type="text" id="n_cadastro" name="n_cadastro" class="form-control" maxlength="50" value="<?php echo htmlspecialchars($_POST['n_cadastro'] ?? ''); ?>"></div>
            <div class="form-group"><label for="cpf_cnpj">CPF/CNPJ</label><input type="text" id="cpf_cnpj" name="cpf_cnpj" class="form-control mask-cpf-cnpj" data-mask="cpf-cnpj" maxlength="18" value="<?php echo htmlspecialchars($_POST['cpf_cnpj'] ?? ''); ?>"></div>
            <div class="form-group"><label for="RG_IE">RG/IE</label><input type="text" id="RG_IE" name="RG_IE" class="form-control" maxlength="20" value="<?php echo htmlspecialchars($_POST['RG_IE'] ?? ''); ?>"></div>
        </div>
        <h3 class="text-sm font-semibold text-gray-700 border-b pb-1">Endereço</h3>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="form-group"><label for="CEP">CEP</label><input type="text" id="CEP" name="CEP" class="form-control mask-cep" data-mask="cep" maxlength="9" value="<?php echo htmlspecialchars($_POST['CEP'] ?? ''); ?>"></div>
            <div class="form-group md:col-span-2"><label for="endereco">Endereço</label><input type="text" id="endereco" name="endereco" class="form-control" maxlength="200" value="<?php echo htmlspecialchars($_POST['endereco'] ?? ''); ?>"></div>
            <div class="form-group"><label for="bairro">Bairro</label><input type="text" id="bairro" name="bairro" class="form-control" maxlength="100" value="<?php echo htmlspecialchars($_POST['bairro'] ?? ''); ?>"></div>
            <div class="form-group"><label for="municipio">Município</label><input type="text" id="municipio" name="municipio" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($_POST['municipio'] ?? ''); ?>"></div>
            <div class="form-group"><label for="UF">UF</label><select id="UF" name="UF" class="form-control"><?php $ufSel = $_POST['UF'] ?? 'PA'; foreach ($estados as $e): ?><option value="<?php echo htmlspecialchars($e['sigla']); ?>" <?php echo $ufSel === $e['sigla'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($e['sigla']); ?></option><?php endforeach; ?></select></div>
        </div>
        <h3 class="text-sm font-semibold text-gray-700 border-b pb-1">Dados da Propriedade</h3>
        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="form-group"><label for="area_total">Área Total (ha)</label><input type="number" id="area_total" name="area_total" class="form-control" step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['area_total'] ?? ''); ?>"></div>
            <div class="form-group"><label for="destino_producao">Destino Produção</label><input type="text" id="destino_producao" name="destino_producao" class="form-control" maxlength="255" value="<?php echo htmlspecialchars($_POST['destino_producao'] ?? ''); ?>"></div>
            <div class="form-group form-group-checkbox flex items-center"><label class="cursor-pointer"><input type="hidden" name="producao_familiar" value="Não"><input type="checkbox" name="producao_familiar" value="Sim" <?php echo (isset($_POST['producao_familiar']) && $_POST['producao_familiar'] === 'Sim') ? 'checked' : ''; ?>><span class="ml-2">Produção Familiar</span></label></div>
            <div class="form-group"><label for="id_proprietario">Proprietário</label><select id="id_proprietario" name="id_proprietario" class="form-control"><option value="">-- Selecione --</option><?php foreach ($produtores as $prod): ?><option value="<?php echo htmlspecialchars($prod['id']); ?>" <?php echo (isset($_POST['id_proprietario']) && $_POST['id_proprietario'] === $prod['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($prod['nome']); ?></option><?php endforeach; ?></select></div>
            <div class="form-group"><label for="classificacao">Classificação</label><select id="classificacao" name="classificacao" class="form-control"><option value="">-- Selecione --</option><option value="Proprietario" <?php echo (isset($_POST['classificacao']) && $_POST['classificacao'] === 'Proprietario') ? 'selected' : ''; ?>>Proprietário</option><option value="Arrendatario" <?php echo (isset($_POST['classificacao']) && $_POST['classificacao'] === 'Arrendatario') ? 'selected' : ''; ?>>Arrendatário</option><option value="Posse" <?php echo (isset($_POST['classificacao']) && $_POST['classificacao'] === 'Posse') ? 'selected' : ''; ?>>Posse</option><option value="Outros" <?php echo (isset($_POST['classificacao']) && $_POST['classificacao'] === 'Outros') ? 'selected' : ''; ?>>Outros</option></select></div>
        </div>
        <h3 class="text-sm font-semibold text-gray-700 border-b pb-1">Coordenadas (opcional)</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="form-group"><label for="latitude">Latitude</label><input type="text" id="latitude" name="latitude" class="form-control mask-coord" placeholder="-90.12345678" value="<?php echo htmlspecialchars($_POST['latitude'] ?? ''); ?>"></div>
            <div class="form-group"><label for="longitude">Longitude</label><input type="text" id="longitude" name="longitude" class="form-control mask-coord" placeholder="-180.12345678" value="<?php echo htmlspecialchars($_POST['longitude'] ?? ''); ?>"></div>
        </div>
        <div class="flex justify-end space-x-3 pt-4">
            <button type="submit" class="btn-primary"><i class="fas fa-save mr-2"></i>Salvar</button>
        </div>
    </form>
</div>
<script src="<?php echo htmlspecialchars($base_path); ?>js/main.js"></script>
<script>
(function(){function m(v,e){var s=document.createElement('div');s.textContent=v;return s.innerHTML}function maskCep(el){var v=(el.value||'').replace(/\D/g,'');if(v.length>5)v=v.slice(0,5)+'-'+v.slice(5,8);if(el.value!==v)el.value=v}function maskCoord(el){var v=(el.value||'').trim();var neg=v.charAt(0)==='-'?'-':'';v=v.replace(/-/g,'').replace(/[^\d.]/g,'');var idx=v.indexOf('.');if(idx>=0){var b=v.slice(0,idx);var a=v.slice(idx+1).replace(/\./g,'').slice(0,8);v=b+'.'+a}v=neg+v;if(el.value!==v)el.value=v}function maskCpfCnpj(el){var v=(el.value||'').replace(/\D/g,'');if(v.length<=11){v=v.replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d)/,'$1.$2').replace(/(\d{3})(\d{1,2})$/,'$1-$2')}else{v=v.slice(0,14);v=v.replace(/^(\d{2})(\d)/,'$1.$2').replace(/^(\d{2})\.(\d{3})(\d)/,'$1.$2.$3').replace(/\.(\d{3})(\d{4})/,'.$1/$2').replace(/(\d{4})(\d{2})$/,'$1-$2')}if(el.value!==v)el.value=v}document.querySelectorAll('.mask-cep').forEach(function(el){el.addEventListener('input',function(){maskCep(this)})});document.querySelectorAll('.mask-coord').forEach(function(el){el.addEventListener('input',function(){maskCoord(this)})});document.querySelectorAll('.mask-cpf-cnpj').forEach(function(el){el.addEventListener('input',function(){maskCpfCnpj(this)})});})();
</script>
</body>
</html>
