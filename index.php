<?php
require_once 'includes/functions.php';
require_once 'includes/auth.php';

$base = getBasePath();
$qs = isset($_GET['msg']) ? '?msg=' . urlencode($_GET['msg']) : '';

if (getLoggedUser()) {
    header('Location: ' . $base . 'dashboard.php' . $qs);
} else {
    header('Location: ' . $base . 'login.php' . $qs);
}
exit;
?>