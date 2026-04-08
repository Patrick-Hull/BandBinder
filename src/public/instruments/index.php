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
    </script>
</html>
