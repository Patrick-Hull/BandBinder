<?php
require_once __DIR__ . '/../../../lib/util_all.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;

// ── PDF generation is a direct GET (no JSON header) ──────────────────────────
if ($action === 'generateSetlistPdf') {
    if (!in_array('setlists.view', $_SESSION['user']['permissions'])) {
        http_response_code(403); exit;
    }
    $idSetlist = trim($_GET['idSetlist'] ?? $_POST['idSetlist'] ?? '');
    if ($idSetlist === '') { http_response_code(400); exit; }

    try {
        $setlist = new Setlist($idSetlist);
        $sets    = $setlist->getSetsWithCharts();
    } catch (Exception $e) {
        http_response_code(500); echo $e->getMessage(); exit;
    }

    // Fetch categories for all charts in setlist
    $chartIds = [];
    foreach ($sets as $set) {
        foreach ($set['charts'] as $c) {
            $chartIds[] = $c['idChart'];
        }
    }
    $categoriesByChart = [];
    if (!empty($chartIds)) {
        $categoriesByChart = Category::GetByCharts($chartIds);
    }

    // ── Build PDF ─────────────────────────────────────────────────────────────
    class SetlistPdf extends \setasign\Fpdi\Fpdi
    {
        private string $title;
        private string $subtitle;

        public function setMeta(string $title, string $subtitle): void
        {
            $this->title    = $title;
            $this->subtitle = $subtitle;
        }

        public function Header(): void
        {
            $this->SetFont('Helvetica', 'B', 14);
            $this->Cell(0, 8, $this->title, 0, 1, 'C');
            if ($this->subtitle) {
                $this->SetFont('Helvetica', '', 10);
                $this->Cell(0, 6, $this->subtitle, 0, 1, 'C');
            }
            $this->Ln(2);
        }

        public function Footer(): void
        {
            $this->SetY(-12);
            $this->SetFont('Helvetica', 'I', 8);
            $this->Cell(0, 5, 'Page ' . $this->PageNo(), 0, 0, 'C');
        }
    }

    function fmtDur(?int $seconds): string
    {
        if (!$seconds) return '';
        return floor($seconds / 60) . ':' . str_pad($seconds % 60, 2, '0', STR_PAD_LEFT);
    }

    function sumDur(array $charts): int
    {
        return array_sum(array_column($charts, 'duration'));
    }

    $pdf = new SetlistPdf('P', 'mm', 'A4');
    $pdf->SetMargins(15, 20, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->setMeta(
        $setlist->getSetlistName(),
        $setlist->getPerformedAt() ? date('j F Y', strtotime($setlist->getPerformedAt())) : ''
    );
    $pdf->AddPage();

    // ── Notes ─────────────────────────────────────────────────────────────────
    $notes = trim((string)$setlist->getNotes());
    if ($notes !== '') {
        $pdf->SetFont('Helvetica', 'I', 10);
        $pdf->SetFillColor(245, 245, 245);
        $pdf->SetX(15);
        $pdf->MultiCell(180, 6, $notes, 1, 'L', true);
        $pdf->Ln(4);
    }

    // Column widths — centred in 180mm usable width
    $colW  = [8, 55, 40, 15, 14, 14, 18]; // #, Chart, Artist/Arr, Key, BPM, Dur, Category
    $heads = ['#', 'Chart', 'Artist / Arranger', 'Key', 'BPM', 'Dur', 'Category'];
    $totalTableW = array_sum($colW);    // 184 mm
    $pageW = 210 - 30;                  // 180 mm usable
    $offsetX = 15 + ($pageW - $totalTableW) / 2;

    $grandTotal = 0;

    foreach ($sets as $set) {
        $charts   = $set['charts'];
        $setTotal = sumDur($charts);
        $grandTotal += $setTotal;

        // Set name header
        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetX($offsetX);
        $pdf->Cell($totalTableW, 7, $set['setName'], 1, 1, 'L', true);

        // Column headers
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetFillColor(245, 245, 245);
        $pdf->SetX($offsetX);
        foreach ($heads as $k => $h) {
            $pdf->Cell($colW[$k], 6, $h, 1, 0, 'C', true);
        }
        $pdf->Ln();

        // Chart rows
        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetFillColor(255, 255, 255);
        foreach ($charts as $idx => $c) {
            $artistOrArranger = $c['displayName'];
            $bpm      = $c['bpm']      ? (string)$c['bpm']        : '';
            $key      = $c['chartKey'] ?? '';
            $dur      = fmtDur($c['duration']);

            // Get categories for this chart
            $cats = $categoriesByChart[$c['idChart']] ?? [];
            $catStr = '';
            foreach ($cats as $cat) {
                $catName = $cat['categoryName'];
                $catColour = $cat['categoryColour'];
                if ($catColour) {
                    // Parse hex colour for FPDF
                    $r = hexdec(substr($catColour, 1, 2));
                    $g = hexdec(substr($catColour, 3, 2));
                    $b = hexdec(substr($catColour, 5, 2));
                    $pdf->SetFillColor($r, $g, $b);
                    $catStr .= $catName . ' ';
                } else {
                    $catStr .= $catName . ' ';
                }
            }
            $catStr = trim($catStr);

            $fill     = ($idx % 2 === 0);
            $pdf->SetFillColor($fill ? 255 : 248, $fill ? 255 : 248, $fill ? 255 : 248);
            $pdf->SetX($offsetX);
            $pdf->Cell($colW[0], 6, (string)($idx + 1), 1, 0, 'C', true);
            $pdf->Cell($colW[1], 6, $c['chartName'],    1, 0, 'L', true);
            $pdf->Cell($colW[2], 6, $artistOrArranger,  1, 0, 'L', true);
            $pdf->Cell($colW[3], 6, $key,               1, 0, 'C', true);
            $pdf->Cell($colW[4], 6, $bpm,               1, 0, 'C', true);
            $pdf->Cell($colW[5], 6, $dur,               1, 0, 'C', true);
            if ($catStr) {
                $pdf->SetFont('Helvetica', 'B', 7);
                $pdf->Cell($colW[6], 6, $catStr, 1, 0, 'C', true);
                $pdf->SetFont('Helvetica', '', 9);
            } else {
                $pdf->Cell($colW[6], 6, '', 1, 0, 'C', true);
            }
            $pdf->Ln();
            // Reset fill color back after coloured category cells
            $pdf->SetFillColor(255, 255, 255);
        }

        // Set total row
        if ($setTotal > 0) {
            $pdf->SetFont('Helvetica', 'B', 9);
            $pdf->SetFillColor(220, 220, 220);
            $pdf->SetX($offsetX);
            $labelW = $colW[0] + $colW[1] + $colW[2] + $colW[3] + $colW[4] + $colW[5];
            $pdf->Cell($labelW, 6, 'Set Total', 1, 0, 'R', true);
            $pdf->Cell($colW[6], 6, fmtDur($setTotal), 1, 0, 'C', true);
            $pdf->Ln();
        }

        $pdf->Ln(4);
    }

    // Grand total
    if ($grandTotal > 0) {
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetFillColor(200, 200, 200);
        $labelW = $colW[0] + $colW[1] + $colW[2] + $colW[3] + $colW[4] + $colW[5];
        $pdf->SetX($offsetX);
        $pdf->Cell($labelW, 7, 'Total Duration', 1, 0, 'R', true);
        $pdf->Cell($colW[6], 7, fmtDur($grandTotal), 1, 0, 'C', true);
        $pdf->Ln();
    }

    $safeName = preg_replace('/[^a-z0-9_\-]/i', '_', $setlist->getSetlistName());
    $pdf->Output('D', $safeName . '.pdf');
    exit;
}

// ── All other actions return JSON ─────────────────────────────────────────────
header('Content-Type: application/json');
http_response_code(418);

switch ($action) {

    // ── List setlists ─────────────────────────────────────────────────────────
    case 'getSetlists':
        if (!in_array('setlists.view', $_SESSION['user']['permissions'])) {
            http_response_code(403); echo json_encode(['error' => 'Permission denied']); exit;
        }
        try {
            $db   = new DatabaseManager();
            $rows = $db->query(
                "SELECT s.*,
                    COUNT(DISTINCT ss.idSet)        AS setCount,
                    COUNT(DISTINCT ssc.idSetChart)  AS chartCount,
                    SUM(c.duration)                 AS totalSeconds
                 FROM `setlists` s
                 LEFT JOIN `setlist__sets`       ss  ON ss.idSetlist  = s.idSetlist
                 LEFT JOIN `setlist__set_charts` ssc ON ssc.idSet     = ss.idSet
                 LEFT JOIN `charts`              c   ON c.idChart     = ssc.idChart
                 GROUP BY s.idSetlist
                 ORDER BY s.performedAt DESC, s.performedAt IS NULL, s.setlistName"
            );
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit;
        }
        $data = [];
        foreach ($rows as $row) {
            $secs = (int)$row['totalSeconds'];
            $data[] = [
                'idSetlist'     => $row['idSetlist'],
                'setlistName'   => $row['setlistName'],
                'performedAt'   => $row['performedAt'] ?? '',
                'notes'         => $row['notes'] ?? '',
                'setCount'      => (int)$row['setCount'],
                'chartCount'    => (int)$row['chartCount'],
                'totalDuration' => $secs > 0
                    ? floor($secs / 60) . ':' . str_pad($secs % 60, 2, '0', STR_PAD_LEFT)
                    : '—',
            ];
        }
        http_response_code(200);
        echo json_encode(['data' => $data]);
        break;

    // ── Create setlist ────────────────────────────────────────────────────────
    case 'createSetlist':
        if (!in_array('setlists.create', $_SESSION['user']['permissions'])) {
            http_response_code(403); echo json_encode(['message' => 'Permission denied']); exit;
        }
        $name = trim($_POST['setlistName'] ?? '');
        if ($name === '') {
            http_response_code(400); echo json_encode(['message' => 'Name is required']); exit;
        }
        $performedAt = trim($_POST['performedAt'] ?? '') ?: null;
        $notes       = trim($_POST['notes']       ?? '') ?: null;
        try {
            $setlist = Setlist::CreateSetlist($name, $performedAt, $notes);
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['message' => $e->getMessage()]); exit;
        }
        http_response_code(200);
        echo json_encode(['id' => $setlist->getIdSetlist()]);
        break;

    // ── Update setlist metadata ───────────────────────────────────────────────
    case 'updateSetlist':
        if (!in_array('setlists.edit', $_SESSION['user']['permissions'])) {
            http_response_code(403); echo json_encode(['message' => 'Permission denied']); exit;
        }
        $idSetlist = trim($_POST['idSetlist'] ?? '');
        $name      = trim($_POST['setlistName'] ?? '');
        if ($idSetlist === '' || $name === '') {
            http_response_code(400); echo json_encode(['message' => 'ID and name are required']); exit;
        }
        try {
            $setlist = new Setlist($idSetlist);
            $setlist->UpdateSetlist(
                $name,
                trim($_POST['performedAt'] ?? '') ?: null,
                trim($_POST['notes']       ?? '') ?: null
            );
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['message' => $e->getMessage()]); exit;
        }
        http_response_code(200);
        echo json_encode(['success' => true]);
        break;

    // ── Delete setlist ────────────────────────────────────────────────────────
    case 'deleteSetlist':
        if (!in_array('setlists.delete', $_SESSION['user']['permissions'])) {
            http_response_code(403); echo json_encode(['message' => 'Permission denied']); exit;
        }
        $idSetlist = trim($_POST['idSetlist'] ?? '');
        if ($idSetlist === '') {
            http_response_code(400); echo json_encode(['message' => 'ID is required']); exit;
        }
        try {
            (new Setlist($idSetlist))->DeleteSetlist();
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['message' => $e->getMessage()]); exit;
        }
        http_response_code(200);
        echo json_encode(['success' => true]);
        break;

    // ── Get setlist detail (for editor) ──────────────────────────────────────
    case 'getSetlistDetail':
        if (!in_array('setlists.view', $_SESSION['user']['permissions'])) {
            http_response_code(403); echo json_encode(['error' => 'Permission denied']); exit;
        }
        $idSetlist = trim($_POST['idSetlist'] ?? $_GET['idSetlist'] ?? '');
        if ($idSetlist === '') {
            http_response_code(400); echo json_encode(['error' => 'ID is required']); exit;
        }
        try {
            $setlist = new Setlist($idSetlist);
            $sets    = $setlist->getSetsWithCharts();
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit;
        }
        http_response_code(200);
        echo json_encode([
            'idSetlist'   => $setlist->getIdSetlist(),
            'setlistName' => $setlist->getSetlistName(),
            'performedAt' => $setlist->getPerformedAt(),
            'notes'       => $setlist->getNotes(),
            'sets'        => $sets,
        ]);
        break;

    // ── Get charts for left panel (with play stats) ───────────────────────────
    case 'getChartsForPanel':
        if (!in_array('setlists.view', $_SESSION['user']['permissions'])) {
            http_response_code(403); echo json_encode(['error' => 'Permission denied']); exit;
        }
        try {
            $db   = new DatabaseManager();
            $rows = $db->query(
                "SELECT c.idChart, c.chartName, c.bpm, c.duration, c.chartKey,
                        a.artistName, ar.arrangerName,
                        COALESCE(ar.arrangerName, a.artistName, '') AS displayName,
                        COUNT(DISTINCT ss.idSetlist) AS timesPlayed,
                        MAX(sl.performedAt)          AS lastPlayed
                 FROM `charts` c
                 LEFT JOIN `artists`   a  ON a.idArtist   = c.idArtist
                 LEFT JOIN `arrangers` ar ON ar.idArranger = c.idArranger
                 LEFT JOIN `setlist__set_charts` ssc ON ssc.idChart  = c.idChart
                 LEFT JOIN `setlist__sets`       ss  ON ss.idSet     = ssc.idSet
                 LEFT JOIN `setlists`            sl  ON sl.idSetlist = ss.idSetlist
                 GROUP BY c.idChart, c.chartName, c.bpm, c.duration, c.chartKey,
                          a.artistName, ar.arrangerName
                 ORDER BY c.chartName"
            );
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['error' => $e->getMessage()]); exit;
        }
        http_response_code(200);
        echo json_encode(['data' => $rows]);
        break;

    // ── Save full layout ──────────────────────────────────────────────────────
    case 'saveSetlistLayout':
        if (!in_array('setlists.edit', $_SESSION['user']['permissions'])) {
            http_response_code(403); echo json_encode(['message' => 'Permission denied']); exit;
        }
        $idSetlist = trim($_POST['idSetlist'] ?? '');
        $sets      = json_decode($_POST['sets'] ?? '[]', true);
        if ($idSetlist === '' || !is_array($sets)) {
            http_response_code(400); echo json_encode(['message' => 'ID and sets are required']); exit;
        }
        try {
            $setlist = new Setlist($idSetlist);
            $setlist->saveLayout($sets);
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
