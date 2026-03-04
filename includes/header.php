<?php
// Carrega funções e exige login
if (!function_exists('sanitizeInput')) {
    require_once __DIR__ . '/functions.php';
}
require_once __DIR__ . '/auth.php';
requireLogin();
// Define base path absoluto - usa o diretório da aplicação a partir do DOCUMENT_ROOT
// Detecta automaticamente se está em /sanveg/ ou na raiz /
$script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/';
$script_dir = str_replace('\\', '/', dirname($script_name));
$dir_segments = array_values(array_filter(explode('/', trim($script_dir, '/'))));
// Remove último segmento se for subpasta de módulo (programas, propriedades, etc.)
$subfolders = ['programas', 'propriedades', 'produtos', 'hospedeiros', 'produtores', 'orgaos', 'orgaos_tipos', 'unidades', 'municipios', 'cargos', 'normas', 'termo_inspecao', 'amostragem', 'usuarios', 'config_email'];
$last_seg = end($dir_segments);
if ($last_seg && in_array($last_seg, $subfolders)) {
    array_pop($dir_segments);
}
$base_path = (count($dir_segments) > 0) ? '/' . implode('/', $dir_segments) . '/' : '/';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SVEG - Sistema de Vigilância Epidemiológica</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo htmlspecialchars($base_path); ?>css/style.css">
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#1e40af',
                        secondary: '#0f172a',
                    }
                }
            }
        }
    </script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var badge = document.getElementById('user-badge');
        var dropdown = document.getElementById('user-dropdown');
        if (badge && dropdown) {
            badge.addEventListener('click', function(e) {
                e.stopPropagation();
                dropdown.classList.toggle('hidden');
            });
            document.addEventListener('click', function() { dropdown.classList.add('hidden'); });
        }
    });
    </script>
</head>
<body class="bg-gray-100">
    <div class="flex h-screen overflow-hidden">
        <?php include 'sidebar.php'; ?>
        
        <div class="flex flex-col flex-1 overflow-y-auto">
            <header id="app-header" class="bg-white shadow-sm">
                <div class="px-6 py-4 flex justify-between items-center">
                    <div class="flex items-center">
                        <button id="menu-toggle" class="text-gray-500 hover:text-gray-700 focus:outline-none md:hidden">
                            <i class="fas fa-bars text-2xl"></i>
                        </button>
                        <h1 class="text-2xl font-bold text-gray-800 ml-4"><?php 
                            $u = getLoggedUser(); 
                            echo htmlspecialchars($u['orgao_nome'] ?? 'SVEG'); 
                        ?></h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-sm text-gray-600"><?php echo date('d/m/Y'); ?></span>
                        <?php $user = getLoggedUser(); 
                        $user_photo_url = $user ? getUserPhotoDataUrl($user['login']) : null;
                        ?>
                        <div class="relative" id="user-badge-wrap">
                            <button type="button" id="user-badge" class="flex items-center gap-2 bg-primary text-white px-3 py-1.5 rounded-full text-sm hover:bg-blue-900 cursor-pointer transition-colors">
                                <?php if ($user_photo_url): ?>
                                <img src="<?php echo htmlspecialchars($user_photo_url); ?>" alt="" class="w-6 h-6 rounded-full object-cover">
                                <?php else: ?>
                                <i class="fas fa-user"></i>
                                <?php endif; ?>
                                <span><?php echo htmlspecialchars($user['name'] ?? $user['login'] ?? 'Usuário'); ?></span>
                                <i class="fas fa-chevron-down text-xs"></i>
                            </button>
                            <div id="user-dropdown" class="hidden absolute right-0 mt-1 bg-white rounded-lg shadow-lg border border-gray-200 py-1 min-w-[180px] z-50">
                                <a href="<?php echo $base_path; ?>conta.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-camera mr-2"></i>Alterar foto
                                </a>
                                <a href="<?php echo $base_path; ?>conta.php#senha" class="block px-4 py-2 text-gray-700 hover:bg-gray-100">
                                    <i class="fas fa-key mr-2"></i>Alterar senha
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </header>
            
            <main class="flex-1 p-6 overflow-y-auto min-w-0">