<?php
require_once __DIR__ . '/../../../lib/util_all.php';
header('Content-Type: application/json');
http_response_code(418);

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;

switch ($action) {
    case 'getArrangers':
        if (!in_array('arrangers.view', $_SESSION['user']['permissions'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }
        try {
            $arrangers = Arranger::GetAll();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
        $data = [];
        foreach ($arrangers as $arranger) {
            $data[] = [
                'idArranger'   => $arranger->getIdArranger(),
                'arrangerName' => $arranger->getArrangerName(),
            ];
        }
        http_response_code(200);
        echo json_encode(['data' => $data]);
        break;

    case 'createArranger':
        if (!in_array('arrangers.create', $_SESSION['user']['permissions'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }
        $arrangerName = trim($_POST['arrangerName'] ?? '');
        if ($arrangerName === '') {
            http_response_code(400);
            echo json_encode(['message' => 'Arranger name is required']);
            exit;
        }
        try {
            $arranger = Arranger::CreateArranger($arrangerName);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }
        http_response_code(200);
        echo json_encode(['id' => $arranger->getIdArranger(), 'name' => $arranger->getArrangerName()]);
        break;

    case 'updateArranger':
        if (!in_array('arrangers.edit', $_SESSION['user']['permissions'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }
        $idArranger   = trim($_POST['idArranger'] ?? '');
        $arrangerName = trim($_POST['arrangerName'] ?? '');
        if ($idArranger === '' || $arrangerName === '') {
            http_response_code(400);
            echo json_encode(['message' => 'Arranger ID and name are required']);
            exit;
        }
        try {
            $arranger = new Arranger($idArranger);
            $arranger->UpdateArranger($arrangerName);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }
        http_response_code(200);
        echo json_encode(['success' => true]);
        break;

    case 'deleteArranger':
        if (!in_array('arrangers.delete', $_SESSION['user']['permissions'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }
        $idArranger = trim($_POST['idArranger'] ?? '');
        if ($idArranger === '') {
            http_response_code(400);
            echo json_encode(['message' => 'Arranger ID is required']);
            exit;
        }
        try {
            $arranger = new Arranger($idArranger);
            $arranger->DeleteArranger();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }
        http_response_code(200);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
