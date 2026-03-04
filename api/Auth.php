<?php
declare(strict_types=1);

class Auth {
    private PDO $db;
    private string $jwtSecret = 'sanveg_api_secret_key_change_in_production_2026';

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function login(string $login, string $password): ?array {
        $stmt = $this->db->prepare("SELECT login, pswd, name, email FROM sec_users_api WHERE login = ? AND (active = 'Y' OR active = '' OR active IS NULL)");
        $stmt->execute([$login]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row || !password_verify($password, $row['pswd'])) {
            return null;
        }
        $payload = [
            'sub' => $row['login'],
            'name' => $row['name'],
            'email' => $row['email'],
            'iat' => time(),
            'exp' => time() + (86400 * 7) // 7 dias
        ];
        $token = $this->encodeJwt($payload);
        return [
            'token' => $token,
            'user' => [
                'login' => $row['login'],
                'name' => $row['name'],
                'email' => $row['email']
            ]
        ];
    }

    public function validateRequest(): ?array {
        $auth = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!preg_match('/^Bearer\s+(.+)$/i', $auth, $m)) {
            return null;
        }
        return $this->decodeJwt($m[1]);
    }

    private function encodeJwt(array $payload): string {
        $header = base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'HS256']));
        $payloadEnc = base64_encode(json_encode($payload));
        $signature = hash_hmac('sha256', "$header.$payloadEnc", $this->jwtSecret, true);
        $sigB64 = strtr(base64_encode($signature), '+/', '-_');
        return rtrim(strtr(base64_encode($header), '+/', '-_'), '=') . '.' . rtrim(strtr(base64_encode($payloadEnc), '+/', '-_'), '=') . '.' . rtrim($sigB64, '=');
    }

    private function decodeJwt(string $token): ?array {
        $parts = explode('.', $token);
        if (count($parts) !== 3) return null;
        $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/') . str_repeat('=', (4 - strlen($parts[1]) % 4) % 4)), true);
        if (!$payload || !isset($payload['exp']) || $payload['exp'] < time()) return null;
        return $payload;
    }
}
