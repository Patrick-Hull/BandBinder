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
        $categoryFilter = trim($_POST['categoryFilter'] ?? '');
        try {
            $db = new DatabaseManager();
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

            // Build query
            $query = "SELECT DISTINCT c.*
                 FROM `charts` c
                 WHERE (
                     NOT EXISTS (SELECT 1 FROM `chart__pdf_parts` cpp WHERE cpp.idChart = c.idChart)
                     OR
                     EXISTS (
                         SELECT 1
                         FROM `chart__pdf_parts` cpp
                         JOIN `link__user_instrument` lui ON lui.idInstrument = cpp.idInstrument
                         WHERE cpp.idChart = c.idChart AND lui.idUser = ?
                     )
                 ) AND c.isActive = ?";
            $params = [$idUser, true];

            if ($categoryFilter !== '') {
                $query .= " AND c.idChart IN (SELECT idChart FROM `link__chart_category` WHERE idCategory = ?)";
                $params[] = $categoryFilter;
            }
            $query .= " ORDER BY c.chartName";

            $rows = $db->query($query, $params);
            $charts = array_map(fn($row) => new Chart($row['idChart']), $rows);

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
        $chartIds = [];
        foreach ($charts as $chart) {
            $idChart = $chart->getIdChart();
            $chartIds[] = $idChart;

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
                'audioPath'          => $chart->getAudioPath() ?? '',
                'myRating'           => $myFields['starRating']       ?? null,
                'myPrivateNotes'     => $myFields['privateNotes']     ?? '',
                'myInstrumentNotes'  => $myFields['instrumentNotes']  ?? '',
                'myFamilyNotes'      => $myFields['familyNotes']      ?? '',
                'instrumentNotes'    => $instrNotes,
                'familyNotes'        => $familyNotes,
            ];
        }
        // Get categories for all charts
        $categoriesMap = [];
        if (!empty($chartIds)) {
            $categoriesMap = Category::GetByCharts($chartIds);
        }
        // Get aggregate ratings for all charts
        $ratingsMap = [];
        if (!empty($chartIds)) {
            try {
                $ratings = $db->query(
                    "SELECT idChart, AVG(starRating) as avgRating, COUNT(*) as ratingCount
                     FROM `chart__user_fields`
                     WHERE idChart IN (" . implode(',', array_fill(0, count($chartIds), '?')) . ") AND starRating IS NOT NULL
                     GROUP BY idChart",
                    $chartIds
                );
                foreach ($ratings as $r) {
                    $ratingsMap[$r['idChart']] = [
                        'avgRating' => round($r['avgRating'], 1),
                        'ratingCount' => (int)$r['ratingCount'],
                    ];
                }
            } catch (Exception $e) {
                // Ignore rating errors
            }
        }
        http_response_code(200);
        echo json_encode(['data' => $data, 'categoriesMap' => $categoriesMap, 'ratingsMap' => $ratingsMap]);
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

    // ── Categories list for dropdown ─────────────────────────────────────────────
    case 'getCategoriesList':
        if (!in_array('charts.view', $_SESSION['user']['permissions'])) {
            http_response_code(403); echo json_encode(['error' => 'Permission denied']); exit;
        }
        try {
            $categories = Category::GetAll();
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit;
        }
        $data = array_map(fn($c) => ['value' => $c->getIdCategory(), 'text' => $c->getCategoryName()], $categories);
        http_response_code(200);
        echo json_encode(['data' => $data]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
