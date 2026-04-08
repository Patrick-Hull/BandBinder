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
                <div class="col-10 mx-auto">
                    <h1>My Charts</h1>
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

                    <!-- PDF link -->
                    <div class="mb-3" id="viewPdfSection">
                        <a id="viewPdfLink" href="#" target="_blank" class="btn btn-outline-danger">
                            <i class="bi bi-file-earmark-pdf"></i> View PDF
                        </a>
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
                        data: function (d) { d.action = "getMyCharts"; },
                        error: ajaxErrorHandler
                    },
                    columns: [
                        {data: 'chartName',    title: 'Name'},
                        {data: 'artistName',   title: 'Artist'},
                        {data: 'arrangerName', title: 'Arranger'},
                        {data: 'chartKey',     title: 'Key'},
                        {data: 'bpm',          title: 'BPM'},
                        {data: 'myRating',     title: 'My Rating', render: function (d) { return starsDisplay(d); }},
                        {data: null,           title: '', defaultContent: ''},
                    ],
                    columnDefs: [
                        {targets: 0, orderable: true, searchable: true},
                        {targets: 1, orderable: true, searchable: true},
                        {targets: 2, orderable: true, searchable: true},
                        {targets: 3, orderable: true, searchable: true},
                        {targets: 4, orderable: true, searchable: false},
                        {targets: 5, orderable: true, searchable: false},
                        {targets: 6, orderable: false, searchable: false,
                            render: function () {
                                return '<button class="btn btn-sm btn-outline-primary view-chart-btn"><i class="bi bi-eye"></i> View</button>';
                            }
                        },
                    ],
                    createdRow: function (row, data) {
                        $(row).attr('data-id', data.idChart);
                    },
                    responsive: true,
                });
            }
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

            if (row.myPdfPath) {
                $('#viewPdfLink').attr('href', row.myPdfPath);
                $('#viewPdfSection').removeClass('d-none');
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
    </script>
</html>
