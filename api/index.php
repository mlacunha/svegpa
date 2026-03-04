<?php
/**
 * API REST Sanveg - Entry Point
 * PHP 8.x | PDO MySQL
 */

declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/Auth.php';
require_once __DIR__ . '/Controllers/AuthController.php';
require_once __DIR__ . '/Controllers/CrudController.php';
require_once __DIR__ . '/Controllers/AreaInspecionadaController.php';
require_once __DIR__ . '/Controllers/RelatorioMapaController.php';

$db = (new Database())->getConnection();
if (!$db) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro de conexão com o banco']);
    exit;
}

$router = new Router();
$auth = new Auth($db);

// Auth (público)
$router->post('/api/auth/login', fn() => (new AuthController($auth))->login());

// Rotas protegidas - CRUD
$protected = function (string $method, string $path, callable $handler) use ($router, $auth) {
    $router->add($method, $path, function (...$params) use ($auth, $handler) {
        $user = $auth->validateRequest();
        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Não autorizado']);
            return;
        }
        $handler($user, ...$params);
    });
};

// CRUD genérico por recurso
$crudRoutes = [
    'programas'      => ['id' => 'id', 'pk_type' => 'char'],
    'propriedades'   => ['id' => 'id', 'pk_type' => 'char'],
    'produtores'     => ['id' => 'id', 'pk_type' => 'char'],
    'produtos'       => ['id' => 'id', 'pk_type' => 'int'],
    'hospedeiros'    => ['id' => 'id', 'pk_type' => 'char'],
    'orgaos'         => ['id' => 'id', 'pk_type' => 'char'],
    'orgaos_tipos'   => ['id' => 'id', 'pk_type' => 'char'],
    'cargos'         => ['id' => 'id', 'pk_type' => 'char'],
    'estados'        => ['id' => 'id', 'pk_type' => 'int'],
    'municipios'     => ['id' => 'id', 'pk_type' => 'int'],
    'normas'         => ['id' => 'id', 'pk_type' => 'char'],
    'unidades'       => ['id' => 'id', 'pk_type' => 'char'],
    'termo_inspecao' => ['id' => 'id', 'pk_type' => 'char'],
    'dashboard_filtros' => ['id' => 'id', 'pk_type' => 'int'],
];

$relMapaCtrl = new RelatorioMapaController($db);
$protected('GET', '/api/relatorio_mapa', fn($u) => $relMapaCtrl->index());
$protected('GET', '/api/relatorio_mapa/{id}', fn($u, $id) => $relMapaCtrl->show($id));
$protected('POST', '/api/relatorio_mapa', fn($u) => $relMapaCtrl->create());
$protected('PUT', '/api/relatorio_mapa/{id}', fn($u, $id) => $relMapaCtrl->update($id));
$protected('DELETE', '/api/relatorio_mapa/{id}', fn($u, $id) => $relMapaCtrl->delete($id));

foreach ($crudRoutes as $resource => $config) {
    $ctrl = new CrudController($db, $resource, $config);
    $protected('GET', "/api/{$resource}", fn($u) => $ctrl->index());
    $protected('GET', "/api/{$resource}/{id}", fn($u, $id) => $ctrl->show($id));
    $protected('POST', "/api/{$resource}", fn($u) => $ctrl->create());
    $protected('PUT', "/api/{$resource}/{id}", fn($u, $id) => $ctrl->update($id));
    $protected('DELETE', "/api/{$resource}/{id}", fn($u, $id) => $ctrl->delete($id));
}

// area_inspecionada - PK composta (id_termo_inspecao, id)
$areaCtrl = new AreaInspecionadaController($db);
$protected('GET', '/api/area-inspecionada', fn($u) => $areaCtrl->index());
$protected('GET', '/api/area-inspecionada/{id_ti}', fn($u, $idTi) => $areaCtrl->index($idTi));
$protected('POST', '/api/area-inspecionada', fn($u) => $areaCtrl->create());
$protected('PUT', '/api/area-inspecionada/{id_ti}/{id}', fn($u, $idTi, $id) => $areaCtrl->update($idTi, $id));
$protected('DELETE', '/api/area-inspecionada/{id_ti}/{id}', fn($u, $idTi, $id) => $areaCtrl->delete($idTi, $id));

// reg_atividade e reg_metas
$regAtivCtrl = new CrudController($db, 'reg_atividade', ['id' => 'id', 'pk_type' => 'int']);
$regMetasCtrl = new CrudController($db, 'reg_metas', ['id' => 'id', 'pk_type' => 'int']);
$protected('GET', '/api/reg-atividade', fn($u) => $regAtivCtrl->index());
$protected('GET', '/api/reg-atividade/{id}', fn($u, $id) => $regAtivCtrl->show($id));
$protected('POST', '/api/reg-atividade', fn($u) => $regAtivCtrl->create());
$protected('PUT', '/api/reg-atividade/{id}', fn($u, $id) => $regAtivCtrl->update($id));
$protected('DELETE', '/api/reg-atividade/{id}', fn($u, $id) => $regAtivCtrl->delete($id));

$protected('GET', '/api/reg-metas', fn($u) => $regMetasCtrl->index());
$protected('GET', '/api/reg-metas/{id}', fn($u, $id) => $regMetasCtrl->show($id));
$protected('POST', '/api/reg-metas', fn($u) => $regMetasCtrl->create());
$protected('PUT', '/api/reg-metas/{id}', fn($u, $id) => $regMetasCtrl->update($id));
$protected('DELETE', '/api/reg-metas/{id}', fn($u, $id) => $regMetasCtrl->delete($id));

// Health check (público)
$router->get('/api/health', fn() => (static function () { echo json_encode(['status' => 'ok', 'timestamp' => date('c')]); })());

$router->dispatch();
