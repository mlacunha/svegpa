<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
$page_title = "Detalhes - Município";
include '../includes/header.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : null;
if (!$id) { header('Location: index.php'); exit; }

$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare("SELECT m.*, e.nome as estado_nome, e.sigla as estado_sigla FROM municipios m LEFT JOIN estados e ON m.estado_id = e.id WHERE m.id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$item) { header('Location: index.php'); exit; }

$estado = trim(($item['estado_nome'] ?? '') . ' ' . (!empty($item['estado_sigla']) ? '(' . $item['estado_sigla'] . ')' : ''));
$copyLines = ["Município", "Código IBGE: " . ($item['id'] ?? '-'), "Nome: " . ($item['nome'] ?? '-'), "Estado: " . ($estado ?: '-')];
?>
<style>@media print { #sidebar, #app-header, .no-print { display: none !important; } }</style>
<div class="card max-w-2xl" id="detalle-print">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800"><i class="fas fa-eye mr-2"></i>Detalhes do Município</h2>
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
            <div><span class="text-gray-600 font-medium">Código IBGE:</span><br><span class="text-gray-800"><?php echo htmlspecialchars($item['id'] ?? '-'); ?></span></div>
            <div><span class="text-gray-600 font-medium">Nome:</span><br><span class="text-gray-800"><?php echo htmlspecialchars($item['nome'] ?? '-'); ?></span></div>
            <div class="md:col-span-2"><span class="text-gray-600 font-medium">Estado:</span><br><span class="text-gray-800"><?php echo htmlspecialchars($estado ?: '-'); ?></span></div>
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
