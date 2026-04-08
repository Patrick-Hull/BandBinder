<?php
require_once __DIR__ . '/../../../lib/util_all.php';
$pageName = "All Charts";
if (!in_array('charts.viewAll', $_SESSION['user']['permissions'])) {
    header("Location: /");
    exit;
}
$canCreate = in_array('charts.create', $_SESSION['user']['permissions']);
$canEdit   = in_array('charts.edit',   $_SESSION['user']['permissions']);
$canDelete = in_array('charts.delete', $_SESSION['user']['permissions']);
$canCreateArtist   = in_array('artists.create',   $_SESSION['user']['permissions']);
$canCreateArranger = in_array('arrangers.create', $_SESSION['user']['permissions']);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title><?php echo $pageName; ?></title>
        <?php require_once __DIR__ . '/../../../lib/html_header/all.php'; ?>
        <?php require_once __DIR__ . '/../../../lib/html_header/tomselect.php'; ?>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
        <style>
            /* ── PDF page grid ───────────────────────────────────── */
            .pdf-page-grid {
                display: grid;
                grid-template-columns: repeat(6, 1fr);
                gap: 10px;
                align-items: start;
            }
            .page-thumb {
                position: relative;
                cursor: pointer;
                border: 3px solid #dee2e6;
                border-radius: 6px;
                background: #fff;
                transition: border-color 0.12s, transform 0.1s;
                user-select: none;
            }
            .page-thumb:hover { transform: scale(1.02); border-color: #adb5bd; z-index: 1; }
            .page-thumb canvas { display: block; width: 100%; height: auto; border-radius: 4px; }
            .page-thumb .page-badge {
                position: absolute; top: 0; left: 0; right: 0;
                padding: 2px 4px; font-size: 0.65rem; font-weight: 600;
                text-align: center; white-space: nowrap;
                overflow: hidden; text-overflow: ellipsis;
            }
            .page-thumb .page-placeholder {
                aspect-ratio: 210 / 297;
            }
            .page-thumb .page-label {
                text-align: center; font-size: 0.7rem;
                padding: 2px 0; background: #f8f9fa; color: #6c757d;
            }
            /* ── Instrument sidebar ──────────────────────────────── */
            .instrument-item {
                cursor: pointer;
                border-left: 5px solid transparent;
                transition: background 0.1s;
            }
            .instrument-item:hover { background: rgba(0,0,0,0.04); }
            .instrument-item.active { background: rgba(0,0,0,0.07); }
            /* ── Fullscreen modal scroll fix ─────────────────────── */
            #managePdfModal .modal-body {
                min-height: 0;
                padding: 0;
            }
        </style>
    </head>
    <body>
        <?php require_once __DIR__ . '/../../../lib/navbar.php'; ?>
        <div class="container-fluid mt-4">
            <div class="row">
                <div class="col-12 col-md-11 col-xl-10 mx-auto">
                    <h1>All Charts</h1>

                    <?php if ($canCreate): ?>
                        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createChartModal">
                            <i class="bi bi-plus-lg"></i> Create Chart
                        </button>
                    <?php endif; ?>

                    <table id="chartTable" class="table table-striped table-bordered table-sm"></table>
                </div>
            </div>
        </div>
        <?php require_once __DIR__ . '/../../../lib/html_footer/all.php'; ?>
    </body>

    <!-- ═══════════════════════════════════════════════════════════
         CREATE CHART MODAL
    ════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="createChartModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Create Chart</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label for="createChartName" class="form-label">Chart Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="createChartName" placeholder="e.g. Sing Sing Sing">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="createChartBpm" class="form-label">BPM</label>
                            <input type="number" class="form-control" id="createChartBpm" min="1" max="999" placeholder="e.g. 140">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="createChartDuration" class="form-label">Duration</label>
                            <input type="text" class="form-control" id="createChartDuration" placeholder="m:ss" pattern="\d+:\d{2}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="createChartKey" class="form-label">Key</label>
                            <input type="text" class="form-control" id="createChartKey" placeholder="e.g. Bb Major">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="createArtistSelect" class="form-label">Artist</label>
                            <select id="createArtistSelect" class="form-control"></select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="createArrangerSelect" class="form-label">Arranger</label>
                            <select id="createArrangerSelect" class="form-control"></select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="createChartNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="createChartNotes" rows="3" placeholder="General notes about this chart..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="createChartPdf" class="form-label">PDF Upload</label>
                        <input type="file" class="form-control" id="createChartPdf" accept=".pdf">
                        <div class="form-text">Upload the master PDF (full band score or single PDF with all instruments).</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveChartBtn">Save Chart</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         EDIT CHART MODAL
    ════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="editChartModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Edit Chart</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editChartId">
                    <div class="row">
                        <div class="col-md-5 mb-3">
                            <label for="editChartName" class="form-label">Chart Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editChartName">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="editChartBpm" class="form-label">BPM</label>
                            <input type="number" class="form-control" id="editChartBpm" min="1" max="999">
                        </div>
                        <div class="col-md-2 mb-3">
                            <label for="editChartDuration" class="form-label">Duration</label>
                            <input type="text" class="form-control" id="editChartDuration" placeholder="m:ss" pattern="\d+:\d{2}">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label for="editChartKey" class="form-label">Key</label>
                            <input type="text" class="form-control" id="editChartKey">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editArtistSelect" class="form-label">Artist</label>
                            <select id="editArtistSelect" class="form-control"></select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editArrangerSelect" class="form-label">Arranger</label>
                            <select id="editArrangerSelect" class="form-control"></select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editChartNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="editChartNotes" rows="3"></textarea>
                    </div>

                    <!-- Master PDF section -->
                    <hr>
                    <h6>Master PDF</h6>
                    <div id="editCurrentPdfSection" class="mb-2 d-none">
                        <a id="editCurrentPdfLink" href="#" target="_blank" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="bi bi-file-earmark-pdf"></i> View Current PDF
                        </a>
                        <button type="button" class="btn btn-sm btn-outline-warning" id="managePdfPartsBtn">
                            <i class="bi bi-scissors"></i> Manage Instrument PDFs
                        </button>
                    </div>
                    <div class="mb-3">
                        <label for="editChartPdf" class="form-label" id="editChartPdfLabel">Upload New Master PDF</label>
                        <input type="file" class="form-control" id="editChartPdf" accept=".pdf">
                        <div class="form-text">Uploading a new PDF will replace the existing master PDF.</div>
                    </div>

                    <!-- Audio section -->
                    <hr>
                    <h6>Audio File</h6>
                    <div id="editCurrentAudioSection" class="mb-2 d-none">
                        <span class="text-muted small me-2"><i class="bi bi-music-note-beamed me-1"></i><span id="editCurrentAudioName"></span></span>
                        <button type="button" class="btn btn-sm btn-outline-danger" id="deleteAudioBtn">
                            <i class="bi bi-trash"></i> Remove Audio
                        </button>
                    </div>
                    <div class="mb-3">
                        <label for="editChartAudio" class="form-label">Upload Audio File</label>
                        <input type="file" class="form-control" id="editChartAudio" accept=".mp3,.wav,.ogg,.m4a,.flac,.aac">
                        <div class="form-text">Supported: MP3, WAV, OGG, M4A, FLAC, AAC.</div>
                    </div>
                    <div id="audioUploadProgress" class="d-none mb-2">
                        <div class="progress" style="height:6px">
                            <div class="progress-bar progress-bar-striped progress-bar-animated w-100"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="updateChartBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         DELETE CHART MODAL
    ════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="deleteChartModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Delete Chart</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <strong id="deleteChartName"></strong>?
                    <div class="text-muted small mt-1">All associated PDFs and user notes will also be deleted.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteChartBtn"><i class="bi bi-trash"></i> Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         MANAGE INSTRUMENT PDFs MODAL  (fullscreen, page-click UI)
    ════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="managePdfModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-fullscreen">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h5 class="modal-title">
                        <i class="bi bi-scissors"></i> Assign Pages — <span id="managePdfChartName"></span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <!-- Two-column body -->
                <div class="modal-body d-flex overflow-hidden">

                    <!-- ── Left sidebar: instruments ────────────── -->
                    <div class="d-flex flex-column border-end" style="width:270px;min-width:270px;overflow-y:auto;">
                        <div class="p-3 border-bottom bg-light">
                            <div class="fw-semibold mb-1">Instruments</div>
                            <small class="text-muted">
                                1. Click an instrument<br>
                                2. Click pages on the right to assign them
                            </small>
                        </div>
                        <div id="instrumentSidebar" class="flex-grow-1 p-2" style="overflow-y:auto;"></div>
                        <div class="p-2 border-top">
                            <button class="btn btn-sm btn-outline-secondary w-100" id="clearAllPagesBtn">
                                <i class="bi bi-x-circle"></i> Clear All Selections
                            </button>
                        </div>
                    </div>

                    <!-- ── Right: PDF page thumbnails ───────────── -->
                    <div class="flex-grow-1 d-flex flex-column overflow-hidden">
                        <div class="px-3 py-2 border-bottom bg-light d-flex align-items-center gap-2">
                            <span id="pdfPageCountBadge" class="badge bg-secondary"></span>
                            <span class="text-muted small" id="pdfStatusMsg">Loading PDF…</span>
                        </div>
                        <div id="pdfPageGrid" class="pdf-page-grid flex-grow-1 overflow-y-auto p-3"
                             style="align-content:start; min-height:0;"></div>
                    </div>
                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="doSplitBtn">
                        <i class="bi bi-check-lg"></i> Save Assignments
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════
         AUDIO PLAYER MODAL
    ════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="audioPlayerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <div class="fw-semibold" id="audioPlayerChartName"></div>
                        <div class="text-muted small" id="audioPlayerArtistName"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    <audio id="audioPlayerEl" src="" preload="metadata" style="display:none"></audio>

                    <!-- Waveform-style progress bar -->
                    <div class="audio-player-bar mb-3" id="audioSeekBar">
                        <div class="audio-player-progress" id="audioProgress"></div>
                        <div class="audio-player-handle" id="audioHandle"></div>
                    </div>

                    <!-- Time -->
                    <div class="d-flex justify-content-between text-muted small mb-3 px-1">
                        <span id="audioCurrent">0:00</span>
                        <span id="audioDuration">0:00</span>
                    </div>

                    <!-- Controls -->
                    <div class="d-flex align-items-center justify-content-center gap-3">
                        <button class="btn btn-outline-secondary btn-sm audio-ctrl-btn" id="audioSkipBack" title="Back 10s">
                            <i class="bi bi-skip-backward-fill"></i>
                        </button>
                        <button class="btn btn-primary audio-play-btn" id="audioPlayBtn" title="Play/Pause">
                            <i class="bi bi-play-fill fs-5"></i>
                        </button>
                        <button class="btn btn-outline-secondary btn-sm audio-ctrl-btn" id="audioSkipFwd" title="Forward 10s">
                            <i class="bi bi-skip-forward-fill"></i>
                        </button>
                    </div>

                    <!-- Volume -->
                    <div class="d-flex align-items-center gap-2 mt-3 px-1">
                        <i class="bi bi-volume-down text-muted"></i>
                        <input type="range" class="form-range flex-grow-1" id="audioVolume" min="0" max="1" step="0.05" value="1">
                        <i class="bi bi-volume-up text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .audio-player-bar {
            position: relative;
            height: 6px;
            background: var(--bs-border-color, #dee2e6);
            border-radius: 3px;
            cursor: pointer;
        }
        .audio-player-progress {
            height: 100%;
            background: #0d6efd;
            border-radius: 3px;
            width: 0%;
            transition: width .1s linear;
        }
        .audio-player-handle {
            position: absolute;
            top: 50%;
            left: 0%;
            transform: translate(-50%, -50%);
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,.25);
            transition: left .1s linear;
        }
        .audio-play-btn {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
        .audio-ctrl-btn {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
        }
    </style>

    <script>
        const canCreate        = <?= json_encode($canCreate) ?>;
        const canEdit          = <?= json_encode($canEdit) ?>;
        const canDelete        = <?= json_encode($canDelete) ?>;
        const canCreateArtist  = <?= json_encode($canCreateArtist) ?>;
        const canCreateArranger = <?= json_encode($canCreateArranger) ?>;

        $(function () { fetchData(); });

        let table           = null;
        let currentEditRow  = null;
        let deleteRow       = null;
        let currentManageId = null;
        let createArtistTs  = null;
        let createArrangerTs = null;
        let editArtistTs    = null;
        let editArrangerTs  = null;

        function ajaxErrorHandler(jqXHR) {
            toastr.error(jqXHR.responseJSON?.message || "An unexpected error occurred.");
        }

        // Parse "m:ss" string → total seconds (returns '' if empty/invalid)
        function parseDurationInput(str) {
            if (!str || !str.trim()) return '';
            const m = str.trim().match(/^(\d+):(\d{1,2})$/);
            if (!m) return '';
            return parseInt(m[1]) * 60 + parseInt(m[2]);
        }

        // Format seconds → "m:ss"
        function formatDurationInput(secs) {
            if (!secs) return '';
            return Math.floor(secs / 60) + ':' + String(secs % 60).padStart(2, '0');
        }

        function starsHtml(n) {
            if (!n) return '<span class="text-muted">—</span>';
            return '★'.repeat(n) + '☆'.repeat(5 - n);
        }

        // ── DataTable ────────────────────────────────────────────
        function fetchData() {
            if (table) {
                table.ajax.reload();
            } else {
                const columns = [
                    {data: 'chartName',    title: 'Name'},
                    {data: 'artistName',   title: 'Artist'},
                    {data: 'arrangerName', title: 'Arranger'},
                    {data: 'bpm',          title: 'BPM'},
                    {data: 'duration',     title: 'Duration', render: function(d) { return d ? Math.floor(d/60)+':'+String(d%60).padStart(2,'0') : ''; }},
                    {data: 'chartKey',     title: 'Key'},
                    {data: 'hasPdf',       title: 'PDF', render: function(d) { return d ? '<i class="bi bi-file-earmark-pdf text-danger"></i>' : ''; }},
                    {data: null, title: '', defaultContent: '', render: function(d, t, row) {
                        let html = '';
                        if (row.audioPath) {
                            html += `<button class="btn btn-sm btn-outline-success play-audio-btn" title="Play Audio"><i class="bi bi-music-note-beamed"></i></button>`;
                        }
                        return html;
                    }},
                ];
                const columnDefs = [
                    {targets: 0, orderable: true, searchable: true},
                    {targets: 1, orderable: true, searchable: true},
                    {targets: 2, orderable: true, searchable: true},
                    {targets: 3, orderable: true, searchable: false},
                    {targets: 4, orderable: false, searchable: false},
                    {targets: 5, orderable: true, searchable: true},
                    {targets: 6, orderable: false, searchable: false},
                    {targets: 7, orderable: false, searchable: false},
                ];

                if (canEdit || canDelete) {
                    columns.push({data: null, defaultContent: ''});
                    columnDefs.push({
                        targets: 8,
                        orderable: false,
                        searchable: false,
                        title: '',
                        render: function () {
                            let html = '';
                            if (canEdit)   html += '<button class="btn btn-sm btn-outline-secondary edit-chart-btn me-1"><i class="bi bi-pencil"></i> Edit</button>';
                            if (canDelete) html += '<button class="btn btn-sm btn-outline-danger delete-chart-btn"><i class="bi bi-trash"></i> Delete</button>';
                            return html;
                        }
                    });
                }

                table = $('#chartTable').DataTable({
                    processing: true,
                    serverSide: false,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    pageLength: 25,
                    ajax: {
                        url: 'lib/action.php',
                        type: 'POST',
                        data: function (d) { d.action = "getCharts"; },
                        error: ajaxErrorHandler
                    },
                    columns: columns,
                    columnDefs: columnDefs,
                    createdRow: function (row, data) {
                        $(row).attr('data-id', data.idChart);
                    },
                    responsive: true,
                });
            }
        }

        // ── TomSelect helpers ────────────────────────────────────
        function buildArtistTsConfig(selectId) {
            return {
                valueField: 'value', labelField: 'text', searchField: 'text',
                placeholder: 'Select or type an artist...',
                create: canCreateArtist ? function (input, callback) {
                    $.ajax({
                        type: 'POST', url: '/artists/lib/action.php',
                        data: {action: 'createArtist', artistName: input},
                        dataType: 'JSON',
                        success: function (r) { callback({value: r.id, text: r.name}); },
                        error: function (xhr) { ajaxErrorHandler(xhr); callback(); }
                    });
                } : false,
            };
        }

        function buildArrangerTsConfig(selectId) {
            return {
                valueField: 'value', labelField: 'text', searchField: 'text',
                placeholder: 'Select or type an arranger...',
                create: canCreateArranger ? function (input, callback) {
                    $.ajax({
                        type: 'POST', url: '/arrangers/lib/action.php',
                        data: {action: 'createArranger', arrangerName: input},
                        dataType: 'JSON',
                        success: function (r) { callback({value: r.id, text: r.name}); },
                        error: function (xhr) { ajaxErrorHandler(xhr); callback(); }
                    });
                } : false,
            };
        }

        function loadArtistOptions(ts, selectedValue) {
            $.ajax({
                type: 'POST', url: 'lib/action.php',
                data: {action: 'getArtistsList'},
                dataType: 'JSON',
                success: function (r) {
                    (r.data || []).forEach(o => ts.addOption(o));
                    ts.refreshOptions(false);
                    if (selectedValue) ts.setValue(selectedValue, true);
                }
            });
        }

        function loadArrangerOptions(ts, selectedValue) {
            $.ajax({
                type: 'POST', url: 'lib/action.php',
                data: {action: 'getArrangersList'},
                dataType: 'JSON',
                success: function (r) {
                    (r.data || []).forEach(o => ts.addOption(o));
                    ts.refreshOptions(false);
                    if (selectedValue) ts.setValue(selectedValue, true);
                }
            });
        }

        // ── Create Modal ─────────────────────────────────────────
        $('#createChartModal').on('shown.bs.modal', function () {
            $('#createChartName, #createChartBpm, #createChartDuration, #createChartKey, #createChartNotes').val('');
            $('#createChartPdf').val('');

            if (createArtistTs)  { createArtistTs.destroy();  createArtistTs = null; }
            if (createArrangerTs){ createArrangerTs.destroy(); createArrangerTs = null; }

            createArtistTs   = new TomSelect('#createArtistSelect',   buildArtistTsConfig());
            createArrangerTs = new TomSelect('#createArrangerSelect',  buildArrangerTsConfig());
            loadArtistOptions(createArtistTs, null);
            loadArrangerOptions(createArrangerTs, null);
        });

        $('#createChartModal').on('hidden.bs.modal', function () {
            if (createArtistTs)  { createArtistTs.destroy();  createArtistTs = null; }
            if (createArrangerTs){ createArrangerTs.destroy(); createArrangerTs = null; }
        });

        $('#saveChartBtn').on('click', function () {
            const chartName = $('#createChartName').val().trim();
            if (!chartName) { toastr.error('Chart name is required.'); return; }

            const fd = new FormData();
            fd.append('action',    'createChart');
            fd.append('chartName', chartName);
            fd.append('idArtist',   createArtistTs  ? createArtistTs.getValue()   : '');
            fd.append('idArranger', createArrangerTs ? createArrangerTs.getValue() : '');
            fd.append('bpm',      $('#createChartBpm').val());
            fd.append('duration', parseDurationInput($('#createChartDuration').val()));
            fd.append('chartKey', $('#createChartKey').val().trim());
            fd.append('notes', $('#createChartNotes').val().trim());
            const pdfFile = $('#createChartPdf')[0].files[0];
            if (pdfFile) fd.append('pdfFile', pdfFile);

            $.ajax({
                type: 'POST', url: 'lib/action.php',
                data: fd, processData: false, contentType: false,
                dataType: 'JSON',
                success: function () {
                    bootstrap.Modal.getInstance(document.getElementById('createChartModal')).hide();
                    fetchData();
                    toastr.success('Chart created successfully.');
                },
                error: ajaxErrorHandler
            });
        });

        // ── Edit Modal ───────────────────────────────────────────
        $('#chartTable').on('click', '.edit-chart-btn', function () {
            currentEditRow = table.row($(this).closest('tr')).data();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editChartModal')).show();
        });

        $('#editChartModal').on('shown.bs.modal', function () {
            if (!currentEditRow) return;
            const row = currentEditRow;

            $('#editChartId').val(row.idChart);
            $('#editChartName').val(row.chartName);
            $('#editChartBpm').val(row.bpm || '');
            $('#editChartDuration').val(row.duration ? formatDurationInput(row.duration) : '');
            $('#editChartKey').val(row.chartKey || '');
            $('#editChartNotes').val(row.notes || '');
            $('#editChartPdf').val('');
            $('#editChartAudio').val('');
            $('#audioUploadProgress').addClass('d-none');

            if (row.pdfPath) {
                $('#editCurrentPdfSection').removeClass('d-none');
                $('#editCurrentPdfLink').attr('href', row.pdfPath);
            } else {
                $('#editCurrentPdfSection').addClass('d-none');
            }

            if (row.audioPath) {
                $('#editCurrentAudioSection').removeClass('d-none');
                const fname = row.audioPath.split('/').pop();
                $('#editCurrentAudioName').text(fname);
            } else {
                $('#editCurrentAudioSection').addClass('d-none');
            }

            if (editArtistTs)  { editArtistTs.destroy();  editArtistTs = null; }
            if (editArrangerTs){ editArrangerTs.destroy(); editArrangerTs = null; }

            editArtistTs   = new TomSelect('#editArtistSelect',   buildArtistTsConfig());
            editArrangerTs = new TomSelect('#editArrangerSelect',  buildArrangerTsConfig());
            loadArtistOptions(editArtistTs,   row.idArtist   || null);
            loadArrangerOptions(editArrangerTs, row.idArranger || null);
        });

        $('#editChartModal').on('hidden.bs.modal', function () {
            if (editArtistTs)  { editArtistTs.destroy();  editArtistTs = null; }
            if (editArrangerTs){ editArrangerTs.destroy(); editArrangerTs = null; }
            currentEditRow = null;
        });

        $('#updateChartBtn').on('click', function () {
            const chartName = $('#editChartName').val().trim();
            if (!chartName) { toastr.error('Chart name is required.'); return; }

            const fd = new FormData();
            fd.append('action',    'updateChart');
            fd.append('idChart',   $('#editChartId').val());
            fd.append('chartName', chartName);
            fd.append('idArtist',   editArtistTs   ? editArtistTs.getValue()   : '');
            fd.append('idArranger', editArrangerTs  ? editArrangerTs.getValue() : '');
            fd.append('bpm',      $('#editChartBpm').val());
            fd.append('duration', parseDurationInput($('#editChartDuration').val()));
            fd.append('chartKey', $('#editChartKey').val().trim());
            fd.append('notes', $('#editChartNotes').val().trim());
            const pdfFile = $('#editChartPdf')[0].files[0];
            if (pdfFile) fd.append('pdfFile', pdfFile);

            $.ajax({
                type: 'POST', url: 'lib/action.php',
                data: fd, processData: false, contentType: false,
                dataType: 'JSON',
                success: function () {
                    bootstrap.Modal.getInstance(document.getElementById('editChartModal')).hide();
                    fetchData();
                    toastr.success('Chart updated successfully.');
                },
                error: ajaxErrorHandler
            });
        });

        // ── Delete Modal ─────────────────────────────────────────
        $('#chartTable').on('click', '.delete-chart-btn', function () {
            deleteRow = table.row($(this).closest('tr')).data();
            $('#deleteChartName').text(deleteRow.chartName);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteChartModal')).show();
        });

        $('#deleteChartModal').on('hidden.bs.modal', function () { deleteRow = null; });

        $('#confirmDeleteChartBtn').on('click', function () {
            if (!deleteRow) return;
            $.ajax({
                type: 'POST', url: 'lib/action.php',
                data: {action: 'deleteChart', idChart: deleteRow.idChart},
                dataType: 'JSON',
                success: function () {
                    bootstrap.Modal.getInstance(document.getElementById('deleteChartModal')).hide();
                    fetchData();
                    toastr.success('Chart deleted successfully.');
                },
                error: ajaxErrorHandler
            });
        });

        // ── Audio upload (inside edit modal) ─────────────────────
        $('#editChartAudio').on('change', function () {
            const file = this.files[0];
            if (!file) return;
            const idChart = $('#editChartId').val();
            if (!idChart) return;

            const fd = new FormData();
            fd.append('action',    'uploadChartAudio');
            fd.append('idChart',   idChart);
            fd.append('audioFile', file);

            $('#audioUploadProgress').removeClass('d-none');
            $.ajax({
                type: 'POST', url: 'lib/action.php',
                data: fd, processData: false, contentType: false,
                dataType: 'JSON',
                success: function (r) {
                    $('#audioUploadProgress').addClass('d-none');
                    $('#editCurrentAudioSection').removeClass('d-none');
                    $('#editCurrentAudioName').text(r.audioPath.split('/').pop());
                    // Update in-memory row so the table reflects change without full reload
                    if (currentEditRow) currentEditRow.audioPath = r.audioPath;
                    fetchData();
                    toastr.success('Audio uploaded.');
                },
                error: function (xhr) {
                    $('#audioUploadProgress').addClass('d-none');
                    ajaxErrorHandler(xhr);
                }
            });
        });

        $('#deleteAudioBtn').on('click', function () {
            const idChart = $('#editChartId').val();
            if (!idChart) return;
            $.ajax({
                type: 'POST', url: 'lib/action.php',
                data: {action: 'deleteChartAudio', idChart: idChart},
                dataType: 'JSON',
                success: function () {
                    $('#editCurrentAudioSection').addClass('d-none');
                    $('#editChartAudio').val('');
                    if (currentEditRow) currentEditRow.audioPath = '';
                    fetchData();
                    toastr.success('Audio removed.');
                },
                error: ajaxErrorHandler
            });
        });

        // ── Audio player modal ────────────────────────────────────
        const audioEl = document.getElementById('audioPlayerEl');

        function fmtTime(s) {
            if (isNaN(s) || !isFinite(s)) return '0:00';
            return Math.floor(s / 60) + ':' + String(Math.floor(s % 60)).padStart(2, '0');
        }

        function updateSeekUI() {
            const pct = audioEl.duration ? (audioEl.currentTime / audioEl.duration * 100) : 0;
            $('#audioProgress').css('width', pct + '%');
            $('#audioHandle').css('left', pct + '%');
            $('#audioCurrent').text(fmtTime(audioEl.currentTime));
        }

        audioEl.addEventListener('loadedmetadata', function () {
            $('#audioDuration').text(fmtTime(audioEl.duration));
            updateSeekUI();
        });
        audioEl.addEventListener('timeupdate', updateSeekUI);
        audioEl.addEventListener('ended', function () {
            $('#audioPlayBtn i').removeClass('bi-pause-fill').addClass('bi-play-fill');
        });

        $('#audioPlayBtn').on('click', function () {
            if (audioEl.paused) {
                audioEl.play();
                $(this).find('i').removeClass('bi-play-fill').addClass('bi-pause-fill');
            } else {
                audioEl.pause();
                $(this).find('i').removeClass('bi-pause-fill').addClass('bi-play-fill');
            }
        });

        $('#audioSkipBack').on('click', function () { audioEl.currentTime = Math.max(0, audioEl.currentTime - 10); });
        $('#audioSkipFwd').on('click', function ()  { audioEl.currentTime = Math.min(audioEl.duration || 0, audioEl.currentTime + 10); });
        $('#audioVolume').on('input', function () { audioEl.volume = this.value; });

        $('#audioSeekBar').on('click', function (e) {
            if (!audioEl.duration) return;
            const rect = this.getBoundingClientRect();
            const pct  = (e.clientX - rect.left) / rect.width;
            audioEl.currentTime = pct * audioEl.duration;
        });

        $('#audioPlayerModal').on('hidden.bs.modal', function () {
            audioEl.pause();
            audioEl.src = '';
            $('#audioPlayBtn i').removeClass('bi-pause-fill').addClass('bi-play-fill');
            $('#audioProgress').css('width', '0%');
            $('#audioHandle').css('left', '0%');
            $('#audioCurrent').text('0:00');
            $('#audioDuration').text('0:00');
        });

        $('#chartTable').on('click', '.play-audio-btn', function () {
            const row = table.row($(this).closest('tr')).data();
            openAudioPlayer(row.audioPath, row.chartName, row.artistName || row.arrangerName || '');
        });

        function openAudioPlayer(src, title, subtitle) {
            $('#audioPlayerChartName').text(title);
            $('#audioPlayerArtistName').text(subtitle);
            audioEl.src = src;
            audioEl.load();
            $('#audioPlayBtn i').removeClass('bi-pause-fill').addClass('bi-play-fill');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('audioPlayerModal')).show();
        }

        // ── Manage PDFs Modal ─────────────────────────────────────
        // PDF.js setup
        const pdfjsLib = window['pdfjs-dist/build/pdf'];
        pdfjsLib.GlobalWorkerOptions.workerSrc =
            'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';

        const COLOR_PALETTE = [
            {bg: '#0d6efd', text: '#fff'},
            {bg: '#198754', text: '#fff'},
            {bg: '#dc3545', text: '#fff'},
            {bg: '#fd7e14', text: '#000'},
            {bg: '#6f42c1', text: '#fff'},
            {bg: '#0dcaf0', text: '#000'},
            {bg: '#e83e8c', text: '#fff'},
            {bg: '#20c997', text: '#000'},
            {bg: '#ffc107', text: '#000'},
            {bg: '#6c757d', text: '#fff'},
            {bg: '#d63384', text: '#fff'},
            {bg: '#495057', text: '#fff'},
        ];

        let managePdfIdChart    = null;
        let managePdfInstruments = [];
        let managePdfPartsMap   = {};   // idInstrument -> part row
        let instrColors         = {};   // idInstrument -> color obj
        let colorIdx            = 0;
        let selectedInstrId     = null;
        let pageAssignments     = {};   // pageNum (int) -> {idInstrument, name, color}
        let savedPagesMap       = {};   // idInstrument -> [pageNums] (restored from DB on open)
        let pdfDoc              = null;

        function instrColor(id) {
            if (!instrColors[id]) {
                instrColors[id] = COLOR_PALETTE[colorIdx % COLOR_PALETTE.length];
                colorIdx++;
            }
            return instrColors[id];
        }

        // Open: called from Edit modal button
        $('#managePdfPartsBtn').on('click', function () {
            if (!currentEditRow) return;
            managePdfIdChart = currentEditRow.idChart;
            $('#managePdfChartName').text(currentEditRow.chartName);
            bootstrap.Modal.getInstance(document.getElementById('editChartModal')).hide();

            // Reset state
            selectedInstrId  = null;
            pageAssignments  = {};
            savedPagesMap    = {};
            instrColors      = {};
            colorIdx         = 0;
            pdfDoc           = null;
            $('#pdfPageGrid').empty();
            $('#instrumentSidebar').empty();
            $('#pdfStatusMsg').text('Loading…');
            $('#pdfPageCountBadge').text('');

            bootstrap.Modal.getOrCreateInstance(document.getElementById('managePdfModal')).show();
        });

        // Load data after modal fully shown (avoids layout issues)
        $('#managePdfModal').on('shown.bs.modal', function () {
            if (!managePdfIdChart) return;
            $.ajax({
                type: 'POST', url: 'lib/action.php',
                data: {action: 'getPdfPartsData', idChart: managePdfIdChart},
                dataType: 'JSON',
                success: function (r) {
                    managePdfInstruments = r.instruments || [];
                    managePdfPartsMap    = {};
                    savedPagesMap        = {};
                    (r.parts || []).forEach(p => {
                        managePdfPartsMap[p.idInstrument] = p;
                        if (p.pages && p.pages.length) {
                            savedPagesMap[p.idInstrument] = p.pages;
                        }
                    });

                    // Pre-assign colors
                    managePdfInstruments.forEach(i => instrColor(i.idInstrument));

                    renderInstrumentSidebar();

                    if (r.masterPdfUrl) {
                        loadPdfThumbnails(r.masterPdfUrl);
                    } else {
                        $('#pdfStatusMsg').text('No master PDF uploaded. Use the upload buttons in the sidebar.');
                    }
                },
                error: ajaxErrorHandler
            });
        });

        // ── Sidebar ───────────────────────────────────────────────
        function renderInstrumentSidebar() {
            const sidebar = $('#instrumentSidebar').empty();
            managePdfInstruments.forEach(inst => {
                const c      = instrColor(inst.idInstrument);
                const hasPdf = !!managePdfPartsMap[inst.idInstrument];
                sidebar.append(`
                    <div class="instrument-item rounded p-2 mb-1 border"
                         data-id="${inst.idInstrument}"
                         data-name="${escHtml(inst.instrumentName)}"
                         style="border-left-color:${c.bg} !important; border-left-width:5px !important;">
                        <div class="d-flex align-items-center gap-1">
                            <span class="fw-semibold small flex-grow-1">${escHtml(inst.instrumentName)}</span>
                            ${hasPdf ? `<a href="${managePdfPartsMap[inst.idInstrument].pdfPath}" target="_blank" class="btn btn-outline-secondary btn-sm py-0 px-1 has-pdf-link" title="View existing PDF" style="font-size:0.7rem"><i class="bi bi-file-earmark-pdf"></i></a>` : ''}
                            <label class="btn btn-outline-primary btn-sm py-0 px-1 mb-0" title="Upload PDF directly" style="font-size:0.7rem">
                                <i class="bi bi-upload"></i>
                                <input type="file" class="d-none direct-pdf-input" accept=".pdf">
                            </label>
                            <button class="btn btn-outline-danger btn-sm py-0 px-1 clear-inst-btn" title="Clear page selections" style="font-size:0.7rem"><i class="bi bi-x"></i></button>
                        </div>
                        <div class="page-count-label text-muted mt-1" style="font-size:0.7rem;">0 pages selected</div>
                    </div>
                `);
            });
        }

        // Select instrument on click (not on child buttons/labels)
        $(document).on('click', '#instrumentSidebar .instrument-item', function (e) {
            if ($(e.target).closest('label,button,a,input').length) return;
            selectedInstrId = $(this).data('id');
            $('#instrumentSidebar .instrument-item').removeClass('active');
            $(this).addClass('active');
        });

        // Clear one instrument's selections
        $(document).on('click', '#instrumentSidebar .clear-inst-btn', function () {
            const id = $(this).closest('.instrument-item').data('id');
            Object.keys(pageAssignments).forEach(p => {
                if (pageAssignments[p].idInstrument === id) {
                    delete pageAssignments[parseInt(p)];
                    clearThumb($(`#pdfPageGrid .page-thumb[data-page="${p}"]`));
                }
            });
            updateSidebarCount(id);
        });

        // Clear all
        $('#clearAllPagesBtn').on('click', function () {
            pageAssignments = {};
            $('#pdfPageGrid .page-thumb').each(function () { clearThumb($(this)); });
            $('#instrumentSidebar .instrument-item').each(function () {
                $(this).find('.page-count-label').text('0 pages selected');
            });
        });

        function updateSidebarCount(id) {
            const n = Object.values(pageAssignments).filter(a => a.idInstrument === id).length;
            $(`#instrumentSidebar .instrument-item[data-id="${id}"] .page-count-label`)
                .text(n + ' page' + (n !== 1 ? 's' : '') + ' selected');
        }

        // Direct upload per instrument (sidebar)
        $(document).on('change', '#instrumentSidebar .direct-pdf-input', function () {
            const file = this.files[0];
            if (!file) return;
            const item        = $(this).closest('.instrument-item');
            const idInstr     = item.data('id');
            const instrName   = item.data('name');
            const fd = new FormData();
            fd.append('action', 'uploadInstrumentPdf');
            fd.append('idChart', managePdfIdChart);
            fd.append('idInstrument', idInstr);
            fd.append('pdfFile', file);
            const input = this;
            $.ajax({
                type: 'POST', url: 'lib/action.php',
                data: fd, processData: false, contentType: false,
                dataType: 'JSON',
                success: function (r) {
                    toastr.success('PDF uploaded for ' + instrName + '.');
                    managePdfPartsMap[idInstr] = r;
                    // Show/update has-pdf link
                    let link = item.find('.has-pdf-link');
                    if (link.length) {
                        link.attr('href', r.pdfPath);
                    } else {
                        item.find('.d-flex').prepend(
                            `<a href="${r.pdfPath}" target="_blank" class="btn btn-outline-secondary btn-sm py-0 px-1 has-pdf-link" title="View existing PDF" style="font-size:0.7rem"><i class="bi bi-file-earmark-pdf"></i></a>`
                        );
                    }
                    input.value = '';
                },
                error: ajaxErrorHandler
            });
        });

        // ── PDF.js page rendering ─────────────────────────────────
        async function loadPdfThumbnails(url) {
            $('#pdfStatusMsg').text('Loading PDF…');
            $('#pdfPageGrid').empty();
            pdfDoc = null;

            try {
                pdfDoc = await pdfjsLib.getDocument(url).promise;
                const total = pdfDoc.numPages;
                $('#pdfPageCountBadge').text(total + ' pages');
                $('#pdfStatusMsg').text('Select an instrument, then click pages to assign them.');

                // Build all placeholder cells immediately
                for (let i = 1; i <= total; i++) {
                    $('#pdfPageGrid').append(`
                        <div class="page-thumb" data-page="${i}">
                            <div class="thumb-canvas-wrap page-placeholder d-flex align-items-center justify-content-center bg-light" style="aspect-ratio:210/297;">
                                <span class="spinner-border spinner-border-sm text-secondary"></span>
                            </div>
                            <div class="page-badge d-none"></div>
                            <div class="page-label">Page ${i}</div>
                        </div>
                    `);
                }

                // Render thumbnails in batches of 6 (one row at a time)
                for (let i = 1; i <= total; i += 6) {
                    const batch = [];
                    for (let j = i; j <= Math.min(i + 5, total); j++) {
                        batch.push(renderThumb(j));
                    }
                    await Promise.all(batch);
                }

                // Restore previously saved page assignments
                restoreSavedAssignments();
            } catch (err) {
                $('#pdfStatusMsg').text('Could not load PDF: ' + err.message);
            }
        }

        function restoreSavedAssignments() {
            for (const [instrId, pages] of Object.entries(savedPagesMap)) {
                const instrItem = $(`#instrumentSidebar .instrument-item[data-id="${instrId}"]`);
                if (!instrItem.length) continue;
                const name = instrItem.data('name');
                const c    = instrColor(instrId);
                pages.forEach(pageNum => {
                    pageAssignments[pageNum] = {idInstrument: instrId, name, color: c};
                    paintThumb($(`#pdfPageGrid .page-thumb[data-page="${pageNum}"]`), c, name);
                });
                updateSidebarCount(instrId);
            }
        }

        async function renderThumb(pageNum) {
            if (!pdfDoc) return;
            try {
                const page      = await pdfDoc.getPage(pageNum);
                // Scale canvas to exactly fill one 4-column cell so the full page is visible
                const grid      = document.getElementById('pdfPageGrid');
                const cellWidth = Math.floor((grid.clientWidth - 50) / 6); // 5 gaps × 10px
                const baseVp    = page.getViewport({scale: 1});
                const scale     = cellWidth / baseVp.width;
                const viewport  = page.getViewport({scale});
                const canvas    = document.createElement('canvas');
                canvas.width    = viewport.width;
                canvas.height   = viewport.height;
                canvas.style.cssText = 'display:block;width:100%;height:auto;';
                await page.render({canvasContext: canvas.getContext('2d'), viewport}).promise;
                const wrap = $(`#pdfPageGrid .page-thumb[data-page="${pageNum}"] .thumb-canvas-wrap`);
                wrap.removeClass('page-placeholder d-flex align-items-center justify-content-center bg-light').html(canvas);
            } catch (_) { /* ignore individual page errors */ }
        }

        // ── Page click → assign to selected instrument ────────────
        $(document).on('click', '#pdfPageGrid .page-thumb', function () {
            if (!selectedInstrId) {
                toastr.warning('Select an instrument from the sidebar first.');
                return;
            }
            const pageNum = parseInt($(this).data('page'));
            const prev    = pageAssignments[pageNum];

            if (prev && prev.idInstrument === selectedInstrId) {
                // Clicking the same instrument: deselect
                delete pageAssignments[pageNum];
                clearThumb($(this));
            } else {
                // Assign (or reassign)
                const prevId = prev ? prev.idInstrument : null;
                const c      = instrColor(selectedInstrId);
                const name   = $(`#instrumentSidebar .instrument-item[data-id="${selectedInstrId}"]`).data('name');
                pageAssignments[pageNum] = {idInstrument: selectedInstrId, name, color: c};
                paintThumb($(this), c, name);
                if (prevId) updateSidebarCount(prevId);
            }
            updateSidebarCount(selectedInstrId);
        });

        function paintThumb(thumb, c, name) {
            thumb.css('border-color', c.bg);
            thumb.find('.page-badge')
                .text(name)
                .css({background: c.bg, color: c.text})
                .removeClass('d-none');
        }

        function clearThumb(thumb) {
            thumb.css('border-color', '');
            thumb.find('.page-badge').addClass('d-none').text('');
        }

        // ── Save assignments → server splits ──────────────────────
        $('#doSplitBtn').on('click', function () {
            // Group pages by instrument
            const grouped = {};
            for (const [p, a] of Object.entries(pageAssignments)) {
                if (!grouped[a.idInstrument]) grouped[a.idInstrument] = [];
                grouped[a.idInstrument].push(parseInt(p));
            }
            const assignments = Object.entries(grouped).map(([id, pages]) => ({
                idInstrument: id,
                pages: pages.sort((x, y) => x - y)
            }));

            if (!assignments.length) {
                toastr.warning('No pages have been assigned to any instrument.');
                return;
            }

            const btn = $(this).prop('disabled', true)
                .html('<span class="spinner-border spinner-border-sm"></span> Saving…');

            $.ajax({
                type: 'POST', url: 'lib/action.php',
                data: {action: 'splitChartPdf', idChart: managePdfIdChart, assignments: JSON.stringify(assignments)},
                dataType: 'JSON',
                success: function (r) {
                    toastr.success(r.count + ' instrument PDF(s) saved.');
                    btn.prop('disabled', false).html('<i class="bi bi-check-lg"></i> Save Assignments');
                    // Update has-pdf links in sidebar
                    (r.results || []).forEach(res => {
                        managePdfPartsMap[res.idInstrument] = res;
                        const item = $(`#instrumentSidebar .instrument-item[data-id="${res.idInstrument}"]`);
                        let link = item.find('.has-pdf-link');
                        if (link.length) { link.attr('href', res.pdfPath); }
                        else {
                            item.find('.d-flex').prepend(
                                `<a href="${res.pdfPath}" target="_blank" class="btn btn-outline-secondary btn-sm py-0 px-1 has-pdf-link" title="View PDF" style="font-size:0.7rem"><i class="bi bi-file-earmark-pdf"></i></a>`
                            );
                        }
                    });
                },
                error: function (xhr) {
                    ajaxErrorHandler(xhr);
                    btn.prop('disabled', false).html('<i class="bi bi-check-lg"></i> Save Assignments');
                }
            });
        });

        function escHtml(str) {
            return $('<div>').text(str || '').html();
        }
    </script>
</html>
