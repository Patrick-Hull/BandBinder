<?php
require_once __DIR__ . '/../../lib/util_all.php';
$pageName = "Setlists";
if (!in_array('setlists.view', $_SESSION['user']['permissions'])) {
    header("Location: /");
    exit;
}
$canCreate = in_array('setlists.create', $_SESSION['user']['permissions']);
$canEdit   = in_array('setlists.edit',   $_SESSION['user']['permissions']);
$canDelete = in_array('setlists.delete', $_SESSION['user']['permissions']);
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
                <div class="col-10 mx-auto">
                    <h1>Setlists</h1>

                    <?php if ($canCreate): ?>
                        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createSetlistModal">
                            <i class="bi bi-plus-lg"></i> Create Setlist
                        </button>
                    <?php endif; ?>

                    <table id="setlistTable" class="table table-striped table-bordered table-sm"></table>
                </div>
            </div>
        </div>
        <?php require_once __DIR__ . '/../../lib/html_footer/all.php'; ?>
    </body>

    <!-- ═══════════════════════════════════════════════════════════
         CREATE SETLIST MODAL
    ════════════════════════════════════════════════════════════════ -->
    <?php if ($canCreate): ?>
    <div class="modal fade" id="createSetlistModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Create Setlist</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="createSetlistName" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="createSetlistName" placeholder="e.g. Saturday Night Gig">
                    </div>
                    <div class="mb-3">
                        <label for="createSetlistDate" class="form-label">Date Performed</label>
                        <input type="date" class="form-control" id="createSetlistDate">
                    </div>
                    <div class="mb-3">
                        <label for="createSetlistNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="createSetlistNotes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveCreateSetlistBtn">Create</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════
         EDIT SETLIST MODAL (metadata only — layout via edit page)
    ════════════════════════════════════════════════════════════════ -->
    <?php if ($canEdit): ?>
    <div class="modal fade" id="editSetlistModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Edit Setlist</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editSetlistId">
                    <div class="mb-3">
                        <label for="editSetlistName" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editSetlistName">
                    </div>
                    <div class="mb-3">
                        <label for="editSetlistDate" class="form-label">Date Performed</label>
                        <input type="date" class="form-control" id="editSetlistDate">
                    </div>
                    <div class="mb-3">
                        <label for="editSetlistNotes" class="form-label">Notes</label>
                        <textarea class="form-control" id="editSetlistNotes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveEditSetlistBtn">Save</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- ═══════════════════════════════════════════════════════════
         DELETE SETLIST MODAL
    ════════════════════════════════════════════════════════════════ -->
    <?php if ($canDelete): ?>
    <div class="modal fade" id="deleteSetlistModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Delete Setlist</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="deleteSetlistId">
                    <p>Are you sure you want to delete <strong id="deleteSetlistNameDisplay"></strong>? This cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteSetlistBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script>
    const CAN_EDIT   = <?php echo $canEdit   ? 'true' : 'false'; ?>;
    const CAN_DELETE = <?php echo $canDelete ? 'true' : 'false'; ?>;

    let setlistTable;

    function ajaxErrorHandler(xhr) {
        try {
            const r = JSON.parse(xhr.responseText);
            toastr.error(r.message || r.error || 'An error occurred.');
        } catch(_) {
            toastr.error('An error occurred.');
        }
    }

    function escHtml(s) {
        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    // ── Load table ────────────────────────────────────────────────
    $(document).ready(function () {
        setlistTable = $('#setlistTable').DataTable({
            ajax: {
                url: 'lib/action.php',
                type: 'POST',
                data: {action: 'getSetlists'},
                dataSrc: 'data'
            },
            columns: [
                {title: 'Name',       data: 'setlistName'},
                {title: 'Date',       data: 'performedAt', defaultContent: '—'},
                {title: 'Sets',       data: 'setCount',    className: 'text-center'},
                {title: 'Charts',     data: 'chartCount',  className: 'text-center'},
                {title: 'Duration',   data: 'totalDuration', className: 'text-center'},
                {title: 'Actions',    data: null, orderable: false, className: 'text-center', render: function(d, t, row) {
                    let html = '';
                    if (CAN_EDIT) {
                        html += `<a href="/setlists/edit/?id=${escHtml(row.idSetlist)}" class="btn btn-sm btn-outline-primary me-1"><i class="bi bi-music-note-list"></i> Edit Layout</a>`;
                        html += `<button class="btn btn-sm btn-outline-secondary me-1 edit-meta-btn" data-id="${escHtml(row.idSetlist)}"><i class="bi bi-pencil"></i></button>`;
                    }
                    html += `<a href="/setlists/lib/action.php?action=generateSetlistPdf&idSetlist=${escHtml(row.idSetlist)}" target="_blank" class="btn btn-sm btn-outline-success me-1" title="Download PDF"><i class="bi bi-file-earmark-pdf"></i></a>`;
                    if (CAN_DELETE) {
                        html += `<button class="btn btn-sm btn-outline-danger delete-btn" data-id="${escHtml(row.idSetlist)}" data-name="${escHtml(row.setlistName)}"><i class="bi bi-trash"></i></button>`;
                    }
                    return html;
                }}
            ],
            order: [[1, 'desc']]
        });
    });

    // ── Create ────────────────────────────────────────────────────
    $('#saveCreateSetlistBtn').on('click', function () {
        const name = $('#createSetlistName').val().trim();
        if (!name) { toastr.warning('Name is required.'); return; }
        $.ajax({
            type: 'POST', url: 'lib/action.php',
            data: {
                action:      'createSetlist',
                setlistName: name,
                performedAt: $('#createSetlistDate').val() || '',
                notes:       $('#createSetlistNotes').val()
            },
            dataType: 'JSON',
            success: function (r) {
                bootstrap.Modal.getInstance(document.getElementById('createSetlistModal')).hide();
                $('#createSetlistName').val('');
                $('#createSetlistDate').val('');
                $('#createSetlistNotes').val('');
                setlistTable.ajax.reload();
                toastr.success('Setlist created.');
                // Go straight to editor
                window.location.href = '/setlists/edit/?id=' + r.id;
            },
            error: ajaxErrorHandler
        });
    });

    // ── Edit metadata ─────────────────────────────────────────────
    $(document).on('click', '.edit-meta-btn', function () {
        const id = $(this).data('id');
        const row = setlistTable.rows().data().toArray().find(r => r.idSetlist === id);
        if (!row) return;
        $('#editSetlistId').val(row.idSetlist);
        $('#editSetlistName').val(row.setlistName);
        $('#editSetlistDate').val(row.performedAt || '');
        $('#editSetlistNotes').val(row.notes || '');
        bootstrap.Modal.getOrCreateInstance(document.getElementById('editSetlistModal')).show();
    });

    $('#saveEditSetlistBtn').on('click', function () {
        const name = $('#editSetlistName').val().trim();
        if (!name) { toastr.warning('Name is required.'); return; }
        $.ajax({
            type: 'POST', url: 'lib/action.php',
            data: {
                action:      'updateSetlist',
                idSetlist:   $('#editSetlistId').val(),
                setlistName: name,
                performedAt: $('#editSetlistDate').val() || '',
                notes:       $('#editSetlistNotes').val()
            },
            dataType: 'JSON',
            success: function () {
                bootstrap.Modal.getInstance(document.getElementById('editSetlistModal')).hide();
                setlistTable.ajax.reload();
                toastr.success('Setlist updated.');
            },
            error: ajaxErrorHandler
        });
    });

    // ── Delete ────────────────────────────────────────────────────
    $(document).on('click', '.delete-btn', function () {
        $('#deleteSetlistId').val($(this).data('id'));
        $('#deleteSetlistNameDisplay').text($(this).data('name'));
        bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteSetlistModal')).show();
    });

    $('#confirmDeleteSetlistBtn').on('click', function () {
        $.ajax({
            type: 'POST', url: 'lib/action.php',
            data: {action: 'deleteSetlist', idSetlist: $('#deleteSetlistId').val()},
            dataType: 'JSON',
            success: function () {
                bootstrap.Modal.getInstance(document.getElementById('deleteSetlistModal')).hide();
                setlistTable.ajax.reload();
                toastr.success('Setlist deleted.');
            },
            error: ajaxErrorHandler
        });
    });
    </script>
</html>
