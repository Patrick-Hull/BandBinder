<?php
require_once __DIR__ . '/../../../lib/util_all.php';

if (!in_array('setlists.view', $_SESSION['user']['permissions'])) {
    header("Location: /");
    exit;
}
$canEdit = in_array('setlists.edit', $_SESSION['user']['permissions']);

$idSetlist = trim($_GET['id'] ?? '');
if ($idSetlist === '') {
    header("Location: /setlists/");
    exit;
}

try {
    $setlist = new Setlist($idSetlist);
    $sets    = $setlist->getSetsWithCharts();
} catch (Exception $e) {
    header("Location: /setlists/");
    exit;
}

function fmtDur(?int $secs): string {
    if (!$secs) return '';
    return floor($secs / 60) . ':' . str_pad($secs % 60, 2, '0', STR_PAD_LEFT);
}

function setTotalSecs(array $charts): int {
    return (int)array_sum(array_column($charts, 'duration'));
}

function setlistTotalSecs(array $sets): int {
    $t = 0;
    foreach ($sets as $s) $t += setTotalSecs($s['charts']);
    return $t;
}

$grandTotal = setlistTotalSecs($sets);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo htmlspecialchars($setlist->getSetlistName()); ?> — Setlist Editor</title>
    <?php require_once __DIR__ . '/../../../lib/html_header/all.php'; ?>
    <?php require_once __DIR__ . '/../../../lib/html_header/sortablejs.php'; ?>
    <style>
        body { overflow: hidden; }
        #editorWrap { display: flex; height: calc(100vh - 56px); }

        /* ── Left panel ─────────────────────────── */
        #chartPanel {
            width: 300px;
            flex-shrink: 0;
            display: flex;
            flex-direction: column;
            border-right: 1px solid #dee2e6;
            background: #f8f9fa;
        }
        #chartPanelList {
            overflow-y: auto;
            flex: 1;
            padding: 8px;
        }
        .chart-panel-item {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            padding: 8px 10px;
            margin-bottom: 6px;
            cursor: grab;
            user-select: none;
        }
        .chart-panel-item:hover { border-color: #0d6efd; }
        .chart-panel-item.sortable-chosen { opacity: 0.7; }

        /* ── Right panel (sets editor) ──────────── */
        #setsPanel {
            flex: 1;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        #setsPanelHeader {
            padding: 10px 16px;
            border-bottom: 1px solid #dee2e6;
            background: #fff;
            flex-shrink: 0;
        }
        #setsScroll {
            flex: 1;
            overflow-y: auto;
            padding: 16px;
        }
        #setsContainer {
            display: flex;
            flex-direction: column;
            gap: 16px;
        }

        /* ── Individual set cards ───────────────── */
        .set-card {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            overflow: hidden;
        }
        .set-card-header {
            background: #f1f3f5;
            padding: 8px 12px;
            display: flex;
            align-items: center;
            gap: 8px;
            border-bottom: 1px solid #dee2e6;
        }
        .drag-set-handle {
            cursor: grab;
            color: #6c757d;
            font-size: 1.1rem;
        }
        .set-name-input {
            flex: 1;
            max-width: 250px;
        }
        .set-duration-badge { font-size: 0.8rem; }

        /* ── Charts within a set ────────────────── */
        .set-chart-list {
            min-height: 50px;
            padding: 8px;
            list-style: none;
            margin: 0;
        }
        .set-chart-list:empty::after {
            content: 'Drag charts here';
            display: block;
            text-align: center;
            color: #adb5bd;
            padding: 12px;
            font-style: italic;
            font-size: 0.85rem;
        }
        .set-chart-item {
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 8px;
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            margin-bottom: 5px;
            user-select: none;
        }
        .set-chart-item .drag-handle {
            cursor: grab;
            color: #adb5bd;
            font-size: 1rem;
            flex-shrink: 0;
        }
        .set-chart-item .chart-info { flex: 1; min-width: 0; }
        .set-chart-item .chart-name { font-weight: 600; font-size: 0.88rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .set-chart-item .chart-meta { font-size: 0.75rem; color: #6c757d; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .set-chart-item .remove-btn  { flex-shrink: 0; }
        .set-chart-item.sortable-ghost { opacity: 0.4; background: #cfe2ff; }
    </style>
</head>
<body>
    <?php require_once __DIR__ . '/../../../lib/navbar.php'; ?>

    <div id="editorWrap">
        <!-- ── Left Panel: All Charts ─────────────────────────────── -->
        <div id="chartPanel">
            <div class="p-2 border-bottom bg-white">
                <div class="fw-semibold mb-1 small text-muted text-uppercase">Charts Library</div>
                <input type="text" id="chartSearch" class="form-control form-control-sm" placeholder="Search charts…">
            </div>
            <div id="chartPanelList">
                <div class="text-center text-muted p-3 small">Loading…</div>
            </div>
        </div>

        <!-- ── Right Panel: Setlist Editor ───────────────────────── -->
        <div id="setsPanel">
            <div id="setsPanelHeader">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="/setlists/" class="btn btn-sm btn-outline-secondary">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <div>
                        <div class="fw-bold fs-5"><?php echo htmlspecialchars($setlist->getSetlistName()); ?></div>
                        <?php if ($setlist->getPerformedAt()): ?>
                            <div class="text-muted small"><?php echo date('j F Y', strtotime($setlist->getPerformedAt())); ?></div>
                        <?php endif; ?>
                    </div>
                    <span id="grandTotalBadge" class="badge bg-dark ms-1">
                        <?php echo $grandTotal > 0 ? fmtDur($grandTotal) . ' total' : '0:00 total'; ?>
                    </span>
                    <div class="ms-auto d-flex gap-2 flex-wrap">
                        <?php if ($canEdit): ?>
                            <button id="addSetBtn" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-plus-lg"></i> Add Set
                            </button>
                            <button id="saveLayoutBtn" class="btn btn-sm btn-success">
                                <i class="bi bi-floppy"></i> Save
                            </button>
                        <?php endif; ?>
                        <a href="/setlists/lib/action.php?action=generateSetlistPdf&idSetlist=<?php echo urlencode($idSetlist); ?>"
                           target="_blank" class="btn btn-sm btn-outline-success">
                            <i class="bi bi-file-earmark-pdf"></i> PDF
                        </a>
                    </div>
                </div>
            </div>

            <div id="setsScroll">
                <div id="setsContainer">
                    <?php foreach ($sets as $setIdx => $set):
                        $setTotal = setTotalSecs($set['charts']);
                    ?>
                    <div class="set-card" data-id-set="<?php echo htmlspecialchars($set['idSet']); ?>">
                        <div class="set-card-header">
                            <i class="bi bi-grip-vertical drag-set-handle"></i>
                            <input type="text" class="form-control form-control-sm set-name-input"
                                   value="<?php echo htmlspecialchars($set['setName']); ?>"
                                   <?php if (!$canEdit) echo 'readonly'; ?>>
                            <span class="badge bg-secondary set-duration-badge">
                                <?php echo $setTotal > 0 ? fmtDur($setTotal) : '0:00'; ?>
                            </span>
                            <?php if ($canEdit): ?>
                            <button class="btn btn-sm btn-outline-danger delete-set-btn ms-auto py-0 px-2">
                                <i class="bi bi-trash"></i>
                            </button>
                            <?php endif; ?>
                        </div>
                        <ul class="set-chart-list">
                            <?php foreach ($set['charts'] as $chart):
                                $meta  = [];
                                if ($chart['displayName']) $meta[] = htmlspecialchars($chart['displayName']);
                                if ($chart['chartKey'])    $meta[] = htmlspecialchars($chart['chartKey']);
                                if ($chart['bpm'])         $meta[] = $chart['bpm'] . ' BPM';
                                if ($chart['duration'])    $meta[] = fmtDur((int)$chart['duration']);
                            ?>
                            <li class="set-chart-item"
                                data-id="<?php echo htmlspecialchars($chart['idChart']); ?>"
                                data-name="<?php echo htmlspecialchars($chart['chartName']); ?>"
                                data-bpm="<?php echo (int)($chart['bpm'] ?? 0); ?>"
                                data-duration="<?php echo (int)($chart['duration'] ?? 0); ?>"
                                data-key="<?php echo htmlspecialchars($chart['chartKey'] ?? ''); ?>"
                                data-display-name="<?php echo htmlspecialchars($chart['displayName'] ?? ''); ?>">
                                <i class="bi bi-grip-vertical drag-handle"></i>
                                <div class="chart-info">
                                    <div class="chart-name"><?php echo htmlspecialchars($chart['chartName']); ?></div>
                                    <div class="chart-meta"><?php echo implode(' · ', $meta); ?></div>
                                </div>
                                <?php if ($canEdit): ?>
                                <button class="btn btn-sm btn-outline-danger remove-btn py-0 px-1">
                                    <i class="bi bi-x"></i>
                                </button>
                                <?php endif; ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <?php require_once __DIR__ . '/../../../lib/html_footer/all.php'; ?>

<script>
const SETLIST_ID = <?php echo json_encode($idSetlist); ?>;
const CAN_EDIT   = <?php echo $canEdit ? 'true' : 'false'; ?>;

function escHtml(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function formatDur(secs) {
    secs = parseInt(secs) || 0;
    if (!secs) return '';
    return Math.floor(secs / 60) + ':' + String(secs % 60).padStart(2, '0');
}
function sumDur(listEl) {
    let t = 0;
    listEl.querySelectorAll('.set-chart-item').forEach(el => { t += parseInt(el.dataset.duration) || 0; });
    return t;
}

// ── Totals ────────────────────────────────────────────────────────────────────
function updateTotals() {
    let grand = 0;
    document.querySelectorAll('.set-card').forEach(card => {
        const list  = card.querySelector('.set-chart-list');
        const secs  = sumDur(list);
        grand += secs;
        card.querySelector('.set-duration-badge').textContent = formatDur(secs) || '0:00';
    });
    document.getElementById('grandTotalBadge').textContent =
        (grand > 0 ? formatDur(grand) : '0:00') + ' total';
}

// ── Build meta string for a chart item ───────────────────────────────────────
function buildMeta(el) {
    const parts = [];
    if (el.dataset.displayName) parts.push(escHtml(el.dataset.displayName));
    if (el.dataset.key)         parts.push(escHtml(el.dataset.key));
    if (el.dataset.bpm > 0)     parts.push(el.dataset.bpm + ' BPM');
    const d = formatDur(el.dataset.duration);
    if (d) parts.push(d);
    return parts.join(' · ');
}

// ── Transform a panel-cloned element into a set-chart-item ───────────────────
function transformToSetItem(el) {
    if (el.classList.contains('set-chart-item')) return; // already transformed

    const name        = el.dataset.name        || '';
    const displayName = el.dataset.displayName || '';
    const key         = el.dataset.key         || '';
    const bpm         = el.dataset.bpm         || 0;
    const dur         = el.dataset.duration    || 0;

    const metaParts = [];
    if (displayName) metaParts.push(escHtml(displayName));
    if (key)         metaParts.push(escHtml(key));
    if (bpm > 0)     metaParts.push(bpm + ' BPM');
    const fDur = formatDur(dur);
    if (fDur)        metaParts.push(fDur);

    el.className = 'set-chart-item';
    el.innerHTML = `
        <i class="bi bi-grip-vertical drag-handle"></i>
        <div class="chart-info">
            <div class="chart-name">${escHtml(name)}</div>
            <div class="chart-meta">${metaParts.join(' · ')}</div>
        </div>
        <button class="btn btn-sm btn-outline-danger remove-btn py-0 px-1">
            <i class="bi bi-x"></i>
        </button>
    `;
}

// ── Initialise SortableJS on a set's chart list ───────────────────────────────
function initSetSortable(listEl) {
    return new Sortable(listEl, {
        group:     'setlist-charts',
        animation: 150,
        handle:    '.drag-handle',
        ghostClass: 'sortable-ghost',
        onAdd: function(evt) {
            transformToSetItem(evt.item);
            updateTotals();
        },
        onUpdate: updateTotals,
        onRemove:  updateTotals
    });
}

// ── Initialise all existing set lists on page load ────────────────────────────
document.querySelectorAll('.set-chart-list').forEach(initSetSortable);

// ── Sets container sortable (reorder sets) ────────────────────────────────────
new Sortable(document.getElementById('setsContainer'), {
    animation:  150,
    handle:     '.drag-set-handle',
    draggable:  '.set-card',
    ghostClass: 'sortable-ghost',
    onUpdate:   updateTotals
});

// ── Load chart panel via AJAX ─────────────────────────────────────────────────
$.ajax({
    type: 'POST', url: '/setlists/lib/action.php',
    data: {action: 'getChartsForPanel'},
    dataType: 'JSON',
    success: function(r) {
        const list = document.getElementById('chartPanelList');
        list.innerHTML = '';

        if (!r.data || !r.data.length) {
            list.innerHTML = '<div class="text-muted small p-3">No charts found.</div>';
            return;
        }

        r.data.forEach(c => {
            const div = document.createElement('div');
            div.className = 'chart-panel-item';
            div.dataset.id          = c.idChart;
            div.dataset.name        = c.chartName;
            div.dataset.bpm         = c.bpm         || 0;
            div.dataset.duration    = c.duration    || 0;
            div.dataset.key         = c.chartKey    || '';
            div.dataset.displayName = c.displayName || '';

            const played   = parseInt(c.timesPlayed) || 0;
            const lastDate = c.lastPlayed
                ? new Date(c.lastPlayed).toLocaleDateString('en-AU', {day:'numeric',month:'short',year:'numeric'})
                : null;

            const metaParts = [];
            if (c.displayName)  metaParts.push(escHtml(c.displayName));
            if (c.chartKey)     metaParts.push(escHtml(c.chartKey));
            if (c.bpm)          metaParts.push(c.bpm + ' BPM');
            const fDur = formatDur(c.duration);
            if (fDur)           metaParts.push(fDur);

            const stats = [];
            stats.push('Played ' + played + '×');
            if (lastDate) stats.push('Last: ' + lastDate);

            div.innerHTML = `
                <div class="fw-semibold" style="font-size:0.88rem">${escHtml(c.chartName)}</div>
                ${metaParts.length ? `<div class="text-muted" style="font-size:0.75rem">${metaParts.join(' · ')}</div>` : ''}
                <div class="text-muted" style="font-size:0.72rem;margin-top:2px">${stats.join(' · ')}</div>
            `;
            list.appendChild(div);
        });

        // Sortable on panel (clone-only source)
        new Sortable(list, {
            group:     {name: 'setlist-charts', pull: 'clone', put: false},
            sort:      false,
            animation: 150,
            ghostClass: 'sortable-chosen'
        });

        // Client-side search filter
        document.getElementById('chartSearch').addEventListener('input', function() {
            const q = this.value.toLowerCase();
            list.querySelectorAll('.chart-panel-item').forEach(el => {
                const match = el.dataset.name.toLowerCase().includes(q)
                    || el.dataset.displayName.toLowerCase().includes(q);
                el.style.display = match ? '' : 'none';
            });
        });
    },
    error: function() {
        document.getElementById('chartPanelList').innerHTML =
            '<div class="text-danger small p-3">Failed to load charts.</div>';
    }
});

// ── Add Set ───────────────────────────────────────────────────────────────────
<?php if ($canEdit): ?>
document.getElementById('addSetBtn').addEventListener('click', function() {
    const idx      = document.querySelectorAll('.set-card').length + 1;
    const idSet    = 'new';
    const setName  = 'Set ' + idx;

    const card = document.createElement('div');
    card.className = 'set-card';
    card.dataset.idSet = idSet;
    card.innerHTML = `
        <div class="set-card-header">
            <i class="bi bi-grip-vertical drag-set-handle"></i>
            <input type="text" class="form-control form-control-sm set-name-input" value="${escHtml(setName)}">
            <span class="badge bg-secondary set-duration-badge">0:00</span>
            <button class="btn btn-sm btn-outline-danger delete-set-btn ms-auto py-0 px-2">
                <i class="bi bi-trash"></i>
            </button>
        </div>
        <ul class="set-chart-list"></ul>
    `;
    document.getElementById('setsContainer').appendChild(card);
    initSetSortable(card.querySelector('.set-chart-list'));
    card.scrollIntoView({behavior: 'smooth', block: 'nearest'});
});

// ── Delete Set ────────────────────────────────────────────────────────────────
$(document).on('click', '.delete-set-btn', function() {
    if (!confirm('Remove this set? Charts in it will be unassigned.')) return;
    $(this).closest('.set-card').remove();
    updateTotals();
});

// ── Remove chart from set ──────────────────────────────────────────────────────
$(document).on('click', '.remove-btn', function() {
    $(this).closest('.set-chart-item').remove();
    updateTotals();
});

// ── Save Layout ───────────────────────────────────────────────────────────────
document.getElementById('saveLayoutBtn').addEventListener('click', function() {
    const sets = [];
    document.querySelectorAll('#setsContainer .set-card').forEach(card => {
        const charts = [];
        card.querySelectorAll('.set-chart-list .set-chart-item').forEach(item => {
            charts.push(item.dataset.id);
        });
        sets.push({
            idSet:   card.dataset.idSet,
            setName: card.querySelector('.set-name-input').value.trim() || 'Set',
            charts:  charts
        });
    });

    const btn = this;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving…';

    $.ajax({
        type: 'POST', url: '/setlists/lib/action.php',
        data: {
            action:    'saveSetlistLayout',
            idSetlist: SETLIST_ID,
            sets:      JSON.stringify(sets)
        },
        dataType: 'JSON',
        success: function() {
            toastr.success('Setlist saved.');
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-floppy"></i> Save';
            // Re-tag any "new" sets with their real IDs by reloading
            // (simplest approach: reload the page so data-id-set values are real UUIDs)
            location.reload();
        },
        error: function(xhr) {
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-floppy"></i> Save';
            try {
                const r = JSON.parse(xhr.responseText);
                toastr.error(r.message || 'Save failed.');
            } catch(_) { toastr.error('Save failed.'); }
        }
    });
});
<?php endif; ?>
</script>
</html>
