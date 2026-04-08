<?php
require_once __DIR__ . '/../../lib/util_all.php';
$pageName = "Arrangers";
if (!in_array('arrangers.view', $_SESSION['user']['permissions'])) {
    header("Location: /");
    exit;
}
$canCreate = in_array('arrangers.create', $_SESSION['user']['permissions']);
$canEdit   = in_array('arrangers.edit',   $_SESSION['user']['permissions']);
$canDelete = in_array('arrangers.delete', $_SESSION['user']['permissions']);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title><?php echo $pageName; ?></title>
        <?php require_once __DIR__ . '/../../lib/html_header/all.php'; ?>
    </head>
    <body>
        <?php require_once __DIR__ . '/../../lib/navbar.php'; ?>
        <div class="container-fluid mt-4">
            <div class="row">
                <div class="col-8 mx-auto">
                    <h1>Arrangers</h1>

                    <?php if ($canCreate): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createArrangerModal">Create</button>
                    <?php endif; ?>

                    <table id="arrangerTable" class="table table-striped table-bordered table-sm mt-3"></table>
                </div>
            </div>
        </div>
        <?php require_once __DIR__ . '/../../lib/html_footer/all.php'; ?>
    </body>

    <!-- Create Arranger Modal -->
    <div class="modal fade" id="createArrangerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Create Arranger</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="arrangerName" class="form-label">Arranger Name</label>
                        <input type="text" class="form-control" id="arrangerName" placeholder="e.g. John Wasson">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveArrangerBtn">Save Arranger</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Arranger Modal -->
    <div class="modal fade" id="editArrangerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Edit Arranger</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editArrangerId">
                    <div class="mb-3">
                        <label for="editArrangerName" class="form-label">Arranger Name</label>
                        <input type="text" class="form-control" id="editArrangerName">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="updateArrangerBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Arranger Confirmation Modal -->
    <div class="modal fade" id="deleteArrangerModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Delete Arranger</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <strong id="deleteArrangerName"></strong>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteArrangerBtn"><i class="bi bi-trash"></i> Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const canEdit   = <?= json_encode($canEdit) ?>;
        const canDelete = <?= json_encode($canDelete) ?>;

        $(function () { fetchData(); });

        let table = null;
        let currentEditRow = null;
        let deleteRow = null;

        function ajaxErrorHandler(jqXHR) {
            toastr.error(jqXHR.responseJSON?.message || "An unexpected error occurred.");
        }

        const columns = [
            {data: 'arrangerName', title: 'Arranger Name'},
        ];
        const columnDefs = [
            {targets: 0, orderable: true, searchable: true},
        ];

        if (canEdit || canDelete) {
            columns.push({data: null, defaultContent: ''});
            columnDefs.push({
                targets: 1,
                orderable: false,
                searchable: false,
                title: '',
                render: function () {
                    let html = '';
                    if (canEdit)   html += '<button class="btn btn-sm btn-outline-secondary edit-arranger-btn me-1"><i class="bi bi-pencil"></i> Edit</button>';
                    if (canDelete) html += '<button class="btn btn-sm btn-outline-danger delete-arranger-btn"><i class="bi bi-trash"></i> Delete</button>';
                    return html;
                }
            });
        }

        function fetchData() {
            if (table) {
                table.ajax.reload();
            } else {
                table = $('#arrangerTable').DataTable({
                    processing: true,
                    serverSide: false,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    pageLength: -1,
                    ajax: {
                        url: 'lib/action.php',
                        type: 'POST',
                        data: function (d) { d.action = "getArrangers"; },
                        error: ajaxErrorHandler
                    },
                    columns: columns,
                    columnDefs: columnDefs,
                    createdRow: function (row, data) {
                        $(row).attr('data-id', data.idArranger);
                    },
                    responsive: true,
                });
            }
        }

        $('#arrangerTable').on('click', '.edit-arranger-btn', function () {
            currentEditRow = table.row($(this).closest('tr')).data();
            $('#editArrangerId').val(currentEditRow.idArranger);
            $('#editArrangerName').val(currentEditRow.arrangerName);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editArrangerModal')).show();
        });

        $('#arrangerTable').on('click', '.delete-arranger-btn', function () {
            deleteRow = table.row($(this).closest('tr')).data();
            $('#deleteArrangerName').text(deleteRow.arrangerName);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteArrangerModal')).show();
        });

        $('#deleteArrangerModal').on('hidden.bs.modal', function () { deleteRow = null; });

        $('#confirmDeleteArrangerBtn').on('click', function () {
            if (!deleteRow) return;
            $.ajax({
                type: 'POST', url: 'lib/action.php',
                data: {action: 'deleteArranger', idArranger: deleteRow.idArranger},
                dataType: 'JSON',
                success: function () {
                    bootstrap.Modal.getInstance(document.getElementById('deleteArrangerModal')).hide();
                    fetchData();
                    toastr.success('Arranger deleted successfully.');
                },
                error: ajaxErrorHandler
            });
        });

        $('#updateArrangerBtn').on('click', function () {
            const idArranger   = $('#editArrangerId').val();
            const arrangerName = $('#editArrangerName').val().trim();
            if (!arrangerName) { toastr.error('Arranger name is required.'); return; }
            $.ajax({
                type: 'POST', url: 'lib/action.php',
                data: {action: 'updateArranger', idArranger, arrangerName},
                dataType: 'JSON',
                success: function () {
                    bootstrap.Modal.getInstance(document.getElementById('editArrangerModal')).hide();
                    fetchData();
                    toastr.success('Arranger updated successfully.');
                },
                error: ajaxErrorHandler
            });
        });

        $('#createArrangerModal').on('shown.bs.modal', function () { $('#arrangerName').val('').focus(); });

        $('#saveArrangerBtn').on('click', function () {
            const arrangerName = $('#arrangerName').val().trim();
            if (!arrangerName) { toastr.error('Arranger name is required.'); return; }
            $.ajax({
                type: 'POST', url: 'lib/action.php',
                data: {action: 'createArranger', arrangerName},
                dataType: 'JSON',
                success: function () {
                    bootstrap.Modal.getInstance(document.getElementById('createArrangerModal')).hide();
                    fetchData();
                    toastr.success('Arranger created successfully.');
                },
                error: ajaxErrorHandler
            });
        });
    </script>
</html>
