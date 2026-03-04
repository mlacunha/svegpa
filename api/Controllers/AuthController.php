<?php
declare(strict_types=1);

class AuthController {
    private Auth $auth;

    public function __construct(Auth $auth) {
        $this->auth = $auth;
    }

    public function login(): void {
        $input = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $login = trim((string) ($input['login'] ?? $input['username'] ?? ''));
        $password = (string) ($input['password'] ?? '');

        if (!$login || !$password) {
            http_response_code(400);
            echo json_encode(['error' => 'login e password são obrigatórios']);
            return;
        }

        $result = $this->auth->login($login, $password);
        if (!$result) {
            http_response_code(401);
            echo json_encode(['error' => 'Credenciais inválidas']);
            return;
        }

        echo json_encode($result);
    }
}
