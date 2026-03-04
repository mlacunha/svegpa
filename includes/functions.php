<?php
/**
 * Funções auxiliares do Sistema SVEG
 * Arquivo criado para resolver erro de inclusão
 */

/** Retorna o caminho base da aplicação (ex: / ou /sanveg/) */
function getBasePath() {
    if (defined('SANVEG_BASE_PATH') && SANVEG_BASE_PATH !== null) {
        $p = rtrim(SANVEG_BASE_PATH, '/');
        return $p === '' ? '/' : $p . '/';
    }
    $script_name = isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/';
    $script_dir = str_replace('\\', '/', dirname($script_name));
    $dir_segments = array_values(array_filter(explode('/', trim($script_dir, '/'))));
    $subfolders = ['programas', 'propriedades', 'produtos', 'hospedeiros', 'produtores', 'orgaos', 'orgaos_tipos', 'unidades', 'municipios', 'cargos', 'normas', 'termo_inspecao', 'amostragem', 'usuarios', 'config_email'];
    $last_seg = end($dir_segments);
    if ($last_seg && in_array($last_seg, $subfolders)) {
        array_pop($dir_segments);
    }
    return (count($dir_segments) > 0) ? '/' . implode('/', $dir_segments) . '/' : '/';
}

/** Retorna data URL da foto do usuário ou null */
function getUserPhotoDataUrl($login) {
    if (!$login) return null;
    require_once __DIR__ . '/../config/database.php';
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT picture FROM sec_users WHERE login = ?");
    $stmt->execute([$login]);
    $blob = $stmt->fetchColumn();
    if (!$blob) return null;
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->buffer($blob) ?: 'image/jpeg';
    return 'data:' . $mime . ';base64,' . base64_encode($blob);
}

// Sanitização de dados
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
}

/** Formata nome de formação ou cargo para exibição inclusiva: adiciona "(a)" se não houver */
function formacaoOuCargoComA($valor) {
    $v = trim($valor ?? '');
    if (!$v) return '';
    return str_ends_with($v, '(a)') ? $v : $v . '(a)';
}

// Formatação de data BR
function formatDateBR($date) {
    if (empty($date) || $date == '0000-00-00 00:00:00') return '-';
    return date('d/m/Y H:i', strtotime($date));
}

// Redirecionamento seguro
function redirect($url) {
    header("Location: $url");
    exit();
}

// Verificação de requisição POST
function isPost() {
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

/**
 * Gera o próximo n_cadastro para propriedade quando não informado.
 * Formato: UF-000001 (6 dígitos, sequencial por UF).
 *
 * @param PDO $db
 * @param string|null $uf
 * @return string|null
 */
function gerarProximoNCadastro($db, $uf) {
    $uf = strtoupper(trim($uf ?? ''));
    if ($uf === '') return null;
    try {
        $db->beginTransaction();
        $stmt = $db->prepare("INSERT INTO controle_n_cadastro (uf, seq) VALUES (?, 1) ON DUPLICATE KEY UPDATE seq = seq + 1");
        $stmt->execute([$uf]);
        $stmt = $db->prepare("SELECT seq FROM controle_n_cadastro WHERE uf = ?");
        $stmt->execute([$uf]);
        $seq = (int) $stmt->fetchColumn();
        $db->commit();
        return $uf . '-' . str_pad($seq, 6, '0', STR_PAD_LEFT);
    } catch (PDOException $e) {
        if ($db->inTransaction()) $db->rollBack();
        return null;
    }
}

// Mensagens de feedback
function alert($message, $type = 'info') {
    $icons = [
        'success' => 'fa-check-circle text-green-600',
        'error' => 'fa-exclamation-circle text-red-600',
        'info' => 'fa-info-circle text-blue-600'
    ];
    $colors = [
        'success' => 'bg-green-50 border-green-200 text-green-800',
        'error' => 'bg-red-50 border-red-200 text-red-800',
        'info' => 'bg-blue-50 border-blue-200 text-blue-800'
    ];
    
    $colorClass = $colors[$type] ?? $colors['info'];
    $iconClass = $icons[$type] ?? $icons['info'];
    
    echo "<div class='alert alert-{$type} flex items-start p-4 mb-4 rounded-lg border {$colorClass}'>";
    echo "<i class='fas {$iconClass} mt-0.5 mr-3'></i>";
    echo "<div>{$message}</div>";
    echo "</div>";
}
?>