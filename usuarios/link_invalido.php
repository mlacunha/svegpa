<?php
/**
 * Página exibida quando o link de ativação é inválido ou já foi utilizado.
 * Não inclui o layout da aplicação - o usuário não acessa o sistema por meio deste link.
 */
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Link inválido - SVEG</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center p-4">
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-md w-full text-center">
        <div class="text-red-500 mb-4">
            <i class="fas fa-link-slash text-5xl"></i>
        </div>
        <h1 class="text-xl font-bold text-gray-800 mb-2">Link inválido ou já utilizado</h1>
        <p class="text-gray-600 mb-6">Este link de ativação não é válido ou já foi utilizado. Por segurança, cada link funciona apenas uma vez.</p>
        <a href="../index.php" class="inline-block bg-blue-800 text-white px-6 py-2 rounded-lg hover:bg-blue-900 transition-colors">
            <i class="fas fa-home mr-2"></i>Ir para o início
        </a>
    </div>
</body>
</html>
