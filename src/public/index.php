<?php
require_once __DIR__ . '/../lib/util_all.php';
$pageName = "Dashboard";

$idUser = $_SESSION['user']['me'];
$db     = new DatabaseManager();

// ── Current user display name ─────────────────────────────────────────────────
$me          = new User($idUser);
$displayName = $me->getNameShort() ?: $me->getUsername();

// ── Quick stats ───────────────────────────────────────────────────────────────
$totalCharts    = (int)($db->query("SELECT COUNT(*) AS n FROM `charts`")[0]['n']    ?? 0);
$totalSetlists  = (int)($db->query("SELECT COUNT(*) AS n FROM `setlists`")[0]['n'] ?? 0);
$totalArtists   = (int)($db->query("SELECT COUNT(*) AS n FROM `artists`")[0]['n']  ?? 0);
$totalArrangers = (int)($db->query("SELECT COUNT(*) AS n FROM `arrangers`")[0]['n'] ?? 0);

// ── User's instrument IDs (for PDF resolution) ────────────────────────────────
$userInstruments = $db->query(
    "SELECT idInstrument FROM `link__user_instrument` WHERE idUser = ?", [$idUser]
);
$instrumentIds = array_column($userInstruments, 'idInstrument');

// Helper: resolve best PDF for a chart (instrument-specific → master)
function resolvePdf(string $idChart, array $instrumentIds, DatabaseManager $db): ?string {
    if (!empty($instrumentIds)) {
        $ph   = implode(',', array_fill(0, count($instrumentIds), '?'));
        $part = $db->query(
            "SELECT pdfPath FROM `chart__pdf_parts`
             WHERE idChart = ? AND idInstrument IN ($ph) LIMIT 1",
            array_merge([$idChart], $instrumentIds)
        );
        if (!empty($part[0]['pdfPath'])) return $part[0]['pdfPath'];
    }
    $master = $db->query("SELECT pdfPath FROM `charts` WHERE idChart = ? LIMIT 1", [$idChart]);
    return $master[0]['pdfPath'] ?? null;
}

// ── Recently added charts (last 8) ───────────────────────────────────────────
$recentCharts = $db->query(
    "SELECT c.idChart, c.chartName, c.bpm, c.duration, c.chartKey, c.created_at,
            COALESCE(ar.arrangerName, a.artistName, '') AS displayName
     FROM `charts` c
     LEFT JOIN `artists`   a  ON a.idArtist   = c.idArtist
     LEFT JOIN `arrangers` ar ON ar.idArranger = c.idArranger
     ORDER BY c.created_at DESC LIMIT 8"
);

// ── Upcoming / recent setlists ────────────────────────────────────────────────
$upcomingSetlists = $db->query(
    "SELECT s.idSetlist, s.setlistName, s.performedAt,
            COUNT(DISTINCT ssc.idSetChart) AS chartCount
     FROM `setlists` s
     LEFT JOIN `setlist__sets`       ss  ON ss.idSetlist = s.idSetlist
     LEFT JOIN `setlist__set_charts` ssc ON ssc.idSet    = ss.idSet
     GROUP BY s.idSetlist
     ORDER BY
         CASE WHEN s.performedAt IS NULL OR s.performedAt >= CURDATE() THEN 0 ELSE 1 END,
         ABS(DATEDIFF(COALESCE(s.performedAt, CURDATE()), CURDATE()))
     LIMIT 6"
);

// ── Top artists by chart count ────────────────────────────────────────────────
$topArtists = $db->query(
    "SELECT a.artistName, COUNT(c.idChart) AS chartCount
     FROM `artists` a
     JOIN `charts` c ON c.idArtist = a.idArtist
     GROUP BY a.idArtist ORDER BY chartCount DESC LIMIT 6"
);

// ── Top arrangers by chart count ──────────────────────────────────────────────
$topArrangers = $db->query(
    "SELECT ar.arrangerName, COUNT(c.idChart) AS chartCount
     FROM `arrangers` ar
     JOIN `charts` c ON c.idArranger = ar.idArranger
     GROUP BY ar.idArranger ORDER BY chartCount DESC LIMIT 6"
);

function fmtDur(?int $secs): string {
    if (!$secs) return '';
    return floor($secs / 60) . ':' . str_pad($secs % 60, 2, '0', STR_PAD_LEFT);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo $pageName; ?> — BandBinder</title>
    <?php require_once __DIR__ . '/../lib/html_header/all.php'; ?>
</head>
<body>
<?php require_once __DIR__ . '/../lib/navbar.php'; ?>

<div class="container-fluid px-4 py-4">

    <!-- ── Welcome ─────────────────────────────────────────────────────── -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
        <div>
            <h1 class="mb-0">Welcome back, <?php echo htmlspecialchars($displayName); ?> <span style="font-size:1.5rem">🎵</span></h1>
            <div class="text-muted small"><?php echo date('l, j F Y'); ?></div>
        </div>
    </div>

    <!-- ── Stat tiles ──────────────────────────────────────────────────── -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="stat-tile bg-primary bg-opacity-10 d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value text-primary"><?php echo $totalCharts; ?></div>
                    <div class="stat-label text-primary">Charts</div>
                </div>
                <i class="bi bi-music-note-list stat-icon text-primary"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-tile bg-success bg-opacity-10 d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value text-success"><?php echo $totalSetlists; ?></div>
                    <div class="stat-label text-success">Setlists</div>
                </div>
                <i class="bi bi-list-ol stat-icon text-success"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-tile bg-warning bg-opacity-10 d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value text-warning"><?php echo $totalArtists; ?></div>
                    <div class="stat-label text-warning">Artists</div>
                </div>
                <i class="bi bi-person-badge stat-icon text-warning"></i>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-tile bg-info bg-opacity-10 d-flex align-items-center justify-content-between">
                <div>
                    <div class="stat-value text-info"><?php echo $totalArrangers; ?></div>
                    <div class="stat-label text-info">Arrangers</div>
                </div>
                <i class="bi bi-pencil-square stat-icon text-info"></i>
            </div>
        </div>
    </div>

    <div class="row g-4">

        <!-- ── Recently Added Charts ───────────────────────────────────── -->
        <div class="col-lg-7">
            <h5 class="section-heading"><i class="bi bi-clock-history me-1"></i> Recently Added Charts</h5>
            <?php if (empty($recentCharts)): ?>
                <p class="text-muted small">No charts yet.</p>
            <?php else: ?>
            <div class="card">
                <div class="table-responsive">
                    <table class="table table-hover table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Chart</th>
                                <th>Artist / Arranger</th>
                                <th class="text-center">BPM</th>
                                <th class="text-center">Dur</th>
                                <th class="text-center">PDF</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($recentCharts as $chart):
                            $pdf = resolvePdf($chart['idChart'], $instrumentIds, $db);
                            $dur = fmtDur((int)($chart['duration'] ?? 0));
                        ?>
                            <tr>
                                <td class="fw-semibold"><?php echo htmlspecialchars($chart['chartName']); ?></td>
                                <td class="text-muted"><?php echo htmlspecialchars($chart['displayName']); ?></td>
                                <td class="text-center"><?php echo htmlspecialchars((string)($chart['bpm'] ?? '')); ?></td>
                                <td class="text-center text-muted"><?php echo $dur; ?></td>
                                <td class="text-center">
                                    <?php if ($pdf): ?>
                                        <a href="<?php echo htmlspecialchars($pdf); ?>" target="_blank"
                                           class="btn btn-sm btn-outline-danger py-0 px-1" title="Open PDF">
                                            <i class="bi bi-file-earmark-pdf"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php if (in_array('charts.viewAll', $_SESSION['user']['permissions'])): ?>
                <a href="/charts/all/" class="small text-muted mt-1 d-inline-block">View all charts →</a>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- ── Setlists ─────────────────────────────────────────────────── -->
        <div class="col-lg-5">
            <h5 class="section-heading"><i class="bi bi-calendar-event me-1"></i> Setlists</h5>
            <?php if (empty($upcomingSetlists)): ?>
                <p class="text-muted small">No setlists yet.</p>
            <?php else: ?>
            <div class="d-flex flex-column gap-2">
                <?php foreach ($upcomingSetlists as $sl):
                    $date       = $sl['performedAt'];
                    $isUpcoming = $date && $date >= date('Y-m-d');
                    $isPast     = $date && $date < date('Y-m-d');
                    $dateLabel  = $date ? date('j M Y', strtotime($date)) : 'Date TBD';
                    $badgeCls   = $isUpcoming ? 'bg-success' : ($isPast ? 'bg-secondary' : 'bg-warning text-dark');
                ?>
                <div class="card px-3 py-2">
                    <div class="d-flex align-items-center gap-2">
                        <div class="flex-grow-1" style="min-width:0">
                            <div class="fw-semibold text-truncate"><?php echo htmlspecialchars($sl['setlistName']); ?></div>
                            <div class="d-flex align-items-center gap-2 mt-1">
                                <span class="badge <?php echo $badgeCls; ?>" style="font-size:.7rem"><?php echo $dateLabel; ?></span>
                                <span class="text-muted" style="font-size:.75rem"><?php echo (int)$sl['chartCount']; ?> charts</span>
                            </div>
                        </div>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <?php if (in_array('setlists.view', $_SESSION['user']['permissions'])): ?>
                            <a href="/setlists/lib/action.php?action=generateSetlistPdf&idSetlist=<?php echo urlencode($sl['idSetlist']); ?>"
                               target="_blank" class="btn btn-sm btn-outline-success py-0 px-2" title="Download PDF">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                            <?php endif; ?>
                            <?php if (in_array('setlists.edit', $_SESSION['user']['permissions'])): ?>
                            <a href="/setlists/edit/?id=<?php echo urlencode($sl['idSetlist']); ?>"
                               class="btn btn-sm btn-outline-primary py-0 px-2" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (in_array('setlists.view', $_SESSION['user']['permissions'])): ?>
                <a href="/setlists/" class="small text-muted mt-2 d-inline-block">View all setlists →</a>
            <?php endif; ?>
            <?php endif; ?>
        </div>

        <!-- ── Top Artists ───────────────────────────────────────────────── -->
        <?php if (!empty($topArtists)): ?>
        <div class="col-md-6">
            <h5 class="section-heading"><i class="bi bi-person-badge me-1"></i> Top Artists</h5>
            <div class="card">
                <ul class="list-group list-group-flush">
                    <?php foreach ($topArtists as $a): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <span><?php echo htmlspecialchars($a['artistName']); ?></span>
                        <span class="badge bg-primary rounded-pill"><?php echo (int)$a['chartCount']; ?> charts</span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- ── Top Arrangers ─────────────────────────────────────────────── -->
        <?php if (!empty($topArrangers)): ?>
        <div class="col-md-6">
            <h5 class="section-heading"><i class="bi bi-pencil-square me-1"></i> Top Arrangers</h5>
            <div class="card">
                <ul class="list-group list-group-flush">
                    <?php foreach ($topArrangers as $a): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <span><?php echo htmlspecialchars($a['arrangerName']); ?></span>
                        <span class="badge bg-info text-dark rounded-pill"><?php echo (int)$a['chartCount']; ?> charts</span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php require_once __DIR__ . '/../lib/html_footer/all.php'; ?>
</body>
</html>
