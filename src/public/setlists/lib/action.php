<?php
require_once __DIR__ . '/../../../lib/util_all.php';

if (!isset($_SESSION['user'])) {
    http_response_code(401);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;

// ── Helper functions for PDF generation (used by both webpage and email) ─────
function fmtDur(?int $seconds): string {
    if (!$seconds) return '';
    return floor($seconds / 60) . ':' . str_pad($seconds % 60, 2, '0', STR_PAD_LEFT);
}

function sumDur(array $charts): int {
    return array_sum(array_column($charts, 'duration'));
}

function generateSetlistPdf(Setlist $setlist, ?string $outputPath = null): string {
    $sets = $setlist->getSetsWithCharts();

    $chartIds = [];
    foreach ($sets as $set) {
        foreach ($set['charts'] as $c) {
            $chartIds[] = $c['idChart'];
        }
    }
    $categoriesByChart = !empty($chartIds) ? Category::GetByCharts($chartIds) : [];

    class SetlistPdf extends \setasign\Fpdi\Fpdi {
        private string $title;
        private string $subtitle;

        public function setMeta(string $title, string $subtitle): void {
            $this->title = $title;
            $this->subtitle = $subtitle;
        }

        public function Header(): void {
            $this->SetFont('Helvetica', 'B', 14);
            $this->Cell(0, 8, $this->title, 0, 1, 'C');
            if ($this->subtitle) {
                $this->SetFont('Helvetica', '', 10);
                $this->Cell(0, 6, $this->subtitle, 0, 1, 'C');
            }
            $this->Ln(2);
        }

        public function Footer(): void {
            $this->SetY(-12);
            $this->SetFont('Helvetica', 'I', 8);
            $this->Cell(0, 5, 'Page ' . $this->PageNo(), 0, 0, 'C');
        }
    }

    $pdf = new SetlistPdf('P', 'mm', 'A4');
    $pdf->SetMargins(15, 20, 15);
    $pdf->SetAutoPageBreak(true, 15);
    $pdf->setMeta(
        $setlist->getSetlistName(),
        $setlist->getPerformedAt() ? date('j F Y', strtotime($setlist->getPerformedAt())) : ''
    );
    $pdf->AddPage();

    $notes = trim((string)$setlist->getNotes());
    if ($notes !== '') {
        $pdf->SetFont('Helvetica', 'I', 10);
        $pdf->SetFillColor(245, 245, 245);
        $pdf->SetX(15);
        $pdf->MultiCell(180, 6, $notes, 1, 'L', true);
        $pdf->Ln(4);
    }

    $colW = [8, 55, 40, 15, 14, 14, 18];
    $heads = ['#', 'Chart', 'Artist / Arranger', 'Key', 'BPM', 'Dur', 'Category'];
    $totalTableW = array_sum($colW);
    $pageW = 210 - 30;
    $offsetX = 15 + ($pageW - $totalTableW) / 2;

    $grandTotal = 0;
    foreach ($sets as $set) {
        $charts = $set['charts'];
        $setTotal = sumDur($charts);
        $grandTotal += $setTotal;

        $pdf->SetFont('Helvetica', 'B', 11);
        $pdf->SetFillColor(230, 230, 230);
        $pdf->SetX($offsetX);
        $pdf->Cell($totalTableW, 7, $set['setName'], 1, 1, 'L', true);

        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->SetFillColor(245, 245, 245);
        $pdf->SetX($offsetX);
        foreach ($heads as $k => $h) {
            $pdf->Cell($colW[$k], 6, $h, 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('Helvetica', '', 9);
        $pdf->SetFillColor(255, 255, 255);
        foreach ($charts as $idx => $c) {
            $artistOrArranger = $c['displayName'];
            $bpm = $c['bpm'] ? (string)$c['bpm'] : '';
            $key = $c['chartKey'] ?? '';
            $dur = fmtDur($c['duration']);

            $cats = $categoriesByChart[$c['idChart']] ?? [];
            $catStr = '';
            foreach ($cats as $cat) {
                $catName = $cat['categoryName'];
                $catColour = $cat['categoryColour'];
                if ($catColour) {
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

            $fill = ($idx % 2 === 0);
            $pdf->SetFillColor($fill ? 255 : 248, $fill ? 255 : 248, $fill ? 255 : 248);
            $pdf->SetX($offsetX);
            $pdf->Cell($colW[0], 6, (string)($idx + 1), 1, 0, 'C', true);
            $pdf->Cell($colW[1], 6, $c['chartName'], 1, 0, 'L', true);
            $pdf->Cell($colW[2], 6, $artistOrArranger, 1, 0, 'L', true);
            $pdf->Cell($colW[3], 6, $key, 1, 0, 'C', true);
            $pdf->Cell($colW[4], 6, $bpm, 1, 0, 'C', true);
            $pdf->Cell($colW[5], 6, $dur, 1, 0, 'C', true);
            if ($catStr) {
                $pdf->SetFont('Helvetica', 'B', 6);
                $pdf->MultiCell($colW[6], 5, $catStr, 1, 'C', true);
                $pdf->SetFont('Helvetica', '', 9);
            } else {
                $pdf->Cell($colW[6], 6, '', 1, 0, 'C', true);
                $pdf->Ln();
            }
            $pdf->SetFillColor(255, 255, 255);
        }

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

    if ($grandTotal > 0) {
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->SetFillColor(200, 200, 200);
        $labelW = $colW[0] + $colW[1] + $colW[2] + $colW[3] + $colW[4] + $colW[5];
        $pdf->SetX($offsetX);
        $pdf->Cell($labelW, 7, 'Total Duration', 1, 0, 'R', true);
        $pdf->Cell($colW[6], 7, fmtDur($grandTotal), 1, 0, 'C', true);
        $pdf->Ln();
    }

    if (!$outputPath) {
        $outputPath = sys_get_temp_dir() . '/setlist_' . $setlist->getIdSetlist() . '_' . time() . '.pdf';
    }
    $pdf->Output('F', $outputPath);
    return $outputPath;
}

// ── PDF generation is a direct GET (no JSON header) ──────────────────────────
if ($action === 'generateSetlistPdf') {
    if (!in_array('setlists.view', $_SESSION['user']['permissions'])) {
        http_response_code(403); exit;
    }
    $idSetlist = trim($_GET['idSetlist'] ?? $_POST['idSetlist'] ?? '');
    if ($idSetlist === '') { http_response_code(400); exit; }

    try {
        $setlist = new Setlist($idSetlist);
    } catch (Exception $e) {
        http_response_code(500); echo $e->getMessage(); exit;
    }

    $safeName = preg_replace('/[^a-z0-9_\-]/i', '_', $setlist->getSetlistName());

    // If saving to file (for email attachments)
    $saveTo = trim($_GET['saveTo'] ?? '');
    if ($saveTo) {
        $tempPath = generateSetlistPdf($setlist, $saveTo);
        echo json_encode(['success' => true, 'path' => $tempPath]);
        exit;
    }

// Otherwise output for download
    $tempPath = generateSetlistPdf($setlist);
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $safeName . '.pdf"');
    readfile($tempPath);
    @unlink($tempPath);
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

    // ── Send setlist to members ─────────────────────────────────────────
    case 'sendSetlistToMembers':
        if (!in_array('setlists.edit', $_SESSION['user']['permissions'])) {
            http_response_code(403); echo json_encode(['message' => 'Permission denied']); exit;
        }
        $idSetlist = trim($_POST['idSetlist'] ?? '');
        $sendToAll = isset($_POST['sendToAll']) && $_POST['sendToAll'] === 'true';
        $memberIds = json_decode($_POST['memberIds'] ?? '[]', true);
        $emailBody = trim($_POST['emailBody'] ?? '');

        // Filter out empty strings
        $memberIds = array_filter($memberIds, fn($id) => !empty($id));

        if ($idSetlist === '') {
            http_response_code(400); echo json_encode(['message' => 'Setlist ID is required']); exit;
        }
        try {
            $setlist = new Setlist($idSetlist);
            $setlistName = $setlist->getSetlistName();
            $performedAt = $setlist->getPerformedAt();

            $subject = "Setlist: {$setlistName}" . ($performedAt ? " - {$performedAt}" : "");
            $sentCount = 0;

            $db = new DatabaseManager();
            $mailConfig = $db->query("SELECT config_value FROM site_config WHERE config_key = 'mail_settings'");
            $mailConfigSet = !empty($mailConfig) && !empty($mailConfig[0]['config_value']);

            $setlistSummary = "Setlist: {$setlistName}";
            if ($performedAt) $setlistSummary .= "\nDate: {$performedAt}";
            $setlistSummary .= "\n\nSongs:";

            $userInstrumentMap = [];

            $users = [];
            if ($sendToAll) {
                $users = $db->query("SELECT id, username, email FROM users");
            } else {
                $users = $db->query("SELECT id, username, email FROM users WHERE id IN (" . implode(',', array_fill(0, count($memberIds), '?')) . ")", $memberIds);
            }

            foreach ($users as $user) {
                $userName = $user['username'];
                $email = $user['email'];

                if (!isset($userInstrumentMap[$user['id']])) {
                    $userInstruments = $db->query(
                        "SELECT it.instrumentName FROM `link__user_instrument` lui
                         JOIN `instrument__types` it ON it.idInstrument = lui.idInstrument
                         WHERE lui.idUser = ?",
                        [$user['id']]
                    );
                    $userInstrumentMap[$user['id']] = array_column($userInstruments, 'instrumentName');
                }
                $instrumentList = $userInstrumentMap[$user['id']];

                $body = $emailBody ? nl2br(htmlspecialchars($emailBody)) : "Please find the setlist PDF attached.";

                $tempPdf = sys_get_temp_dir() . '/setlist_' . $idSetlist . '_' . $user['id'] . '_' . time() . '.pdf';
                $attachments = [];
                try {
                    $tempPdf = generateSetlistPdf($setlist, $tempPdf);
                    if (file_exists($tempPdf)) {
                        $attachments[] = ['path' => $tempPdf, 'name' => $setlistName . '.pdf'];
                    }
                } catch (Exception $e) {
                    error_log('Setlist PDF generation failed: ' . $e->getMessage());
                    $setlistPdfUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/setlists/lib/action.php?action=generateSetlistPdf&idSetlist=' . $idSetlist;
                    $body .= '<br><br><a href="' . $setlistPdfUrl . '">Download Setlist PDF</a>';
                }

                $result = Mail::send($email, $subject, $body, [], $attachments);
                if ($result) $sentCount++;

                if (!empty($attachments) && file_exists($attachments[0]['path'])) {
                    @unlink($attachments[0]['path']);
                }
            }
        } catch (Exception $e) {
            http_response_code(500); echo json_encode(['message' => $e->getMessage()]); exit;
        }
        http_response_code(200);
        echo json_encode(['sentCount' => $sentCount]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
