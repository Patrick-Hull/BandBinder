<?php
require_once __DIR__ . '/../../../../lib/util_all.php';
header('Content-Type: application/json');
http_response_code(418);

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;
$idUser = $_SESSION['user']['me'];

switch ($action) {
    // ── Get charts visible to logged-in user ──────────────────────────────────
    case 'getMyCharts':
        if (!in_array('charts.view', $_SESSION['user']['permissions'])) {
            http_response_code(403); echo json_encode(['error' => 'Permission denied']); exit;
        }
        try {
            $charts = Chart::GetAllForUser($idUser);
            $db     = new DatabaseManager();

            // Get user's instruments
            $userInstruments = $db->query(
                "SELECT lui.idInstrument, it.idInstrumentFamily
                 FROM `link__user_instrument` lui
                 JOIN `instrument__types` it ON it.idInstrument = lui.idInstrument
                 WHERE lui.idUser = ?",
                [$idUser]
            );
            $instrumentIds = array_column($userInstruments, 'idInstrument');
            $familyIds     = array_unique(array_column($userInstruments, 'idInstrumentFamily'));
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit;
        }

        $data = [];
        foreach ($charts as $chart) {
            $idChart = $chart->getIdChart();

            // Determine which PDF to show: instrument-specific or master
            $myPdfPath = null;
            if (!empty($instrumentIds)) {
                foreach ($chart->getPdfParts() as $part) {
                    if (in_array($part['idInstrument'], $instrumentIds)) {
                        $myPdfPath = $part['pdfPath'];
                        break;
                    }
                }
            }
            if (!$myPdfPath) {
                $myPdfPath = $chart->getPdfPath(); // fall back to master
            }

            // My personal fields
            $myFields = $chart->getUserFields($idUser);

            // Instrument notes from others
            $instrNotes  = $chart->getInstrumentNotesForInstruments($instrumentIds);
            $familyNotes = $chart->getFamilyNotesForFamilies($familyIds);

            // Get artist/arranger names
            $artistName   = $chart->getArtist()   ? $chart->getArtist()->getArtistName()     : '';
            $arrangerName = $chart->getArranger()  ? $chart->getArranger()->getArrangerName() : '';

            $data[] = [
                'idChart'            => $idChart,
                'chartName'          => $chart->getChartName(),
                'artistName'         => $artistName,
                'arrangerName'       => $arrangerName,
                'bpm'                => $chart->getBpm(),
                'chartKey'           => $chart->getChartKey() ?? '',
                'notes'              => $chart->getNotes()    ?? '',
                'myPdfPath'          => $myPdfPath,
                'myRating'           => $myFields['starRating']       ?? null,
                'myPrivateNotes'     => $myFields['privateNotes']     ?? '',
                'myInstrumentNotes'  => $myFields['instrumentNotes']  ?? '',
                'myFamilyNotes'      => $myFields['familyNotes']      ?? '',
                'instrumentNotes'    => $instrNotes,
                'familyNotes'        => $familyNotes,
            ];
        }
        http_response_code(200);
        echo json_encode(['data' => $data]);
        break;

    // ── Save personal fields for a chart ─────────────────────────────────────
    case 'saveMyFields':
        if (!in_array('charts.view', $_SESSION['user']['permissions'])) {
            http_response_code(403); echo json_encode(['message' => 'Permission denied']); exit;
        }
        $idChart         = trim($_POST['idChart']         ?? '');
        $starRating      = isset($_POST['starRating']) && $_POST['starRating'] !== '' ? (int)$_POST['starRating'] : null;
        $privateNotes    = trim($_POST['privateNotes']    ?? '') ?: null;
        $instrumentNotes = trim($_POST['instrumentNotes'] ?? '') ?: null;
        $familyNotes     = trim($_POST['familyNotes']     ?? '') ?: null;

        if ($idChart === '') {
            http_response_code(400); echo json_encode(['message' => 'Chart ID is required']); exit;
        }
        if ($starRating !== null && ($starRating < 1 || $starRating > 5)) {
            http_response_code(400); echo json_encode(['message' => 'Star rating must be between 1 and 5']); exit;
        }
        try {
            $chart = new Chart($idChart);
            $chart->setUserFields($idUser, $starRating, $privateNotes, $instrumentNotes, $familyNotes);
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['message' => $e->getMessage()]); exit;
        }
        http_response_code(200);
        echo json_encode(['success' => true]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
