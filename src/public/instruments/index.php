<?php
require_once __DIR__ . '/../../lib/util_all.php';
$pageName = "Instruments";
$canCreateInstrument = in_array('instruments.create', $_SESSION['user']['permissions']);
$canEditInstrument   = in_array('instruments.edit',   $_SESSION['user']['permissions']);
$canDeleteInstrument = in_array('instruments.delete', $_SESSION['user']['permissions']);
$canCreateFamily     = in_array('instrumentFamilies.create', $_SESSION['user']['permissions']);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title><?php echo $pageName; ?></title>
        <?php require_once __DIR__ . '/../../lib/html_header/all.php'; ?>
        <?php require_once __DIR__ . '/../../lib/html_header/tomselect.php'; ?>
        <style>
            .drag-handle {
                cursor: grab;
                color: #aaa;
                font-size: 1.1em;
                user-select: none;
            }
            .drag-handle:active { cursor: grabbing; }
            .sortable-ghost { opacity: 0.4; }
        </style>
    </head>
    <body>
        <?php require_once __DIR__ . '/../../lib/navbar.php'; ?>
        <div class="container-fluid mt-4">
            <div class="row">
                <div class="col-8 mx-auto">
                    <h1>Instruments</h1>

                    <?php if($canCreateInstrument): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createInstrumentModal">Create</button>
                        <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#preseedModal">Preseed</button>
                    <?php endif; ?>

                    <table id="instrumentTable" class="table table-striped table-bordered table-sm mt-3"></table>

                </div>
            </div>
        </div>
        <?php require_once __DIR__ . '/../../lib/html_footer/all.php'; ?>
    </body>


    <!-- Create Instrument Modal -->
    <div class="modal fade" id="createInstrumentModal" tabindex="-1" aria-labelledby="createInstrumentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="createInstrumentModalLabel">Create Instrument</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="instrumentName" class="form-label">Instrument Name</label>
                        <input type="text" class="form-control" id="instrumentName" placeholder="e.g. Trumpet">
                    </div>
                    <div class="mb-3">
                        <label for="instrumentFamilySelect" class="form-label">Instrument Family</label>
                        <select id="instrumentFamilySelect" class="form-control"></select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveInstrumentBtn">Save Instrument</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Preseed Modal -->
    <div class="modal fade" id="preseedModal" tabindex="-1" aria-labelledby="preseedModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="preseedModalLabel">Preseed Instruments</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Select a band type to preseed instruments and instrument families:</p>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="preseedType" id="preseedBigBand" value="bigband" checked>
                            <label class="form-check-label" for="preseedBigBand">Big Band / Jazz Band</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="preseedType" id="preseedClassical" value="classical">
                            <label class="form-check-label" for="preseedClassical">Classical / Orchestra</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="preseedType" id="preseedConcert" value="concert">
                            <label class="form-check-label" for="preseedConcert">Concert Band</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="preseedType" id="preseedSmall" value="small">
                            <label class="form-check-label" for="preseedSmall">Small Ensemble / Combo</label>
                        </div>
                    </div>
                    <div id="preseedPreview" class="border rounded p-2 bg-light" style="max-height: 300px; overflow-y: auto;">
                        <small class="text-muted">Click "Add Instruments" to preview what will be added.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="addPreseedBtn">Add Instruments</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Instrument Confirmation Modal -->
    <div class="modal fade" id="deleteInstrumentModal" tabindex="-1" aria-labelledby="deleteInstrumentModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="deleteInstrumentModalLabel">Delete Instrument</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <strong id="deleteInstrumentName"></strong>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteInstrumentBtn"><i class="bi bi-trash"></i> Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Instrument Modal -->
    <div class="modal fade" id="editInstrumentModal" tabindex="-1" aria-labelledby="editInstrumentModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="editInstrumentModalLabel">Edit Instrument</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editInstrumentId">
                    <div class="mb-3">
                        <label for="editInstrumentName" class="form-label">Instrument Name</label>
                        <input type="text" class="form-control" id="editInstrumentName">
                    </div>
                    <div class="mb-3">
                        <label for="editInstrumentFamilySelect" class="form-label">Instrument Family</label>
                        <select id="editInstrumentFamilySelect" class="form-control"></select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="updateInstrumentBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>


    <script>
        const canCreateFamily    = <?= json_encode($canCreateFamily) ?>;
        const canEditInstrument  = <?= json_encode($canEditInstrument) ?>;
        const canDeleteInstrument = <?= json_encode($canDeleteInstrument) ?>;

        $(function(){
            fetchData();
        });

        let table       = null;
        let sortable    = null;
        let familyTomSelect     = null;
        let editFamilyTomSelect = null;
        let currentEditRow      = null;

        function ajaxErrorHandler(jqXHR) {
            const errorMsg = jqXHR.responseJSON?.message || "An unexpected error occurred.";
            toastr.error(errorMsg);
        }

        function initSortable() {
            if (!canEditInstrument) return;
            if (sortable) { sortable.destroy(); sortable = null; }
            const tbody = document.querySelector('#instrumentTable tbody');
            if (!tbody) return;
            sortable = new Sortable(tbody, {
                handle: '.drag-handle',
                animation: 150,
                ghostClass: 'sortable-ghost',
                onEnd: function () {
                    const rows = tbody.querySelectorAll('tr[data-id]');
                    const orderedIds = Array.from(rows).map(tr => tr.dataset.id);
                    $.ajax({
                        type: 'POST',
                        url: 'lib/action.php',
                        data: {action: 'updateInstrumentOrder', orderedIds: orderedIds},
                        dataType: 'JSON',
                        error: ajaxErrorHandler
                    });
                }
            });
        }

        const columns = canEditInstrument
            ? [
                {data: null, defaultContent: '<span class="drag-handle">&#8942;&#8942;</span>'},
                {data: 'instrumentName'},
                {data: 'instrumentFamilyName'},
                {data: null, defaultContent: ''},
              ]
            : [
                {data: 'instrumentName'},
                {data: 'instrumentFamilyName'},
              ];

        const columnDefs = canEditInstrument
            ? [
                {targets: 0, orderable: false, searchable: false, title: '', width: '24px'},
                {targets: 1, orderable: true,  searchable: true,  title: 'Instrument'},
                {targets: 2, orderable: true,  searchable: true,  title: 'Family'},
                {targets: 3, orderable: false, searchable: false, title: '',
                    render: function(data, type, row) {
                        let html = '';
                        if (canEditInstrument) {
                            html += '<button class="btn btn-sm btn-outline-secondary edit-instrument-btn me-1"><i class="bi bi-pencil"></i> Edit</button>';
                        }
                        if (canDeleteInstrument) {
                            html += '<button class="btn btn-sm btn-outline-danger delete-instrument-btn"><i class="bi bi-trash"></i> Delete</button>';
                        }
                        return html;
                    }
                },
              ]
            : [
                {targets: 0, orderable: true, searchable: true, title: 'Instrument'},
                {targets: 1, orderable: true, searchable: true, title: 'Family'},
              ];

        const fetchData = () => {
            if (table) {
                table.ajax.reload(function() {
                    initSortable();
                });
            } else {
                table = $('#instrumentTable').DataTable({
                    processing: true,
                    serverSide: false,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    pageLength: -1,
                    ajax: {
                        url: 'lib/action.php',
                        type: 'POST',
                        data: function (d) { d.action = "getInstruments"; },
                        error: ajaxErrorHandler
                    },
                    columns: columns,
                    columnDefs: columnDefs,
                    createdRow: function(row, data) {
                        if (canEditInstrument) {
                            $(row).attr('data-id', data.idInstrument);
                        }
                    },
                    drawCallback: function() {
                        initSortable();
                    },
                    responsive: true,
                });

                setTimeout(() => { table.draw(); }, 200);
            }
        };

        // ── Edit button click ────────────────────────────────────────────
        $('#instrumentTable').on('click', '.edit-instrument-btn', function () {
            currentEditRow = table.row($(this).closest('tr')).data();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editInstrumentModal')).show();
        });

        // ── Delete button click ──────────────────────────────────────────
        let deleteRow = null;
        $('#instrumentTable').on('click', '.delete-instrument-btn', function () {
            deleteRow = table.row($(this).closest('tr')).data();
            $('#deleteInstrumentName').text(deleteRow.instrumentName);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteInstrumentModal')).show();
        });

        $('#deleteInstrumentModal').on('hidden.bs.modal', function () {
            deleteRow = null;
        });

        $('#confirmDeleteInstrumentBtn').on('click', function () {
            if (!deleteRow) return;
            $.ajax({
                type: 'POST',
                url: 'lib/action.php',
                data: {action: 'deleteInstrument', idInstrument: deleteRow.idInstrument},
                dataType: 'JSON',
                success: function () {
                    bootstrap.Modal.getInstance(document.getElementById('deleteInstrumentModal')).hide();
                    fetchData();
                    toastr.success('Instrument deleted successfully.');
                },
                error: ajaxErrorHandler
            });
        });

        // ── Edit modal: populate on open ─────────────────────────────────
        $('#editInstrumentModal').on('shown.bs.modal', function () {
            if (!currentEditRow) return;

            $('#editInstrumentId').val(currentEditRow.idInstrument);
            $('#editInstrumentName').val(currentEditRow.instrumentName);

            $.ajax({
                type: 'POST',
                url: 'lib/action.php',
                data: {action: 'getInstrumentFamilies'},
                dataType: 'JSON',
                success: function (response) {
                    const options = response.data || [];
                    const tsConfig = {
                        options: options,
                        valueField: 'value',
                        labelField: 'text',
                        searchField: 'text',
                        placeholder: 'Select a family...',
                        create: canCreateFamily
                            ? function (input, callback) {
                                $.ajax({
                                    type: 'POST',
                                    url: 'lib/action.php',
                                    data: {action: 'createInstrumentFamily', instrumentFamilyName: input},
                                    dataType: 'JSON',
                                    success: function (data) { callback({value: data.id, text: data.name}); },
                                    error: function (xhr) { ajaxErrorHandler(xhr); callback(); }
                                });
                            }
                            : false,
                    };
                    editFamilyTomSelect = new TomSelect('#editInstrumentFamilySelect', tsConfig);
                    editFamilyTomSelect.setValue(currentEditRow.idInstrumentFamily, true);
                },
                error: ajaxErrorHandler
            });
        });

        // ── Edit modal: cleanup on close ─────────────────────────────────
        $('#editInstrumentModal').on('hidden.bs.modal', function () {
            if (editFamilyTomSelect) { editFamilyTomSelect.destroy(); editFamilyTomSelect = null; }
            currentEditRow = null;
        });

        // ── Save edit ────────────────────────────────────────────────────
        $('#updateInstrumentBtn').on('click', function () {
            const idInstrument      = $('#editInstrumentId').val();
            const instrumentName    = $('#editInstrumentName').val().trim();
            const idInstrumentFamily = editFamilyTomSelect ? editFamilyTomSelect.getValue() : '';

            if (!instrumentName || !idInstrumentFamily) {
                toastr.error('Please provide an instrument name and select a family.');
                return;
            }

            $.ajax({
                type: 'POST',
                url: 'lib/action.php',
                data: {
                    action: 'updateInstrument',
                    idInstrument: idInstrument,
                    instrumentName: instrumentName,
                    idInstrumentFamily: idInstrumentFamily,
                },
                dataType: 'JSON',
                success: function () {
                    bootstrap.Modal.getInstance(document.getElementById('editInstrumentModal')).hide();
                    fetchData();
                    toastr.success('Instrument updated successfully.');
                },
                error: ajaxErrorHandler
            });
        });

        // ── Create modal: init TomSelect on open ─────────────────────────
        $('#createInstrumentModal').on('shown.bs.modal', function () {
            $('#instrumentName').val('');

            $.ajax({
                type: 'POST',
                url: 'lib/action.php',
                data: {action: 'getInstrumentFamilies'},
                dataType: 'JSON',
                success: function (response) {
                    const options = response.data || [];
                    const tsConfig = {
                        options: options,
                        valueField: 'value',
                        labelField: 'text',
                        searchField: 'text',
                        placeholder: 'Select a family...',
                        create: canCreateFamily
                            ? function (input, callback) {
                                $.ajax({
                                    type: 'POST',
                                    url: 'lib/action.php',
                                    data: {action: 'createInstrumentFamily', instrumentFamilyName: input},
                                    dataType: 'JSON',
                                    success: function (data) { callback({value: data.id, text: data.name}); },
                                    error: function (xhr) { ajaxErrorHandler(xhr); callback(); }
                                });
                            }
                            : false,
                    };
                    familyTomSelect = new TomSelect('#instrumentFamilySelect', tsConfig);
                },
                error: ajaxErrorHandler
            });
        });

        // ── Create modal: cleanup on close ───────────────────────────────
        $('#createInstrumentModal').on('hidden.bs.modal', function () {
            if (familyTomSelect) { familyTomSelect.destroy(); familyTomSelect = null; }
        });

        // ── Save create ──────────────────────────────────────────────────
        $('#saveInstrumentBtn').on('click', function () {
            const instrumentName    = $('#instrumentName').val().trim();
            const idInstrumentFamily = familyTomSelect ? familyTomSelect.getValue() : '';

            if (!instrumentName || !idInstrumentFamily) {
                toastr.error('Please provide an instrument name and select a family.');
                return;
            }

            $.ajax({
                type: 'POST',
                url: 'lib/action.php',
                data: {
                    action: 'createInstrument',
                    instrumentName: instrumentName,
                    idInstrumentFamily: idInstrumentFamily,
                },
                dataType: 'JSON',
                success: function () {
                    bootstrap.Modal.getInstance(document.getElementById('createInstrumentModal')).hide();
                    fetchData();
                    toastr.success('Instrument created successfully.');
                },
                error: ajaxErrorHandler
            });
        });

        // ----- Preseed -----
        const preseedData = {
            bigband: {
                families: [
                    {name: 'Saxophone Section', prefix: 'bb-saxes'},
                    {name: 'Trumpet Section', prefix: 'bb-trumpets'},
                    {name: 'Trombone Section', prefix: 'bb-trombones'},
                    {name: 'Rhythm Section', prefix: 'bb-rhythm'}
                ],
                instruments: [
                    {family: 'bb-saxes', name: 'Alto Saxophone 1', order: 1},
                    {family: 'bb-saxes', name: 'Alto Saxophone 2', order: 2},
                    {family: 'bb-saxes', name: 'Tenor Saxophone 1', order: 3},
                    {family: 'bb-saxes', name: 'Tenor Saxophone 2', order: 4},
                    {family: 'bb-saxes', name: 'Baritone Saxophone', order: 5},
                    {family: 'bb-trumpets', name: 'Trumpet 1', order: 1},
                    {family: 'bb-trumpets', name: 'Trumpet 2', order: 2},
                    {family: 'bb-trumpets', name: 'Trumpet 3', order: 3},
                    {family: 'bb-trumpets', name: 'Trumpet 4', order: 4},
                    {family: 'bb-trombones', name: 'Trombone 1', order: 1},
                    {family: 'bb-trombones', name: 'Trombone 2', order: 2},
                    {family: 'bb-trombones', name: 'Trombone 3', order: 3},
                    {family: 'bb-trombones', name: 'Bass Trombone', order: 4},
                    {family: 'bb-rhythm', name: 'Piano', order: 1},
                    {family: 'bb-rhythm', name: 'Guitar', order: 2},
                    {family: 'bb-rhythm', name: 'Bass', order: 3},
                    {family: 'bb-rhythm', name: 'Drums', order: 4}
                ]
            },
            classical: {
                families: [
                    {name: 'Woodwind Section', prefix: 'cl-woodwinds'},
                    {name: 'Brass Section', prefix: 'cl-brass'},
                    {name: 'Percussion Section', prefix: 'cl-percussion'},
                    {name: 'String Section', prefix: 'cl-strings'}
                ],
                instruments: [
                    {family: 'cl-woodwinds', name: 'Flute', order: 1},
                    {family: 'cl-woodwinds', name: 'Oboe', order: 2},
                    {family: 'cl-woodwinds', name: 'Clarinet', order: 3},
                    {family: 'cl-woodwinds', name: 'Bassoon', order: 4},
                    {family: 'cl-woodwinds', name: 'Alto Flute', order: 5},
                    {family: 'cl-woodwinds', name: 'English Horn', order: 6},
                    {family: 'cl-woodwinds', name: 'Bass Clarinet', order: 7},
                    {family: 'cl-woodwinds', name: 'Contrabassoon', order: 8},
                    {family: 'cl-brass', name: 'French Horn', order: 1},
                    {family: 'cl-brass', name: 'Trumpet', order: 2},
                    {family: 'cl-brass', name: 'Trombone', order: 3},
                    {family: 'cl-brass', name: 'Tuba', order: 4},
                    {family: 'cl-percussion', name: 'Timpani', order: 1},
                    {family: 'cl-percussion', name: 'Snare Drum', order: 2},
                    {family: 'cl-percussion', name: 'Bass Drum', order: 3},
                    {family: 'cl-percussion', name: 'Cymbals', order: 4},
                    {family: 'cl-percussion', name: 'Xylophone', order: 5},
                    {family: 'cl-percussion', name: 'Glockenspiel', order: 6},
                    {family: 'cl-strings', name: 'Violin 1', order: 1},
                    {family: 'cl-strings', name: 'Violin 2', order: 2},
                    {family: 'cl-strings', name: 'Viola', order: 3},
                    {family: 'cl-strings', name: 'Cello', order: 4},
                    {family: 'cl-strings', name: 'String Bass', order: 5}
                ]
            },
            concert: {
                families: [
                    {name: 'Woodwind Section', prefix: 'cb-woodwinds'},
                    {name: 'Brass Section', prefix: 'cb-brass'},
                    {name: 'Percussion', prefix: 'cb-percussion'}
                ],
                instruments: [
                    {family: 'cb-woodwinds', name: 'Flute 1', order: 1},
                    {family: 'cb-woodwinds', name: 'Flute 2', order: 2},
                    {family: 'cb-woodwinds', name: 'Clarinet 1', order: 3},
                    {family: 'cb-woodwinds', name: 'Clarinet 2', order: 4},
                    {family: 'cb-woodwinds', name: 'Clarinet 3', order: 5},
                    {family: 'cb-woodwinds', name: 'Alto Clarinet', order: 6},
                    {family: 'cb-woodwinds', name: 'Bass Clarinet', order: 7},
                    {family: 'cb-woodwinds', name: 'Oboe', order: 8},
                    {family: 'cb-woodwinds', name: 'Bassoon', order: 9},
                    {family: 'cb-woodwinds', name: 'Alto Saxophone', order: 10},
                    {family: 'cb-woodwinds', name: 'Tenor Saxophone', order: 11},
                    {family: 'cb-woodwinds', name: 'Baritone Saxophone', order: 12},
                    {family: 'cb-brass', name: 'French Horn 1', order: 1},
                    {family: 'cb-brass', name: 'French Horn 2', order: 2},
                    {family: 'cb-brass', name: 'French Horn 3', order: 3},
                    {family: 'cb-brass', name: 'French Horn 4', order: 4},
                    {family: 'cb-brass', name: 'Trumpet 1', order: 5},
                    {family: 'cb-brass', name: 'Trumpet 2', order: 6},
                    {family: 'cb-brass', name: 'Trumpet 3', order: 7},
                    {family: 'cb-brass', name: 'Trombone 1', order: 8},
                    {family: 'cb-brass', name: 'Trombone 2', order: 9},
                    {family: 'cb-brass', name: 'Trombone 3', order: 10},
                    {family: 'cb-brass', name: 'Baritone', order: 11},
                    {family: 'cb-brass', name: 'Tuba', order: 12},
                    {family: 'cb-percussion', name: 'Percussion 1', order: 1},
                    {family: 'cb-percussion', name: 'Percussion 2', order: 2},
                    {family: 'cb-percussion', name: 'Timpani', order: 3}
                ]
            },
            small: {
                families: [
                    {name: 'Rhythm', prefix: 'se-rhythm'},
                    {name: 'Melody', prefix: 'se-melody'}
                ],
                instruments: [
                    {family: 'se-rhythm', name: 'Piano', order: 1},
                    {family: 'se-rhythm', name: 'Guitar', order: 2},
                    {family: 'se-rhythm', name: 'Bass', order: 3},
                    {family: 'se-rhythm', name: 'Drums', order: 4},
                    {family: 'se-melody', name: 'Saxophone', order: 1},
                    {family: 'se-melody', name: 'Trumpet', order: 2},
                    {family: 'se-melody', name: 'Trombone', order: 3},
                    {family: 'se-melody', name: 'Clarinet', order: 4},
                    {family: 'se-melody', name: 'Flute', order: 5},
                    {family: 'se-melody', name: 'Violin', order: 6},
                    {family: 'se-melody', name: 'Voice', order: 7}
                ]
            }
        };

        $('input[name="preseedType"]').on('change', showPreseedPreview);
        $('#preseedModal').on('shown.bs.modal', showPreseedPreview);

        function showPreseedPreview() {
            var type = $('input[name="preseedType"]:checked').val();
            var data = preseedData[type];
            var html = '';
            data.families.forEach(function(f) {
                html += '<div class="fw-semibold mt-2">' + f.name + '</div>';
                var famInstruments = data.instruments.filter(function(i) { return i.family === f.prefix; });
                famInstruments.forEach(function(i) {
                    html += '<div class="ps-3 small">- ' + i.name + '</div>';
                });
            });
            if (html === '') html = '<small class="text-muted">Select a band type to see preview</small>';
            $('#preseedPreview').html(html);
        }

        $('#addPreseedBtn').on('click', function() {
            var type = $('input[name="preseedType"]:checked').val();
            var data = preseedData[type];
            var btn = $(this);
            btn.prop('disabled', true).text('Adding...');

            var processed = 0;
            var total = data.families.length;

            if (total === 0) {
                addInstrument();
                return;
            }

            data.families.forEach(function(f) {
                $.ajax({
                    type: 'POST',
                    url: 'lib/action.php',
                    data: {action: 'createInstrumentFamily', instrumentFamilyName: f.name, familyId: f.prefix},
                    dataType: 'JSON',
                    success: function() {},
                    error: function() {},
                    complete: function() {
                        processed++;
                        if (processed >= total) {
                            setTimeout(addInstrument, 300);
                        }
                    }
                });
            });

            function addInstrument() {
                var instProcessed = 0;
                var instTotal = data.instruments.length;
                if (instTotal === 0) {
                    finish();
                    return;
                }
                data.instruments.forEach(function(i) {
                    $.ajax({
                        type: 'POST',
                        url: 'lib/action.php',
                        data: {action: 'createInstrument', instrumentName: i.name, idInstrumentFamily: i.family, instrumentId: null, sortOrder: i.order || null},
                        dataType: 'JSON',
                        complete: function() {
                            instProcessed++;
                            if (instProcessed >= instTotal) {
                                finish();
                            }
                        }
                    });
                });
            }

            function finish() {
                btn.prop('disabled', false).text('Add Instruments');
                bootstrap.Modal.getInstance(document.getElementById('preseedModal')).hide();
                fetchData();
                toastr.success('Instruments preseeded successfully.');
            }
        });
    </script>
</html>
