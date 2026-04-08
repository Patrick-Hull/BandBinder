<?php
require_once __DIR__ . '/../../lib/util_all.php';
$pageName = "Artists";
if (!in_array('artists.view', $_SESSION['user']['permissions'])) {
    header("Location: /");
    exit;
}
$canCreate = in_array('artists.create', $_SESSION['user']['permissions']);
$canEdit   = in_array('artists.edit',   $_SESSION['user']['permissions']);
$canDelete = in_array('artists.delete', $_SESSION['user']['permissions']);
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
                    <h1>Artists</h1>

                    <?php if ($canCreate): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createArtistModal">Create</button>
                    <?php endif; ?>

                    <table id="artistTable" class="table table-striped table-bordered table-sm mt-3"></table>
                </div>
            </div>
        </div>
        <?php require_once __DIR__ . '/../../lib/html_footer/all.php'; ?>
    </body>

    <!-- Create Artist Modal -->
    <div class="modal fade" id="createArtistModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Create Artist</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="artistName" class="form-label">Artist Name</label>
                        <input type="text" class="form-control" id="artistName" placeholder="e.g. Miles Davis">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveArtistBtn">Save Artist</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Artist Modal -->
    <div class="modal fade" id="editArtistModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Edit Artist</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editArtistId">
                    <div class="mb-3">
                        <label for="editArtistName" class="form-label">Artist Name</label>
                        <input type="text" class="form-control" id="editArtistName">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="updateArtistBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Artist Confirmation Modal -->
    <div class="modal fade" id="deleteArtistModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Delete Artist</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <strong id="deleteArtistName"></strong>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteArtistBtn"><i class="bi bi-trash"></i> Delete</button>
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
            {data: 'artistName', title: 'Artist Name'},
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
                    if (canEdit)   html += '<button class="btn btn-sm btn-outline-secondary edit-artist-btn me-1"><i class="bi bi-pencil"></i> Edit</button>';
                    if (canDelete) html += '<button class="btn btn-sm btn-outline-danger delete-artist-btn"><i class="bi bi-trash"></i> Delete</button>';
                    return html;
                }
            });
        }

        function fetchData() {
            if (table) {
                table.ajax.reload();
            } else {
                table = $('#artistTable').DataTable({
                    processing: true,
                    serverSide: false,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    pageLength: -1,
                    ajax: {
                        url: 'lib/action.php',
                        type: 'POST',
                        data: function (d) { d.action = "getArtists"; },
                        error: ajaxErrorHandler
                    },
                    columns: columns,
                    columnDefs: columnDefs,
                    createdRow: function (row, data) {
                        $(row).attr('data-id', data.idArtist);
                    },
                    responsive: true,
                });
            }
        }

        $('#artistTable').on('click', '.edit-artist-btn', function () {
            currentEditRow = table.row($(this).closest('tr')).data();
            $('#editArtistId').val(currentEditRow.idArtist);
            $('#editArtistName').val(currentEditRow.artistName);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editArtistModal')).show();
        });

        $('#artistTable').on('click', '.delete-artist-btn', function () {
            deleteRow = table.row($(this).closest('tr')).data();
            $('#deleteArtistName').text(deleteRow.artistName);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteArtistModal')).show();
        });

        $('#deleteArtistModal').on('hidden.bs.modal', function () { deleteRow = null; });

        $('#confirmDeleteArtistBtn').on('click', function () {
            if (!deleteRow) return;
            $.ajax({
                type: 'POST', url: 'lib/action.php',
                data: {action: 'deleteArtist', idArtist: deleteRow.idArtist},
                dataType: 'JSON',
                success: function () {
                    bootstrap.Modal.getInstance(document.getElementById('deleteArtistModal')).hide();
                    fetchData();
                    toastr.success('Artist deleted successfully.');
                },
                error: ajaxErrorHandler
            });
        });

        $('#updateArtistBtn').on('click', function () {
            const idArtist   = $('#editArtistId').val();
            const artistName = $('#editArtistName').val().trim();
            if (!artistName) { toastr.error('Artist name is required.'); return; }
            $.ajax({
                type: 'POST', url: 'lib/action.php',
                data: {action: 'updateArtist', idArtist, artistName},
                dataType: 'JSON',
                success: function () {
                    bootstrap.Modal.getInstance(document.getElementById('editArtistModal')).hide();
                    fetchData();
                    toastr.success('Artist updated successfully.');
                },
                error: ajaxErrorHandler
            });
        });

        $('#createArtistModal').on('shown.bs.modal', function () { $('#artistName').val('').focus(); });

        $('#saveArtistBtn').on('click', function () {
            const artistName = $('#artistName').val().trim();
            if (!artistName) { toastr.error('Artist name is required.'); return; }
            $.ajax({
                type: 'POST', url: 'lib/action.php',
                data: {action: 'createArtist', artistName},
                dataType: 'JSON',
                success: function () {
                    bootstrap.Modal.getInstance(document.getElementById('createArtistModal')).hide();
                    fetchData();
                    toastr.success('Artist created successfully.');
                },
                error: ajaxErrorHandler
            });
        });
    </script>
</html>
