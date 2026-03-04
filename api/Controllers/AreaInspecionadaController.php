<?php
declare(strict_types=1);

class AreaInspecionadaController {
    private PDO $db;

    public function __construct(PDO $db) {
        $this->db = $db;
    }

    public function index(?string $idTermoInspecao = null): void {
        if ($idTermoInspecao) {
            $stmt = $this->db->prepare("SELECT * FROM area_inspecionada WHERE id_termo_inspecao = ?");
            $stmt->execute([$idTermoInspecao]);
        } else {
            $limit = min(500, (int)($_GET['limit'] ?? 100));
            $offset = max(0, (int)($_GET['offset'] ?? 0));
            $stmt = $this->db->prepare("SELECT * FROM area_inspecionada LIMIT ? OFFSET ?");
            $stmt->execute([$limit, $offset]);
        }
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($rows);
    }

    public function create(): void {
        $input = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $required = ['id_termo_inspecao', 'id_programa', 'numero_plantas', 'numero_inspecionadas'];
        foreach ($required as $k) {
            if (empty($input[$k]) && $input[$k] !== 0) {
                http_response_code(400);
                echo json_encode(['error' => "Campo obrigatório: $k"]);
                return;
            }
        }
        $cols = ['id', 'id_termo_inspecao', 'id_programa', 'tipo_area', 'nome_local', 'especie', 'variedade', 'material_multiplicacao', 'origem', 'idade_plantio', 'area_plantada', 'numero_plantas', 'numero_inspecionadas', 'numero_suspeitas', 'coleta_amostra', 'obs', 'identificacao_amostra', 'resultado', 'associado', 'latitude', 'longitude'];
        $data = [];
        foreach ($cols as $c) {
            if (array_key_exists($c, $input)) $data[$c] = $input[$c];
        }
        if (empty($data['id'] ?? '')) {
            $data['id'] = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex(random_bytes(16)), 4));
        }
        $data['obs'] = $data['obs'] ?? '';
        $data['numero_suspeitas'] = $data['numero_suspeitas'] ?? 0;
        $data['coleta_amostra'] = $data['coleta_amostra'] ?? 0;
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $colsStr = implode('`, `', array_keys($data));
        try {
            $this->db->prepare("INSERT INTO area_inspecionada (`$colsStr`) VALUES ($placeholders)")->execute(array_values($data));
            http_response_code(201);
            echo json_encode(['id' => $data['id'], 'id_termo_inspecao' => $data['id_termo_inspecao'], 'message' => 'Criado']);
        } catch (PDOException $e) {
            http_response_code(400);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    public function update(string $idTermoInspecao, string $id): void {
        $input = json_decode((string) file_get_contents('php://input'), true) ?: [];
        $cols = ['tipo_area', 'nome_local', 'especie', 'variedade', 'material_multiplicacao', 'origem', 'idade_plantio', 'area_plantada', 'numero_plantas', 'numero_inspecionadas', 'numero_suspeitas', 'coleta_amostra', 'obs', 'identificacao_amostra', 'resultado', 'associado', 'latitude', 'longitude'];
        $set = [];
        $vals = [];
        foreach ($cols as $c) {
            if (array_key_exists($c, $input)) {
                $set[] = "`$c` = ?";
                $vals[] = $input[$c];
            }
        }
        if (empty($set)) {
            http_response_code(400);
            echo json_encode(['error' => 'Nenhum campo para atualizar']);
            return;
        }
        $vals[] = $idTermoInspecao;
        $vals[] = $id;
        $sql = "UPDATE area_inspecionada SET " . implode(', ', $set) . " WHERE id_termo_inspecao = ? AND id = ?";
        try {
            $stmt = $this->db->prepare($sql);
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

    public function delete(string $idTermoInspecao, string $id): void {
        $stmt = $this->db->prepare("DELETE FROM area_inspecionada WHERE id_termo_inspecao = ? AND id = ?");
        $stmt->execute([$idTermoInspecao, $id]);
        if ($stmt->rowCount() === 0) {
            http_response_code(404);
            echo json_encode(['error' => 'Não encontrado']);
            return;
        }
        echo json_encode(['message' => 'Excluído']);
    }
}
