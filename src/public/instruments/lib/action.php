<?php
require_once __DIR__ . '/../../../lib/util_all.php';
header('Content-Type: application/json');
http_response_code(418);

if(!isset($_SESSION['user'])) {
    http_response_code(401);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;

switch($action) {
    case 'getInstruments':
        if(!in_array('instruments.view', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }

        try {
            $instruments = Instrument::GetAll(true);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }

        $datatable = [];
        foreach($instruments as $instrument){
            $datatable[] = [
                'idInstrument'       => $instrument->getIdInstrument(),
                'idInstrumentFamily' => $instrument->getRawIdInstrumentFamily(),
                'instrumentFamilyName' => $instrument->getIdInstrumentFamily()->getInstrumentFamilyName(),
                'instrumentName'     => $instrument->getInstrumentName(),
            ];
        }
        $responseArray['data'] = $datatable;

        http_response_code(200);
        echo json_encode($responseArray);
        break;

    case 'getInstrumentFamilies':
        if(!in_array('instruments.create', $_SESSION['user']['permissions']) && !in_array('instruments.edit', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }

        try {
            $families = InstrumentFamily::GetAll();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }

        $data = [];
        foreach ($families as $family) {
            $data[] = [
                'value' => $family->getIdInstrumentFamily(),
                'text'  => $family->getInstrumentFamilyName(),
            ];
        }

        http_response_code(200);
        echo json_encode(['data' => $data]);
        break;

    case 'createInstrumentFamily':
        if(!in_array('instrumentFamilies.create', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }

        $familyName = trim($_POST['instrumentFamilyName'] ?? '');
        $familyId = trim($_POST['familyId'] ?? '') ?: null;
        if($familyName === ''){
            http_response_code(400);
            echo json_encode(['message' => 'Instrument family name is required']);
            exit;
        }

        try {
            $family = InstrumentFamily::CreateInstrumentFamily($familyName, $familyId);
        } catch (Exception $e) {
            // Check if it's a duplicate key error - that's okay for preseeding
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                http_response_code(200);
                echo json_encode(['id' => $familyId, 'name' => $familyName, 'skipped' => true]);
                exit;
            }
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }

        http_response_code(200);
        echo json_encode([
            'id'   => $family->getIdInstrumentFamily(),
            'name' => $family->getInstrumentFamilyName(),
        ]);
        break;

    case 'createInstrument':
        if(!in_array('instruments.create', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }

        $instrumentName    = trim($_POST['instrumentName'] ?? '');
        $idInstrumentFamily = trim($_POST['idInstrumentFamily'] ?? '');
        $instrumentId = trim($_POST['instrumentId'] ?? '') ?: null;
        $sortOrder = isset($_POST['sortOrder']) && $_POST['sortOrder'] !== '' ? (int)$_POST['sortOrder'] : null;

        if($instrumentName === '' || $idInstrumentFamily === ''){
            http_response_code(400);
            echo json_encode(['message' => 'Instrument name and family are required']);
            exit;
        }

        try {
            $family     = new InstrumentFamily($idInstrumentFamily);
            $instrument = Instrument::CreateInstrument($instrumentName, $family, $instrumentId, $sortOrder);
        } catch (Exception $e) {
            // Check if it's a duplicate - that's okay for preseeding
            if (strpos($e->getMessage(), 'Duplicate entry') !== false || strpos($e->getMessage(), 'Duplicate key') !== false) {
                http_response_code(200);
                echo json_encode(['id' => $instrumentId, 'skipped' => true]);
                exit;
            }
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }

        http_response_code(200);
        echo json_encode(['id' => $instrument->getIdInstrument()]);
        break;

    case 'deleteInstrument':
        if(!in_array('instruments.delete', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }

        $idInstrument = trim($_POST['idInstrument'] ?? '');
        if($idInstrument === ''){
            http_response_code(400);
            echo json_encode(['message' => 'Instrument ID is required']);
            exit;
        }

        try {
            $instrument = new Instrument($idInstrument);
            $instrument->DeleteInstrument();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }

        http_response_code(200);
        echo json_encode(['success' => true]);
        break;

    case 'updateInstrument':
        if(!in_array('instruments.edit', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }

        $idInstrument       = trim($_POST['idInstrument'] ?? '');
        $instrumentName     = trim($_POST['instrumentName'] ?? '');
        $idInstrumentFamily = trim($_POST['idInstrumentFamily'] ?? '');

        if($idInstrument === '' || $instrumentName === '' || $idInstrumentFamily === ''){
            http_response_code(400);
            echo json_encode(['message' => 'Instrument ID, name and family are required']);
            exit;
        }

        try {
            $instrument = new Instrument($idInstrument);
            $family     = new InstrumentFamily($idInstrumentFamily);
            $instrument->UpdateInstrument($instrumentName, $family);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }

        http_response_code(200);
        echo json_encode(['success' => true]);
        break;

    case 'updateInstrumentOrder':
        if(!in_array('instruments.edit', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }

        $orderedIds = $_POST['orderedIds'] ?? [];
        if(!is_array($orderedIds) || empty($orderedIds)){
            http_response_code(400);
            echo json_encode(['message' => 'orderedIds array is required']);
            exit;
        }

        try {
            Instrument::UpdateOrder($orderedIds);
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