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
    case 'getArtists':
        if (!in_array('artists.view', $_SESSION['user']['permissions'])) {
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }
        try {
            $artists = Artist::GetAll();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['error' => $e->getMessage()]);
            exit;
        }
        $data = [];
        foreach ($artists as $artist) {
            $data[] = [
                'idArtist'   => $artist->getIdArtist(),
                'artistName' => $artist->getArtistName(),
            ];
        }
        http_response_code(200);
        echo json_encode(['data' => $data]);
        break;

    case 'createArtist':
        if (!in_array('artists.create', $_SESSION['user']['permissions'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }
        $artistName = trim($_POST['artistName'] ?? '');
        if ($artistName === '') {
            http_response_code(400);
            echo json_encode(['message' => 'Artist name is required']);
            exit;
        }
        try {
            $artist = Artist::CreateArtist($artistName);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }
        http_response_code(200);
        echo json_encode(['id' => $artist->getIdArtist(), 'name' => $artist->getArtistName()]);
        break;

    case 'updateArtist':
        if (!in_array('artists.edit', $_SESSION['user']['permissions'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }
        $idArtist   = trim($_POST['idArtist'] ?? '');
        $artistName = trim($_POST['artistName'] ?? '');
        if ($idArtist === '' || $artistName === '') {
            http_response_code(400);
            echo json_encode(['message' => 'Artist ID and name are required']);
            exit;
        }
        try {
            $artist = new Artist($idArtist);
            $artist->UpdateArtist($artistName);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }
        http_response_code(200);
        echo json_encode(['success' => true]);
        break;

    case 'deleteArtist':
        if (!in_array('artists.delete', $_SESSION['user']['permissions'])) {
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }
        $idArtist = trim($_POST['idArtist'] ?? '');
        if ($idArtist === '') {
            http_response_code(400);
            echo json_encode(['message' => 'Artist ID is required']);
            exit;
        }
        try {
            $artist = new Artist($idArtist);
            $artist->DeleteArtist();
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
