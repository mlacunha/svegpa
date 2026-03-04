<?php
declare(strict_types=1);

class CrudController {
    private PDO $db;
    private string $table;
    private array $config;

    public function __construct(PDO $db, string $table, array $config) {
        $this->db = $db;
        $this->table = $table;
        $this->config = $config;
    }

    public function index(): void {
        $limit = min(500, (int)($_GET['limit'] ?? 100));
        $offset = max(0, (int)($_GET['offset'] ?? 0));
        $sql = "SELECT * FROM `{$this->table}` LIMIT $limit OFFSET $offset";
        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(array_map([$this, 'normalizeRow'], $rows));
    }

    public function show(?string $id): void {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID obrigatório']);
            return;
        }
        $pk = $this->config['id'];
        $pkType = $this->config['pk_type'] ?? 'char';
        if (is_array($pk)) {
            http_response_code(400);
            echo json_encode(['error' => 'PK composta - use rota específica']);
            return;
        }
        $placeholder = $pkType === 'int' ? (int) $id : $id;
        $stmt = $this->db->prepare("SELECT * FROM `{$this->table}` WHERE `$pk` = ?");
        $stmt->execute([$placeholder]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'Não encontrado']);
            return;
        }
        echo json_encode($this->normalizeRow($row));
    }

    public function create(): void {
        $input = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $cols = $this->getInsertableColumns();
        $data = $this->filterInput($input, $cols);
        if (empty($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nenhum dado válido']);
            return;
        }
        $fields = array_keys($data);
        $placeholders = implode(', ', array_fill(0, count($fields), '?'));
        $colsStr = implode('`, `', $fields);
        $sql = "INSERT INTO `{$this->table}` (`$colsStr`) VALUES ($placeholders)";
        try {
            $this->db->prepare($sql)->execute(array_values($data));
            $id = $this->db->lastInsertId() ?: ($data[$this->config['id']] ?? null);
            http_response_code(201);
            echo json_encode(['id' => $id, 'message' => 'Criado com sucesso']);
        } catch (PDOException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function update(?string $id): void {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID obrigatório']);
            return;
        }
        $input = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $pk = $this->config['id'];
        if (is_array($pk)) {
            http_response_code(400);
            echo json_encode(['error' => 'PK composta não suportada nesta rota']);
            return;
        }
        $cols = $this->getUpdatableColumns();
        unset($cols[$pk]);
        $data = $this->filterInput($input, $cols);
        if (empty($data)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nenhum dado válido']);
            return;
        }
        $set = implode(' = ?, ', array_keys($data)) . ' = ?';
        $pkType = $this->config['pk_type'] ?? 'char';
        $pkVal = $pkType === 'int' ? (int) $id : $id;
        $values = array_values($data);
        $values[] = $pkVal;
        $sql = "UPDATE `{$this->table}` SET $set WHERE `$pk` = ?";
        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($values);
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Não encontrado']);
                return;
            }
            echo json_encode(['message' => 'Atualizado com sucesso']);
        } catch (PDOException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function delete(?string $id): void {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID obrigatório']);
            return;
        }
        $pk = $this->config['id'];
        if (is_array($pk)) {
            http_response_code(400);
            echo json_encode(['error' => 'PK composta não suportada']);
            return;
        }
        $pkType = $this->config['pk_type'] ?? 'char';
        $pkVal = $pkType === 'int' ? (int) $id : $id;
        $stmt = $this->db->prepare("DELETE FROM `{$this->table}` WHERE `$pk` = ?");
        $stmt->execute([$pkVal]);
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Não encontrado']);
            return;
        }
        echo json_encode(['message' => 'Excluído com sucesso']);
    }

    private function getInsertableColumns(): array {
        $exclude = ['criado_em', 'atualizado_em', 'data_criacao', 'data_atualizacao', 'coordenada', 'picture'];
        return $this->getTableColumns($exclude);
    }

    private function getUpdatableColumns(): array {
        $exclude = ['criado_em', 'data_criacao', 'coordenada', 'picture'];
        return $this->getTableColumns($exclude);
    }

    private function getTableColumns(array $exclude = []): array {
        $stmt = $this->db->query("DESCRIBE `{$this->table}`");
        $cols = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $name = $row['Field'];
            if (in_array($name, $exclude)) continue;
            if ($row['Extra'] === 'auto_increment') continue;
            $cols[$name] = $row['Type'];
        }
        return $cols;
    }

    private function filterInput(array $input, array $allowed): array {
        $out = [];
        foreach ($allowed as $col => $type) {
            if (!array_key_exists($col, $input)) continue;
            $val = $input[$col];
            if ($val === null || $val === '') continue;
            if (str_contains((string) $type, 'int')) $val = (int) $val;
            elseif (str_contains((string) $type, 'decimal') || str_contains((string) $type, 'float')) $val = (float) $val;
            $out[$col] = $val;
        }
        return $out;
    }

    private function normalizeRow(array $row): array {
        foreach ($row as $k => $v) {
            if ($v instanceof DateTimeInterface) {
                $row[$k] = $v->format('Y-m-d H:i:s');
            } elseif (is_resource($v) || (is_string($v) && !mb_check_encoding($v, 'UTF-8'))) {
                unset($row[$k]); // binary/blob/geometry
            }
        }
        return $row;
    }
}
