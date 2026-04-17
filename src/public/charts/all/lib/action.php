<?php
require_once __DIR__ . '/../../../../lib/util_all.php';
header('Content-Type: application/json');
http_response_code(418);

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;

// Upload directory (relative to web root for URL generation)
define('CHART_UPLOAD_DIR', __DIR__ . '/../../../uploads/charts/');
define('CHART_UPLOAD_URL', '/uploads/charts/');

function ensureChartDir(string $idChart): string
{
    $dir = CHART_UPLOAD_DIR . $idChart . '/';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new Exception('Could not create upload directory: ' . $dir . ' — check that /public/uploads/charts/ exists and is writable by www-data');
    }
    return $dir;
}

function ensurePartsDir(string $idChart): string
{
    $dir = CHART_UPLOAD_DIR . $idChart . '/parts/';
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
        throw new Exception('Could not create parts directory: ' . $dir);
    }
    return $dir;
}

/**
 * Re-encode a PDF file in-place to PDF 1.4 using GhostScript.
 * This makes the file compatible with FPDI's free parser.
 * Silently skips if GhostScript is not available.
 */
function reEncodePdfForFpdi(string $pdfPath): void
{
    $gs = null;
    foreach (['/usr/bin/gs', '/usr/local/bin/gs'] as $candidate) {
        if (is_executable($candidate)) { $gs = $candidate; break; }
    }
    if ($gs === null) return;

    $tmpPath = $pdfPath . '.reencode.pdf';
    $cmd = sprintf(
        '%s -dBATCH -dNOPAUSE -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -sOutputFile=%s %s 2>/dev/null',
        escapeshellcmd($gs),
        escapeshellarg($tmpPath),
        escapeshellarg($pdfPath)
    );
    exec($cmd, $output, $exitCode);
    if ($exitCode === 0 && file_exists($tmpPath) && filesize($tmpPath) > 0) {
        rename($tmpPath, $pdfPath);
    } elseif (file_exists($tmpPath)) {
        unlink($tmpPath);
    }
}

/**
 * Save an uploaded PDF file for a chart. Returns the web-accessible URL path.
 */
function saveMasterPdf(string $idChart, array $file): string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed (error code ' . $file['error'] . ')');
    }
    if ($file['type'] !== 'application/pdf' && strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') {
        throw new Exception('Only PDF files are accepted');
    }
    $dir  = ensureChartDir($idChart);
    $dest = $dir . 'master.pdf';
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new Exception('Failed to save uploaded file');
    }
    reEncodePdfForFpdi($dest);
    return CHART_UPLOAD_URL . $idChart . '/master.pdf';
}

/**
 * Save an uploaded instrument-specific PDF. Returns the web URL path.
 */
function saveInstrumentPdf(string $idChart, string $idInstrument, array $file): string
{
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('File upload failed (error code ' . $file['error'] . ')');
    }
    if ($file['type'] !== 'application/pdf' && strtolower(pathinfo($file['name'], PATHINFO_EXTENSION)) !== 'pdf') {
        throw new Exception('Only PDF files are accepted');
    }
    $dir  = ensurePartsDir($idChart);
    $dest = $dir . $idInstrument . '.pdf';
    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        throw new Exception('Failed to save uploaded file');
    }
    return CHART_UPLOAD_URL . $idChart . '/parts/' . $idInstrument . '.pdf';
}

/**
 * Use FPDI to extract a specific list of pages from the master PDF and save as instrument PDF.
 */
function splitPdfByPageList(string $idChart, string $idInstrument, array $pages): string
{
    if (!class_exists('\setasign\Fpdi\Fpdi')) {
        throw new Exception('PDF splitting library (FPDI) is not installed. Run: composer install');
    }

    $sourcePath = CHART_UPLOAD_DIR . $idChart . '/master.pdf';
    if (!file_exists($sourcePath)) {
        throw new Exception('Master PDF not found on disk');
    }

    $outputDir  = ensurePartsDir($idChart);
    $outputPath = $outputDir . $idInstrument . '.pdf';

    $pdf = new \setasign\Fpdi\Fpdi();
    try {
        $totalPages = $pdf->setSourceFile($sourcePath);
    } catch (\Exception $e) {
        // PDF may use unsupported compression — try re-encoding to PDF 1.4 first
        reEncodePdfForFpdi($sourcePath);
        $pdf        = new \setasign\Fpdi\Fpdi();
        $totalPages = $pdf->setSourceFile($sourcePath);
    }

    foreach ($pages as $page) {
        $page = (int)$page;
        if ($page < 1 || $page > $totalPages) continue;
        $tplId = $pdf->importPage($page);
        $size  = $pdf->getTemplateSize($tplId);
        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
        $pdf->useTemplate($tplId, 0, 0, null, null, true);
    }

    $pdf->Output('F', $outputPath);

    return CHART_UPLOAD_URL . $idChart . '/parts/' . $idInstrument . '.pdf';
}

switch ($action) {
    // ── List all charts ───────────────────────────────────────────────────────
    case 'getCharts':
        if (!in_array('charts.viewAll', $_SESSION['user']['permissions'])) {
            http_response_code(403); echo json_encode(['error' => 'Permission denied']); exit;
        }
        try {
            $db = new DatabaseManager();
            $rows = $db->query(
                "SELECT c.*, a.artistName, ar.arrangerName
                 FROM `charts` c
                 LEFT JOIN `artists`   a  ON a.idArtist   = c.idArtist
                 LEFT JOIN `arrangers` ar ON ar.idArranger = c.idArranger
                 ORDER BY c.chartName"
            );
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit;
        }
        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'idChart'      => $row['idChart'],
                'chartName'    => $row['chartName'],
                'idArtist'     => $row['idArtist'],
                'artistName'   => $row['artistName']   ?? '',
                'idArranger'   => $row['idArranger'],
                'arrangerName' => $row['arrangerName'] ?? '',
                'bpm'          => $row['bpm'],
                'duration'     => isset($row['duration']) ? (int)$row['duration'] : null,
                'chartKey'     => $row['chartKey']     ?? '',
                'notes'        => $row['notes']        ?? '',
                'pdfPath'      => $row['pdfPath']      ?? '',
                'hasPdf'       => !empty($row['pdfPath']),
                'audioPath'    => $row['audioPath']    ?? '',
                'hasAudio'     => !empty($row['audioPath']),
                'isActive'     => !empty($row['isActive']),
            ];
        }
        http_response_code(200);
        echo json_encode(['data' => $data]);
        break;

    // ── Create chart ─────────────────────────────────────────────────────────
    case 'createChart':
        if (!in_array('charts.create', $_SESSION['user']['permissions'])) {
            http_response_code(403); echo json_encode(['message' => 'Permission denied']); exit;
        }
        $chartName  = trim($_POST['chartName'] ?? '');
        if ($chartName === '') {
            http_response_code(400); echo json_encode(['message' => 'Chart name is required']); exit;
        }
        $idArtist   = trim($_POST['idArtist']   ?? '') ?: null;
        $idArranger = trim($_POST['idArranger'] ?? '') ?: null;
        $bpm        = isset($_POST['bpm'])      && $_POST['bpm']      !== '' ? (int)$_POST['bpm']      : null;
        $duration   = isset($_POST['duration']) && $_POST['duration'] !== '' ? (int)$_POST['duration'] : null;
        $chartKey   = trim($_POST['chartKey']   ?? '') ?: null;
        $notes      = trim($_POST['notes']      ?? '') ?: null;

        try {
            $chart = Chart::CreateChart($chartName, $idArtist, $idArranger, $bpm, $duration, $chartKey, $notes);
            if (isset($_FILES['pdfFile']) && $_FILES['pdfFile']['error'] !== UPLOAD_ERR_NO_FILE) {
                $pdfPath = saveMasterPdf($chart->getIdChart(), $_FILES['pdfFile']);
                $chart->SetPdfPath($pdfPath);
            }
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['message' => $e->getMessage()]); exit;
        }
        http_response_code(200);
        echo json_encode(['id' => $chart->getIdChart()]);
        break;

    // ── Update chart ─────────────────────────────────────────────────────────
    case 'updateChart':
        if (!in_array('charts.edit', $_SESSION['user']['permissions'])) {
            http_response_code(403); echo json_encode(['message' => 'Permission denied']); exit;
        }
        $idChart    = trim($_POST['idChart']    ?? '');
        $chartName  = trim($_POST['chartName']  ?? '');
        if ($idChart === '' || $chartName === '') {
            http_response_code(400); echo json_encode(['message' => 'Chart ID and name are required']); exit;
        }
        $idArtist   = trim($_POST['idArtist']   ?? '') ?: null;
        $idArranger = trim($_POST['idArranger'] ?? '') ?: null;
        $bpm        = isset($_POST['bpm'])      && $_POST['bpm']      !== '' ? (int)$_POST['bpm']      : null;
        $duration   = isset($_POST['duration']) && $_POST['duration'] !== '' ? (int)$_POST['duration'] : null;
        $chartKey   = trim($_POST['chartKey']   ?? '') ?: null;
        $notes      = trim($_POST['notes']      ?? '') ?: null;

        try {
            $chart = new Chart($idChart);
            $chart->UpdateChart($chartName, $idArtist, $idArranger, $bpm, $duration, $chartKey, $notes);
            if (isset($_FILES['pdfFile']) && $_FILES['pdfFile']['error'] !== UPLOAD_ERR_NO_FILE) {
                $pdfPath = saveMasterPdf($chart->getIdChart(), $_FILES['pdfFile']);
                $chart->SetPdfPath($pdfPath);
            }
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['message' => $e->getMessage()]); exit;
        }
        http_response_code(200);
        echo json_encode(['success' => true]);
        break;

    // ── Delete chart ─────────────────────────────────────────────────────────
    case 'deleteChart':
        if (!in_array('charts.delete', $_SESSION['user']['permissions'])) {
            http_response_code(403); echo json_encode(['message' => 'Permission denied']); exit;
        }
        $idChart = trim($_POST['idChart'] ?? '');
        if ($idChart === '') {
            http_response_code(400); echo json_encode(['message' => 'Chart ID is required']); exit;
        }
        try {
            $chart = new Chart($idChart);
            $chart->DeleteChart();
            // Remove upload directory
            $chartDir = CHART_UPLOAD_DIR . $idChart;
            if (is_dir($chartDir)) {
                array_map('unlink', glob($chartDir . '/parts/*'));
                @rmdir($chartDir . '/parts');
                array_map('unlink', glob($chartDir . '/*'));
                @rmdir($chartDir);
            }
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['message' => $e->getMessage()]); exit;
        }
        http_response_code(200);
        echo json_encode(['success' => true]);
        break;

    // ── Toggle chart activation ─────────────────────────────────────────────────────────
    case 'toggleActivation':
        if (!in_array('charts.edit', $_SESSION['user']['permissions'])) {
            http_response_code(403); echo json_encode(['message' => 'Permission denied']); exit;
        }
        $idChart = trim($_POST['idChart'] ?? '');
        if ($idChart === '') {
            http_response_code(400); echo json_encode(['message' => 'Chart ID is required']); exit;
        }
        try {
            $chart = new Chart($idChart);
            if($chart->IsChartActive()){
                $chart->DeactivateChart();
                $action = 'deactivated';
            } else {
                $chart->ActivateChart();
                $action = 'activated';
            }
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['message' => $e->getMessage()]); exit;
        }
        http_response_code(200);
        echo json_encode(['success' => true, "action" => $action]);
        break;

    // ── Artists list for dropdown ─────────────────────────────────────────────
    case 'getArtistsList':
        try {
            $artists = Artist::GetAll();
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit;
        }
        $data = array_map(fn($a) => ['value' => $a->getIdArtist(), 'text' => $a->getArtistName()], $artists);
        http_response_code(200);
        echo json_encode(['data' => $data]);
        break;

    // ── Arrangers list for dropdown ───────────────────────────────────────────
    case 'getArrangersList':
        try {
            $arrangers = Arranger::GetAll();
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit;
        }
        $data = array_map(fn($a) => ['value' => $a->getIdArranger(), 'text' => $a->getArrangerName()], $arrangers);
        http_response_code(200);
        echo json_encode(['data' => $data]);
        break;

    // ── Get PDF parts data (instruments + current assignments + page count) ───
    case 'getPdfPartsData':
        if (!in_array('charts.edit', $_SESSION['user']['permissions'])) {
            http_response_code(403); echo json_encode(['message' => 'Permission denied']); exit;
        }
        $idChart = trim($_POST['idChart'] ?? '');
        if ($idChart === '') {
            http_response_code(400); echo json_encode(['message' => 'Chart ID is required']); exit;
        }
        try {
            $chart       = new Chart($idChart);
            $parts       = $chart->getPdfParts();
            $db          = new DatabaseManager();
            $instruments = $db->query(
                "SELECT idInstrument, instrumentName FROM `instrument__types` ORDER BY `sortOrder` IS NULL, `sortOrder`, `instrumentName`"
            );
            $pageCount = 0;
            if ($chart->getPdfPath()) {
                $masterFile = CHART_UPLOAD_DIR . $idChart . '/master.pdf';
                if (file_exists($masterFile) && class_exists('\setasign\Fpdi\Fpdi')) {
                    $fpdi = new \setasign\Fpdi\Fpdi();
                    $pageCount = $fpdi->setSourceFile($masterFile);
                }
            }
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['message' => $e->getMessage()]); exit;
        }
        // Decode the JSON pages column so JS receives a native array
        foreach ($parts as &$part) {
            $part['pages'] = isset($part['pages']) ? json_decode($part['pages'], true) ?? [] : [];
        }
        unset($part);
        http_response_code(200);
        echo json_encode([
            'instruments'  => $instruments,
            'parts'        => $parts,
            'pageCount'    => $pageCount,
            'masterPdfUrl' => $chart->getPdfPath(),
        ]);
        break;

    // ── Upload individual instrument PDF ──────────────────────────────────────
    case 'uploadInstrumentPdf':
        if (!in_array('charts.edit', $_SESSION['user']['permissions'])) {
            http_response_code(403); echo json_encode(['message' => 'Permission denied']); exit;
        }
        $idChart      = trim($_POST['idChart']      ?? '');
        $idInstrument = trim($_POST['idInstrument'] ?? '');
        if ($idChart === '' || $idInstrument === '') {
            http_response_code(400); echo json_encode(['message' => 'Chart ID and instrument ID are required']); exit;
        }
        if (!isset($_FILES['pdfFile']) || $_FILES['pdfFile']['error'] === UPLOAD_ERR_NO_FILE) {
            http_response_code(400); echo json_encode(['message' => 'No file uploaded']); exit;
        }
        try {
            $chart   = new Chart($idChart);
            $pdfPath = saveInstrumentPdf($idChart, $idInstrument, $_FILES['pdfFile']);
            $chart->setPdfPart($idInstrument, $pdfPath);
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['message' => $e->getMessage()]); exit;
        }
        http_response_code(200);
        echo json_encode(['success' => true, 'pdfPath' => $pdfPath]);
        break;

    // ── Remove instrument PDF assignment ──────────────────────────────────────
    case 'removeInstrumentPdf':
        if (!in_array('charts.edit', $_SESSION['user']['permissions'])) {
            http_response_code(403); echo json_encode(['message' => 'Permission denied']); exit;
        }
        $idChart      = trim($_POST['idChart']      ?? '');
        $idInstrument = trim($_POST['idInstrument'] ?? '');
        if ($idChart === '' || $idInstrument === '') {
            http_response_code(400); echo json_encode(['message' => 'Chart ID and instrument ID are required']); exit;
        }
        try {
            $chart = new Chart($idChart);
            // Delete physical file
            $filePath = CHART_UPLOAD_DIR . $idChart . '/parts/' . $idInstrument . '.pdf';
            if (file_exists($filePath)) unlink($filePath);
            $chart->removePdfPart($idInstrument);
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['message' => $e->getMessage()]); exit;
        }
        http_response_code(200);
        echo json_encode(['success' => true]);
        break;

    // ── Split master PDF into instrument parts (page-list format) ────────────
    case 'splitChartPdf':
        if (!in_array('charts.edit', $_SESSION['user']['permissions'])) {
            http_response_code(403); echo json_encode(['message' => 'Permission denied']); exit;
        }
        $idChart     = trim($_POST['idChart'] ?? '');
        $assignments = json_decode($_POST['assignments'] ?? '[]', true);
        if ($idChart === '' || !is_array($assignments) || empty($assignments)) {
            http_response_code(400); echo json_encode(['message' => 'Chart ID and assignments are required']); exit;
        }
        try {
            $chart = new Chart($idChart);
            if (!$chart->getPdfPath()) {
                throw new Exception('This chart has no master PDF to split');
            }
            $count   = 0;
            $results = [];
            foreach ($assignments as $assignment) {
                $idInstrument = trim($assignment['idInstrument'] ?? '');
                $pages        = array_map('intval', $assignment['pages'] ?? []);
                $pages        = array_filter($pages, fn($p) => $p >= 1);
                if (!$idInstrument || empty($pages)) continue;

                $pdfPath = splitPdfByPageList($idChart, $idInstrument, array_values($pages));
                $chart->setPdfPart($idInstrument, $pdfPath, array_values($pages));
                $results[] = ['idInstrument' => $idInstrument, 'pdfPath' => $pdfPath];
                $count++;
            }
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['message' => $e->getMessage()]); exit;
        }
        http_response_code(200);
        echo json_encode(['success' => true, 'count' => $count, 'results' => $results]);
        break;

    // ── Upload audio file ─────────────────────────────────────────────────────
    case 'uploadChartAudio':
        if (!in_array('charts.edit', $_SESSION['user']['permissions'])) {
            http_response_code(403); echo json_encode(['message' => 'Permission denied']); exit;
        }
        $idChart = trim($_POST['idChart'] ?? '');
        if ($idChart === '') {
            http_response_code(400); echo json_encode(['message' => 'Chart ID is required']); exit;
        }
        if (!isset($_FILES['audioFile']) || $_FILES['audioFile']['error'] === UPLOAD_ERR_NO_FILE) {
            http_response_code(400); echo json_encode(['message' => 'No file uploaded']); exit;
        }
        $file = $_FILES['audioFile'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            http_response_code(400); echo json_encode(['message' => 'Upload error code ' . $file['error']]); exit;
        }
        $allowedExts  = ['mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac'];
        $allowedMimes = ['audio/mpeg', 'audio/wav', 'audio/wave', 'audio/ogg', 'audio/mp4', 'audio/x-m4a',
                         'audio/flac', 'audio/x-flac', 'audio/aac', 'video/mp4', 'application/octet-stream'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExts)) {
            http_response_code(400); echo json_encode(['message' => 'Unsupported audio format. Allowed: ' . implode(', ', $allowedExts)]); exit;
        }
        try {
            $dir  = ensureChartDir($idChart);
            // Remove any existing audio file
            foreach ($allowedExts as $oldExt) {
                $old = $dir . 'audio.' . $oldExt;
                if (file_exists($old)) unlink($old);
            }
            $dest = $dir . 'audio.' . $ext;
            if (!move_uploaded_file($file['tmp_name'], $dest)) {
                throw new Exception('Failed to save audio file');
            }
            $audioPath = CHART_UPLOAD_URL . $idChart . '/audio.' . $ext;
            $chart = new Chart($idChart);
            $chart->SetAudioPath($audioPath);
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['message' => $e->getMessage()]); exit;
        }
        http_response_code(200);
        echo json_encode(['success' => true, 'audioPath' => $audioPath]);
        break;

    // ── Delete audio file ─────────────────────────────────────────────────────
    case 'deleteChartAudio':
        if (!in_array('charts.edit', $_SESSION['user']['permissions'])) {
            http_response_code(403); echo json_encode(['message' => 'Permission denied']); exit;
        }
        $idChart = trim($_POST['idChart'] ?? '');
        if ($idChart === '') {
            http_response_code(400); echo json_encode(['message' => 'Chart ID is required']); exit;
        }
        try {
            $chart = new Chart($idChart);
            $chartDir = CHART_UPLOAD_DIR . $idChart . '/';
            foreach (['mp3', 'wav', 'ogg', 'm4a', 'flac', 'aac'] as $ext) {
                $f = $chartDir . 'audio.' . $ext;
                if (file_exists($f)) unlink($f);
            }
            $chart->SetAudioPath(null);
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
