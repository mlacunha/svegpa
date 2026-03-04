<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
$page_title = "Detalhes - Unidade";
include '../includes/header.php';

$id = isset($_GET['id']) ? sanitizeInput($_GET['id']) : null;
if (!$id) { header('Location: index.php'); exit; }

$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare("SELECT u.*, o.nome as orgao_nome, o.sigla as orgao_sigla, m.nome as municipio_nome FROM unidades u LEFT JOIN orgaos o ON u.orgao = o.id LEFT JOIN municipios m ON CAST(m.id AS CHAR) COLLATE utf8mb4_bin = u.municipio COLLATE utf8mb4_bin WHERE u.id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$item) { header('Location: index.php'); exit; }

$orgao = trim(($item['orgao_nome'] ?? '') . (!empty($item['orgao_sigla']) ? ' (' . $item['orgao_sigla'] . ')' : ''));
$municipioNome = $item['municipio_nome'] ?? $item['municipio'] ?? '-';
$copyLines = ["Unidade", "Nome: " . ($item['nome'] ?? '-'), "Órgão: " . ($orgao ?: '-'), "UF: " . ($item['uf'] ?? '-'), "Município: " . $municipioNome];
?>
<style>@media print { #sidebar, #app-header, .no-print { display: none !important; } }</style>
<div class="card max-w-2xl" id="detalle-print">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800"><i class="fas fa-eye mr-2"></i>Detalhes da Unidade</h2>
        <div class="flex gap-2 no-print">
            <button type="button" onclick="window.print()" class="btn-primary">
                <i class="fas fa-print mr-2"></i>Imprimir
            </button>
            <button type="button" onclick="copiarDetalhe()" class="btn-secondary">
                <i class="fas fa-copy mr-2"></i>Copiar
            </button>
            <a href="index.php" class="btn-secondary">
                <i class="fas fa-arrow-left mr-2"></i>Voltar
            </a>
        </div>
    </div>
    <div id="detalle-content" class="space-y-4">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="md:col-span-2"><span class="text-gray-600 font-medium">Nome:</span><br><span class="text-gray-800"><?php echo htmlspecialchars($item['nome'] ?? '-'); ?></span></div>
            <div class="md:col-span-2"><span class="text-gray-600 font-medium">Órgão:</span><br><span class="text-gray-800"><?php echo htmlspecialchars($orgao ?: '-'); ?></span></div>
            <div><span class="text-gray-600 font-medium">UF:</span><br><span class="text-gray-800"><?php echo htmlspecialchars($item['uf'] ?? '-'); ?></span></div>
            <div><span class="text-gray-600 font-medium">Município:</span><br><span class="text-gray-800"><?php echo htmlspecialchars($municipioNome); ?></span></div>
        </div>
    </div>
</div>
<script>
var copyText = <?php echo json_encode(implode("\n", $copyLines)); ?>;
function copiarDetalhe() {
    navigator.clipboard.writeText(copyText).then(function() {
        alert('Conteúdo copiado para a área de transferência.');
    }).catch(function() {
        alert('Não foi possível copiar.');
    });
}
</script>
<?php include '../includes/footer.php'; ?>
