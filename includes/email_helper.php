<?php
/**
 * Helper para envio de e-mail via SMTP usando config_email
 * @param string $to Destinatário
 * @param string $subject Assunto
 * @param string $bodyHtml Corpo HTML
 * @param string $bodyText Corpo texto (opcional)
 * @param string|null $errorMsg Recebe a mensagem de erro em caso de falha
 * @return bool true se enviou com sucesso
 */
function sendEmailSmtp($to, $subject, $bodyHtml, $bodyText = '', &$errorMsg = null) {
    require_once __DIR__ . '/../config/database.php';
    $db = (new Database())->getConnection();
    
    $stmt = $db->query("SELECT smtp_host, smtp_port, smtp_user, smtp_pass, smtp_secure, from_email, from_name FROM config_email WHERE id = 1");
    $cfg = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$cfg) {
        if ($errorMsg !== null) $errorMsg = "Tabela config_email vazia ou inexistente. Configure em Auxiliares → Config. E-mail.";
        return false;
    }
    if (empty($cfg['smtp_host'])) {
        if ($errorMsg !== null) $errorMsg = "Host SMTP não configurado. Preencha em Config. E-mail.";
        return false;
    }
    
    $host = $cfg['smtp_host'];
    $port = (int)($cfg['smtp_port'] ?? 587);
    $user = $cfg['smtp_user'] ?? '';
    $pass = $cfg['smtp_pass'] ?? '';
    $secure = strtolower($cfg['smtp_secure'] ?? 'tls');
    $fromEmail = trim($cfg['from_email'] ?? '');
    $fromName = trim($cfg['from_name'] ?? 'SVEG');
    
    if (empty($fromEmail)) {
        if ($errorMsg !== null) $errorMsg = "E-mail de origem não configurado em Config. E-mail.";
        return false;
    }
    
    $useTls = ($secure === 'tls' && $port == 587);
    $useSsl = ($secure === 'ssl');
    $targetHost = ($useSsl ? 'ssl://' : '') . $host;
    
    $errno = $errstr = '';
    $ctx = stream_context_create();
    $fp = @stream_socket_client($targetHost . ':' . $port, $errno, $errstr, 15, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) {
        if ($errorMsg !== null) $errorMsg = "Não foi possível conectar ao servidor SMTP ({$host}:{$port}). " . ($errstr ?: "Verifique host, porta e firewall.");
        return false;
    }
    
    stream_set_timeout($fp, 15);
    $read = function() use ($fp) { $l = fgets($fp, 512); return $l !== false ? $l : ''; };
    $send = function($s) use ($fp) { fwrite($fp, $s . "\r\n"); };
    
    $read(); // banner
    $send("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
    while ($line = $read()) { if (strlen($line) < 4 || substr($line, 3, 1) === ' ') break; }
    
    if ($useTls && !$useSsl) {
        $send("STARTTLS");
        $r = $read();
        if (substr($r, 0, 3) !== '220') {
            fclose($fp);
            if ($errorMsg !== null) $errorMsg = "Servidor não suporta STARTTLS ou erro: " . trim($r);
            return false;
        }
        if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            fclose($fp);
            if ($errorMsg !== null) $errorMsg = "Falha ao iniciar TLS. Verifique se a extensão openssl do PHP está habilitada.";
            return false;
        }
        $send("EHLO " . ($_SERVER['SERVER_NAME'] ?? 'localhost'));
        while ($line = $read()) { if (strlen($line) < 4 || substr($line, 3, 1) === ' ') break; }
    }
    
    $send("AUTH LOGIN");
    $read();
    $send(base64_encode($user));
    $read();
    $send(base64_encode($pass));
    $r = $read();
    if (substr($r, 0, 3) !== '235') {
        fclose($fp);
        if ($errorMsg !== null) $errorMsg = "Autenticação SMTP falhou (usuário/senha incorretos). Resposta: " . trim($r) . ". Para Gmail/Outlook, use 'Senha de app' se 2FA estiver ativo.";
        return false;
    }
    
    $fromDisplay = (strpos($fromName, ',') !== false || strpos($fromName, '"') !== false) ? '"' . addslashes($fromName) . '"' : $fromName;
    $headers = "From: {$fromDisplay} <{$fromEmail}>\r\n";
    $headers .= "To: {$to}\r\n";
    $headers .= "Subject: =?UTF-8?B?" . base64_encode($subject) . "?=\r\n";
    $headers .= "MIME-Version: 1.0\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    $headers .= "\r\n" . $bodyHtml;
    
    $send("MAIL FROM:<{$fromEmail}>");
    $r = $read();
    if (substr($r, 0, 3) !== '250') {
        fclose($fp);
        if ($errorMsg !== null) $errorMsg = "MAIL FROM rejeitado: " . trim($r);
        return false;
    }
    $toAddr = trim(explode(',', $to)[0]);
    $toAddr = (preg_match('/<([^>]+)>/', $toAddr, $m)) ? $m[1] : $toAddr;
    $send("RCPT TO:<{$toAddr}>");
    $r = $read();
    if (substr($r, 0, 3) !== '250') {
        fclose($fp);
        if ($errorMsg !== null) $errorMsg = "Destinatário rejeitado: " . trim($r);
        return false;
    }
    $send("DATA");
    $read();
    $send($headers);
    $send(".");
    $r = $read();
    $send("QUIT");
    fclose($fp);
    if (substr($r, 0, 3) !== '250') {
        if ($errorMsg !== null) $errorMsg = "Envio da mensagem falhou: " . trim($r);
        return false;
    }
    return true;
}
