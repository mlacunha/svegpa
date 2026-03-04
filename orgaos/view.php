<?php
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
$page_title = "Detalhes - Órgão";
include '../includes/header.php';

$id = isset($_GET['id']) ? sanitizeInput($_GET['id']) : null;
if (!$id) { header('Location: index.php'); exit; }

$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare("SELECT o.*, ot.tipo as tipo_nome FROM orgaos o LEFT JOIN orgaos_tipos ot ON o.tipo = ot.id WHERE o.id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$item) { header('Location: index.php'); exit; }

$copyLines = ["Órgão", "Sigla: " . ($item['sigla'] ?? '-'), "Nome: " . ($item['nome'] ?? '-'), "Tipo: " . ($item['tipo_nome'] ?? '-'), "UF Sede: " . ($item['UF_sede'] ?? '-'), "Logo: " . ($item['logo'] ?? '-')];

$bp = $base_path ?? '/';
$logo_url = !empty($item['logo']) && $item['logo'] !== '-' ? ($bp . $item['logo']) : '';
?>
<style>@media print { #sidebar, #app-header, .no-print { display: none !important; } }</style>
<div class="card max-w-2xl" id="detalle-print">
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-gray-800"><i class="fas fa-eye mr-2"></i>Detalhes do Órgão</h2>
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
            <div><span class="text-gray-600 font-medium">Sigla:</span><br><span class="text-gray-800"><?php echo htmlspecialchars($item['sigla'] ?? '-'); ?></span></div>
            <div><span class="text-gray-600 font-medium">Nome:</span><br><span class="text-gray-800"><?php echo htmlspecialchars($item['nome'] ?? '-'); ?></span></div>
            <div><span class="text-gray-600 font-medium">Tipo:</span><br><span class="text-gray-800"><?php echo htmlspecialchars($item['tipo_nome'] ?? '-'); ?></span></div>
            <div><span class="text-gray-600 font-medium">UF Sede:</span><br><span class="text-gray-800"><?php echo htmlspecialchars($item['UF_sede'] ?? '-'); ?></span></div>
            <div class="md:col-span-2">
                <span class="text-gray-600 font-medium">Logo:</span><br>
                <?php if ($logo_url): ?>
                <img src="<?php echo htmlspecialchars($logo_url); ?>" alt="Logo do órgão" class="max-h-[80px] max-w-full object-contain mt-1">
                <span class="text-gray-500 text-sm block mt-1"><?php echo htmlspecialchars($item['logo']); ?></span>
                <?php else: ?>
                <span class="text-gray-800">—</span>
                <?php endif; ?>
            </div>
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
