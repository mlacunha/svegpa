<?php
declare(strict_types=1);

class RelatorioMapaController {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function index(): void {
        $limit = min(500, (int)($_GET['limit'] ?? 100));
        $offset = max(0, (int)($_GET['offset'] ?? 0));
        $stmt = $this->db->prepare("SELECT id, id_programa, ano, data, trimestre, orgao, id_usuario, termo_inspecao, termo_coleta, id_propriedade, tipo_imovel, municipio, cultura, latitude, longitude, status FROM relatorio_mapa LIMIT ? OFFSET ?");
        $stmt->execute([$limit, $offset]);
        echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
    }

    public function show(?string $id): void {
        if (!$id) {
            http_response_code(400);
            echo json_encode(['error' => 'ID obrigatório']);
            return;
        }
        $stmt = $this->db->prepare("SELECT id, id_programa, ano, data, trimestre, orgao, id_usuario, termo_inspecao, termo_coleta, id_propriedade, tipo_imovel, municipio, cultura, latitude, longitude, status FROM relatorio_mapa WHERE id = ?");
        $stmt->execute([(int)$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$row) {
            http_response_code(404);
            echo json_encode(['error' => 'Não encontrado']);
            return;
        }
        echo json_encode($row);
    }

    public function create(): void {
        $input = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $cols = ['id_programa', 'ano', 'data', 'trimestre', 'orgao', 'id_usuario', 'termo_inspecao', 'termo_coleta', 'id_propriedade', 'tipo_imovel', 'municipio', 'cultura', 'latitude', 'longitude', 'status'];
        $data = [];
        foreach ($cols as $c) {
            if (array_key_exists($c, $input)) $data[$c] = $input[$c];
        }
        if (empty($data['id_programa']) || empty($data['ano']) || empty($data['data'])) {
            http_response_code(400);
            echo json_encode(['error' => 'id_programa, ano e data são obrigatórios']);
            return;
        }
        $lat = isset($data['latitude']) ? (float)$data['latitude'] : null;
        $lon = isset($data['longitude']) ? (float)$data['longitude'] : null;
        if ($lat !== null && $lon !== null) {
            $data['coordenada_sql'] = "ST_GeomFromText(CONCAT('POINT(', ?, ' ', ?, ')'), 4326)";
        }
        $fields = array_keys($data);
        unset($data['coordenada_sql']);
        $fields = array_filter($fields, fn($f) => $f !== 'coordenada_sql');
        $placeholders = [];
        $vals = [];
        foreach ($fields as $f) {
            $placeholders[] = '?';
            $vals[] = $data[$f];
        }
        if ($lat !== null && $lon !== null) {
            $fields[] = 'coordenada';
            $placeholders[] = "ST_GeomFromText(CONCAT('POINT(', ?, ' ', ?, ')'), 4326)";
            $vals[] = $lon;
            $vals[] = $lat;
        }
        $colsStr = implode('`, `', $fields);
        $phStr = implode(', ', $placeholders);
        try {
            $this->db->prepare("INSERT INTO relatorio_mapa (`$colsStr`) VALUES ($phStr)")->execute($vals);
            http_response_code(201);
            echo json_encode(['id' => (int)$this->db->lastInsertId(), 'message' => 'Criado']);
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
        $cols = ['id_programa', 'ano', 'data', 'trimestre', 'orgao', 'id_usuario', 'termo_inspecao', 'termo_coleta', 'id_propriedade', 'tipo_imovel', 'municipio', 'cultura', 'latitude', 'longitude', 'status'];
        $set = [];
        $vals = [];
        foreach ($cols as $c) {
            if (array_key_exists($c, $input)) {
                $set[] = "`$c` = ?";
                $vals[] = $input[$c];
            }
        }
        if (isset($input['latitude'], $input['longitude'])) {
            $set[] = "coordenada = ST_GeomFromText(CONCAT('POINT(', ?, ' ', ?, ')'), 4326)";
            $vals[] = (float)$input['longitude'];
            $vals[] = (float)$input['latitude'];
        }
        if (empty($set)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nenhum campo para atualizar']);
            return;
        }
        $vals[] = (int)$id;
        try {
            $stmt = $this->db->prepare("UPDATE relatorio_mapa SET " . implode(', ', $set) . " WHERE id = ?");
            $stmt->execute($vals);
            if ($stmt->rowCount() === 0) {
                http_response_code(404);
                echo json_encode(['error' => 'Não encontrado']);
                return;
            }
            echo json_encode(['message' => 'Atualizado']);
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
        $stmt = $this->db->prepare("DELETE FROM relatorio_mapa WHERE id = ?");
        $stmt->execute([(int)$id]);
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Não encontrado']);
            return;
        }
        echo json_encode(['message' => 'Excluído']);
    }
}
