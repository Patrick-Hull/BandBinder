<?php
require_once __DIR__ . '/../../../lib/util_all.php';
$pageName = "My Charts";
if (!in_array('charts.view', $_SESSION['user']['permissions'])) {
    header("Location: /");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title><?php echo $pageName; ?></title>
        <?php require_once __DIR__ . '/../../../lib/html_header/all.php'; ?>
        <style>
            .star-rating { font-size: 1.3rem; cursor: pointer; color: #ccc; }
            .star-rating .star.active { color: #f5a623; }
            .star-rating .star:hover,
            .star-rating .star:hover ~ .star { color: #ccc; }
            .star-rating:hover .star { color: #f5a623; }
            .star-rating .star:hover ~ .star { color: #ccc !important; }
        </style>
    </head>
    <body>
        <?php require_once __DIR__ . '/../../../lib/navbar.php'; ?>
        <div class="container-fluid mt-4">
            <div class="row">
                <div class="col-12 col-md-11 col-xl-10 mx-auto">
                    <h1>My Charts</h1>
                    <div class="mb-3">
                        <label for="categoryFilter" class="form-label d-inline me-2">Filter by Category:</label>
                        <select id="categoryFilter" class="form-control form-control-sm d-inline-block" style="width:auto;">
                            <option value="">All Categories</option>
                        </select>
                    </div>
                    <table id="myChartTable" class="table table-striped table-bordered table-sm"></table>
                </div>
            </div>
        </div>
        <?php require_once __DIR__ . '/../../../lib/html_footer/all.php'; ?>
    </body>

    <!-- ═══════════════════════════════════════════════════════════
         VIEW CHART MODAL
    ════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="viewChartModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="viewChartTitle"></h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="viewChartId">

                    <!-- Chart info -->
                    <div class="row mb-3">
                        <div class="col-md-4">
                            <small class="text-muted">Artist</small>
                            <div id="viewArtistName" class="fw-semibold">—</div>
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted">Arranger</small>
                            <div id="viewArrangerName" class="fw-semibold">—</div>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">BPM</small>
                            <div id="viewBpm" class="fw-semibold">—</div>
                        </div>
                        <div class="col-md-2">
                            <small class="text-muted">Key</small>
                            <div id="viewKey" class="fw-semibold">—</div>
                        </div>
                    </div>

                    <div class="mb-3" id="viewNotesSection">
                        <small class="text-muted">Chart Notes</small>
                        <div id="viewNotes" class="border rounded p-2 bg-light small"></div>
                    </div>

                    <!-- PDF / Audio links -->
                    <div class="mb-3 d-flex flex-wrap gap-2" id="viewPdfSection">
                        <a id="viewPdfLink" href="#" target="_blank" class="btn btn-outline-danger">
                            <i class="bi bi-file-earmark-pdf"></i> View PDF
                        </a>
                        <button type="button" id="viewAudioBtn" class="btn btn-outline-success d-none">
                            <i class="bi bi-music-note-beamed me-1"></i> Play Audio
                        </button>
                    </div>

                    <hr>

                    <!-- My fields -->
                    <h6>My Notes</h6>

                    <!-- Star rating -->
                    <div class="mb-3">
                        <label class="form-label">My Rating</label>
                        <div class="star-rating d-flex flex-row-reverse justify-content-end" id="starRatingWidget">
                            <span class="star" data-value="5">&#9733;</span>
                            <span class="star" data-value="4">&#9733;</span>
                            <span class="star" data-value="3">&#9733;</span>
                            <span class="star" data-value="2">&#9733;</span>
                            <span class="star" data-value="1">&#9733;</span>
                        </div>
                        <input type="hidden" id="myStarRating" value="">
                    </div>

                    <div class="mb-3">
                        <label for="myPrivateNotes" class="form-label">Private Notes <span class="text-muted small">(only you can see these)</span></label>
                        <textarea class="form-control" id="myPrivateNotes" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="myInstrumentNotes" class="form-label">Instrument Notes <span class="text-muted small">(visible to others with your instrument)</span></label>
                        <textarea class="form-control" id="myInstrumentNotes" rows="2"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="myFamilyNotes" class="form-label">Family Notes <span class="text-muted small">(visible to your instrument family)</span></label>
                        <textarea class="form-control" id="myFamilyNotes" rows="2"></textarea>
                    </div>

                    <!-- Others' notes -->
                    <div id="othersInstrumentNotesSection" class="d-none mb-3">
                        <hr>
                        <h6>Instrument Section Notes <span class="text-muted small">(from others in your instrument section)</span></h6>
                        <div id="othersInstrumentNotesList"></div>
                    </div>

                    <div id="othersFamilyNotesSection" class="d-none mb-3">
                        <h6>Band Family Notes <span class="text-muted small">(from others in your instrument family)</span></h6>
                        <div id="othersFamilyNotesList"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveMyFieldsBtn">Save My Notes</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        $(function () { fetchData(); });

        let table = null;

        function ajaxErrorHandler(jqXHR) {
            toastr.error(jqXHR.responseJSON?.message || "An unexpected error occurred.");
        }

        function renderStars(value) {
            $('#starRatingWidget .star').each(function () {
                const v = parseInt($(this).data('value'));
                $(this).toggleClass('active', v <= value);
            });
            $('#myStarRating').val(value || '');
        }

        function starsDisplay(n) {
            if (!n) return '<span class="text-muted">—</span>';
            return '<span style="color:#f5a623">' + '★'.repeat(n) + '</span>' + '☆'.repeat(5 - n);
        }

        function escHtml(str) {
            if (!str) return '';
            return $('<div>').text(str).html();
        }

        function isLightColour(hex) {
            if (!hex || hex.length < 7) return true;
            var r = parseInt(hex.slice(1, 3), 16);
            var g = parseInt(hex.slice(3, 5), 16);
            var b = parseInt(hex.slice(5, 7), 16);
            return (r * 299 + g * 587 + b * 114) / 1000 > 128;
        }

        // ── DataTable ────────────────────────────────────────────
        function fetchData() {
            if (table) {
                table.ajax.reload();
            } else {
                table = $('#myChartTable').DataTable({
                    processing: true,
                    serverSide: false,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    pageLength: 25,
                    ajax: {
                        url: 'lib/action.php',
                        type: 'POST',
                        data: function (d) {
                            d.action = "getMyCharts";
                            var catFilter = $('#categoryFilter').val();
                            if (catFilter) d.categoryFilter = catFilter;
                        },
                        error: ajaxErrorHandler,
                        dataSrc: function(json) {
                            if (json.categoriesMap) {
                                $.each(json.data, function(i, row) {
                                    row.categories = json.categoriesMap[row.idChart] || [];
                                });
                            }
                            if (json.ratingsMap) {
                                $.each(json.data, function(i, row) {
                                    var r = json.ratingsMap[row.idChart] || {};
                                    row.avgRating = r.avgRating || null;
                                    row.ratingCount = r.ratingCount || 0;
                                });
                            }
                            return json.data;
                        }
                    },
                    columns: [
                        {data: 'chartName',    title: 'Name'},
                        {data: 'artistName',   title: 'Artist'},
                        {data: 'arrangerName', title: 'Arranger'},
                        {data: 'chartKey',     title: 'Key'},
                        {data: 'bpm',          title: 'BPM'},
                        {data: 'categories', title: 'Category', render: function(d) {
                            if (!d || !d.length) return '<span class="text-muted">—</span>';
                            return d.map(function(c) {
                                var style = c.categoryColour ? 'background-color:' + c.categoryColour + ';color:' + (isLightColour(c.categoryColour) ? '#000' : '#fff') + ';' : '';
                                return '<span class="badge me-1" style="' + style + '">' + escHtml(c.categoryName) + '</span>';
                            }).join('');
                        }},
                        {data: 'myRating',     title: 'My Rating', render: function (d) { return starsDisplay(d); }},
                        {data: 'avgRating', title: 'Band Rating', render: function(d, t, row) {
                            var r = row.avgRating || 0;
                            var c = row.ratingCount || 0;
                            if (!r) return '<span class="text-muted">—</span>';
                            return '<span style="color:#f5a623">' + '★'.repeat(Math.round(r)) + '</span> ' + r.toFixed(1) + ' <span class="text-muted small">(' + c + ')</span>';
                        }},
                        {data: null,           title: '', defaultContent: ''},
                    ],
                    columnDefs: [
                        {targets: 0, orderable: true, searchable: true},
                        {targets: 1, orderable: true, searchable: true},
                        {targets: 2, orderable: true, searchable: true},
                        {targets: 3, orderable: true, searchable: true},
                        {targets: 4, orderable: true, searchable: false},
                        {targets: 5, orderable: true, searchable: true},
                        {targets: 6, orderable: true, searchable: false},
                        {targets: 7, orderable: true, searchable: false},
                        {targets: 8, orderable: false, searchable: false,
                            render: function (d, t, row) {
                                let html = '<button class="btn btn-sm btn-outline-primary view-chart-btn me-1"><i class="bi bi-eye"></i> View</button>';
                                if (row.myPdfPath) {
                                    html += `<a href="${row.myPdfPath}" target="_blank" class="btn btn-sm btn-outline-danger me-1" title="Open PDF"><i class="bi bi-file-earmark-pdf"></i></a>`;
                                }
                                if (row.audioPath) {
                                    html += `<button class="btn btn-sm btn-outline-success play-audio-btn" title="Play Audio"><i class="bi bi-music-note-beamed"></i></button>`;
                                }
                                return html;
                            }
                        },
                    ],
                    createdRow: function (row, data) {
                        $(row).attr('data-id', data.idChart);
                    },
                    responsive: true,
                });
            }

            // Load category filter options
            $.ajax({
                type: 'POST', url: 'lib/action.php',
                data: { action: 'getCategoriesList' },
                dataType: 'JSON',
                success: function(r) {
                    var sel = $('#categoryFilter');
                    (r.data || []).forEach(function(o) {
                        sel.append('<option value="' + o.value + '">' + escHtml(o.text) + '</option>');
                    });
                }
            });

            // Category filter change handler
            $('#categoryFilter').on('change', function() {
                table.ajax.reload();
            });
        }

        // ── Open view modal ──────────────────────────────────────
        $('#myChartTable').on('click', '.view-chart-btn', function () {
            const row = table.row($(this).closest('tr')).data();
            openViewModal(row);
        });

        function openViewModal(row) {
            $('#viewChartId').val(row.idChart);
            $('#viewChartTitle').text(row.chartName);
            $('#viewArtistName').text(row.artistName  || '—');
            $('#viewArrangerName').text(row.arrangerName || '—');
            $('#viewBpm').text(row.bpm || '—');
            $('#viewKey').text(row.chartKey || '—');

            if (row.notes) {
                $('#viewNotes').text(row.notes);
                $('#viewNotesSection').removeClass('d-none');
            } else {
                $('#viewNotesSection').addClass('d-none');
            }

            if (row.myPdfPath || row.audioPath) {
                $('#viewPdfSection').removeClass('d-none');
                if (row.myPdfPath) {
                    $('#viewPdfLink').attr('href', row.myPdfPath).removeClass('d-none');
                } else {
                    $('#viewPdfLink').addClass('d-none');
                }
                if (row.audioPath) {
                    $('#viewAudioBtn').removeClass('d-none').data('audio-src', row.audioPath);
                } else {
                    $('#viewAudioBtn').addClass('d-none');
                }
            } else {
                $('#viewPdfSection').addClass('d-none');
            }

            // My fields
            renderStars(row.myRating || 0);
            $('#myPrivateNotes').val(row.myPrivateNotes  || '');
            $('#myInstrumentNotes').val(row.myInstrumentNotes || '');
            $('#myFamilyNotes').val(row.myFamilyNotes   || '');

            // Others' notes
            if (row.instrumentNotes && row.instrumentNotes.length) {
                const html = row.instrumentNotes.map(n =>
                    `<div class="mb-2 p-2 border rounded bg-light small"><strong>${escHtml(n.nameShort || n.username)}:</strong> ${escHtml(n.instrumentNotes)}</div>`
                ).join('');
                $('#othersInstrumentNotesList').html(html);
                $('#othersInstrumentNotesSection').removeClass('d-none');
            } else {
                $('#othersInstrumentNotesSection').addClass('d-none');
            }

            if (row.familyNotes && row.familyNotes.length) {
                const html = row.familyNotes.map(n =>
                    `<div class="mb-2 p-2 border rounded bg-light small"><strong>${escHtml(n.nameShort || n.username)}:</strong> ${escHtml(n.familyNotes)}</div>`
                ).join('');
                $('#othersFamilyNotesList').html(html);
                $('#othersFamilyNotesSection').removeClass('d-none');
            } else {
                $('#othersFamilyNotesSection').addClass('d-none');
            }

            bootstrap.Modal.getOrCreateInstance(document.getElementById('viewChartModal')).show();
        }

        // ── Star rating widget ────────────────────────────────────
        $('#starRatingWidget').on('click', '.star', function () {
            renderStars(parseInt($(this).data('value')));
        });

        // ── Save my fields ────────────────────────────────────────
        $('#saveMyFieldsBtn').on('click', function () {
            const idChart         = $('#viewChartId').val();
            const starRating      = $('#myStarRating').val() || null;
            const privateNotes    = $('#myPrivateNotes').val().trim();
            const instrumentNotes = $('#myInstrumentNotes').val().trim();
            const familyNotes     = $('#myFamilyNotes').val().trim();

            $.ajax({
                type: 'POST', url: 'lib/action.php',
                data: {action: 'saveMyFields', idChart, starRating, privateNotes, instrumentNotes, familyNotes},
                dataType: 'JSON',
                success: function () {
                    toastr.success('Notes saved.');
                    fetchData();
                },
                error: ajaxErrorHandler
            });
        });

        function escHtml(str) {
            return $('<div>').text(str || '').html();
        }

        // ── Audio player ──────────────────────────────────────────
        const myAudioEl = document.getElementById('myAudioPlayerEl');

        function fmtTime(s) {
            if (isNaN(s) || !isFinite(s)) return '0:00';
            return Math.floor(s / 60) + ':' + String(Math.floor(s % 60)).padStart(2, '0');
        }

        myAudioEl.addEventListener('loadedmetadata', function () {
            $('#myAudioDuration').text(fmtTime(myAudioEl.duration));
        });
        myAudioEl.addEventListener('timeupdate', function () {
            const pct = myAudioEl.duration ? (myAudioEl.currentTime / myAudioEl.duration * 100) : 0;
            $('#myAudioProgress').css('width', pct + '%');
            $('#myAudioHandle').css('left', pct + '%');
            $('#myAudioCurrent').text(fmtTime(myAudioEl.currentTime));
        });
        myAudioEl.addEventListener('ended', function () {
            $('#myAudioPlayBtn i').removeClass('bi-pause-fill').addClass('bi-play-fill');
        });

        $('#myAudioPlayBtn').on('click', function () {
            if (myAudioEl.paused) {
                myAudioEl.play();
                $(this).find('i').removeClass('bi-play-fill').addClass('bi-pause-fill');
            } else {
                myAudioEl.pause();
                $(this).find('i').removeClass('bi-pause-fill').addClass('bi-play-fill');
            }
        });

        $('#myAudioSkipBack').on('click', function () { myAudioEl.currentTime = Math.max(0, myAudioEl.currentTime - 10); });
        $('#myAudioSkipFwd').on('click', function ()  { myAudioEl.currentTime = Math.min(myAudioEl.duration || 0, myAudioEl.currentTime + 10); });
        $('#myAudioVolume').on('input', function () { myAudioEl.volume = this.value; });

        $('#myAudioSeekBar').on('click', function (e) {
            if (!myAudioEl.duration) return;
            const rect = this.getBoundingClientRect();
            myAudioEl.currentTime = ((e.clientX - rect.left) / rect.width) * myAudioEl.duration;
        });

        $('#myAudioPlayerModal').on('hidden.bs.modal', function () {
            myAudioEl.pause();
            myAudioEl.src = '';
            $('#myAudioPlayBtn i').removeClass('bi-pause-fill').addClass('bi-play-fill');
            $('#myAudioProgress').css('width', '0%');
            $('#myAudioHandle').css('left', '0%');
            $('#myAudioCurrent, #myAudioDuration').text('0:00');
        });

        // Table row audio button
        $('#myChartTable').on('click', '.play-audio-btn', function () {
            const row = table.row($(this).closest('tr')).data();
            openMyAudioPlayer(row.audioPath, row.chartName, row.artistName || row.arrangerName || '');
        });

        // View modal audio button
        $('#viewAudioBtn').on('click', function () {
            const src = $(this).data('audio-src');
            const title = $('#viewChartTitle').text();
            const subtitle = $('#viewArtistName').text();
            openMyAudioPlayer(src, title, subtitle);
        });

        function openMyAudioPlayer(src, title, subtitle) {
            $('#myAudioChartName').text(title);
            $('#myAudioArtistName').text(subtitle !== '—' ? subtitle : '');
            myAudioEl.src = src;
            myAudioEl.load();
            $('#myAudioPlayBtn i').removeClass('bi-pause-fill').addClass('bi-play-fill');
            bootstrap.Modal.getOrCreateInstance(document.getElementById('myAudioPlayerModal')).show();
        }
    </script>

    <!-- ═══════════════════════════════════════════════════════════
         AUDIO PLAYER MODAL
    ════════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="myAudioPlayerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" style="max-width:480px">
            <div class="modal-content">
                <div class="modal-header border-0 pb-0">
                    <div>
                        <div class="fw-semibold" id="myAudioChartName"></div>
                        <div class="text-muted small" id="myAudioArtistName"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body pt-2">
                    <audio id="myAudioPlayerEl" src="" preload="metadata" style="display:none"></audio>
                    <div class="audio-player-bar mb-3" id="myAudioSeekBar">
                        <div class="audio-player-progress" id="myAudioProgress"></div>
                        <div class="audio-player-handle" id="myAudioHandle"></div>
                    </div>
                    <div class="d-flex justify-content-between text-muted small mb-3 px-1">
                        <span id="myAudioCurrent">0:00</span>
                        <span id="myAudioDuration">0:00</span>
                    </div>
                    <div class="d-flex align-items-center justify-content-center gap-3">
                        <button class="btn btn-outline-secondary btn-sm audio-ctrl-btn" id="myAudioSkipBack" title="Back 10s">
                            <i class="bi bi-skip-backward-fill"></i>
                        </button>
                        <button class="btn btn-primary audio-play-btn" id="myAudioPlayBtn" title="Play/Pause">
                            <i class="bi bi-play-fill fs-5"></i>
                        </button>
                        <button class="btn btn-outline-secondary btn-sm audio-ctrl-btn" id="myAudioSkipFwd" title="Forward 10s">
                            <i class="bi bi-skip-forward-fill"></i>
                        </button>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-3 px-1">
                        <i class="bi bi-volume-down text-muted"></i>
                        <input type="range" class="form-range flex-grow-1" id="myAudioVolume" min="0" max="1" step="0.05" value="1">
                        <i class="bi bi-volume-up text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .audio-player-bar {
            position: relative; height: 6px;
            background: var(--bs-border-color, #dee2e6);
            border-radius: 3px; cursor: pointer;
        }
        .audio-player-progress {
            height: 100%; background: #0d6efd;
            border-radius: 3px; width: 0%;
            transition: width .1s linear;
        }
        .audio-player-handle {
            position: absolute; top: 50%; left: 0%;
            transform: translate(-50%, -50%);
            width: 14px; height: 14px; border-radius: 50%;
            background: #0d6efd;
            box-shadow: 0 0 0 3px rgba(13,110,253,.25);
            transition: left .1s linear;
        }
        .audio-play-btn {
            width: 52px; height: 52px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; padding: 0;
        }
        .audio-ctrl-btn {
            width: 36px; height: 36px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center; padding: 0;
        }
    </style>
</html>
