<?php
/**
 * Diagnóstico de conexão e usuários.
 * Acesse: /check_db.php ou /sanveg/check_db.php
 * EXCLUA ESTE ARQUIVO após resolver o problema (expõe logins).
 */
require_once __DIR__ . '/config/database.php';
header('Content-Type: text/plain; charset=utf-8');
echo "=== SVEG Diagnóstico DB ===\n\n";

$host = getenv('DB_HOST') ?: '209.50.227.136';
$port = 3306;

echo "1. Teste de conectividade TCP (host: $host:$port)\n";
$sock = @fsockopen($host, $port, $errno, $errstr, 5);
$tcpOk = (bool) $sock;
if ($tcpOk) {
    fclose($sock);
    echo "   OK: Porta 3306 acessível.\n\n";
} else {
    echo "   FALHOU: $errstr (errno $errno)\n";
    echo "   Possível causa: firewall bloqueando saída ou MySQL não aceita conexões remotas.\n";
    echo "   Solução: liberar IP do servidor web no MySQL ou usar DB no mesmo servidor.\n\n";
}

echo "2. Teste de conexão PDO\n";
$db = (new Database())->getConnection();
if (!$db) {
    echo "   FALHOU: Não foi possível conectar via PDO.\n";
    echo "   Verifique config/database.php ou variáveis DB_HOST, DB_NAME, DB_USER, DB_PASS.\n";
    if (!$tcpOk) {
        echo "   (A falha TCP acima provavelmente explica o problema.)\n";
    }
    echo "   Log: " . ini_get('error_log') . "\n";
    exit;
}
echo "   OK: Conexão PDO estabelecida.\n\n";

echo "3. Usuários em sec_users\n";
try {
    $stmt = $db->query("SELECT login, name, active, LENGTH(pswd) as pswd_len FROM sec_users LIMIT 10");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Usuários (sec_users): " . count($users) . " primeiros\n";
    foreach ($users as $u) {
        echo "  - " . $u['login'] . " | " . ($u['name'] ?? '-') . " | active=" . ($u['active'] ?? 'NULL') . " | hash_len=" . $u['pswd_len'] . "\n";
    }
} catch (PDOException $e) {
    echo "ERRO ao ler sec_users: " . $e->getMessage() . "\n";
}

$testLogin = isset($_GET['test']) ? trim($_GET['test']) : '';
$testPwd = isset($_GET['pwd']) ? $_GET['pwd'] : '';
if ($testLogin !== '' && $testPwd !== '' && $db) {
    echo "\n4. Teste de hash para login '$testLogin'\n";
    try {
        $stmt = $db->prepare("SELECT login, pswd FROM sec_users WHERE LOWER(TRIM(login)) = LOWER(?)");
        $stmt->execute([$testLogin]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$u) {
            echo "   Usuário não encontrado.\n";
        } else {
            $hashInput = md5(trim($testPwd));
            $hashDb = $u['pswd'];
            $match = ($hashInput === $hashDb);
            echo "   Hash do input: " . $hashInput . "\n";
            echo "   Hash no banco: " . $hashDb . "\n";
            echo "   Resultado: " . ($match ? "BATE - login deveria funcionar" : "NÃO BATE - senha incorreta ou problema de encoding") . "\n";
        }
    } catch (PDOException $e) {
        echo "   ERRO: " . $e->getMessage() . "\n";
    }
}
