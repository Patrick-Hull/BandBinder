<?php
require_once __DIR__ . '/../../lib/util_all.php';
$pageName = "Categories";
$canCreate = in_array('categories.create', $_SESSION['user']['permissions']);
$canEdit   = in_array('categories.edit',   $_SESSION['user']['permissions']);
$canDelete = in_array('categories.delete', $_SESSION['user']['permissions']);
?>
<!DOCTYPE html>
<html lang="en">
    <head>
        <title><?php echo $pageName; ?></title>
        <?php require_once __DIR__ . '/../../lib/html_header/all.php'; ?>
        <style>
            .colour-swatch {
                display: inline-block;
                width: 24px;
                height: 24px;
                border-radius: 4px;
                border: 1px solid #dee2e6;
                vertical-align: middle;
            }
            .colour-swatch.no-colour {
                background: linear-gradient(135deg, #fff 45%, #dee2e6 45%, #dee2e6 55%, #fff 55%);
            }
        </style>
    </head>
    <body>
        <?php require_once __DIR__ . '/../../lib/navbar.php'; ?>
        <div class="container-fluid mt-4">
            <div class="row">
                <div class="col-8 mx-auto">
                    <h1>Categories</h1>

                    <?php if ($canCreate): ?>
                        <button type="button" class="btn btn-primary mb-3" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                            <i class="bi bi-plus-lg"></i> Create Category
                        </button>
                    <?php endif; ?>

                    <table id="categoryTable" class="table table-striped table-bordered table-sm"></table>
                </div>
            </div>
        </div>
        <?php require_once __DIR__ . '/../../lib/html_footer/all.php'; ?>
    </body>

    <!-- Create Category Modal -->
    <div class="modal fade" id="createCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Create Category</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="categoryName" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="categoryName" placeholder="e.g. Jazz, Ballad, Opener">
                    </div>
                    <div class="mb-3">
                        <label for="categoryColour" class="form-label">Colour</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="categoryColour" value="#6c757d" style="width: 60px;">
                            <input type="text" class="form-control" id="categoryColourHex" placeholder="#6c757d">
                        </div>
                        <div class="form-text">Optional. Pick a colour to display on charts and setlists.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveCategoryBtn">Save Category</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Category Modal -->
    <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Edit Category</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editCategoryId">
                    <div class="mb-3">
                        <label for="editCategoryName" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editCategoryName">
                    </div>
                    <div class="mb-3">
                        <label for="editCategoryColour" class="form-label">Colour</label>
                        <div class="input-group">
                            <input type="color" class="form-control form-control-color" id="editCategoryColour" value="#6c757d" style="width: 60px;">
                            <input type="text" class="form-control" id="editCategoryColourHex">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="updateCategoryBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Category Modal -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5">Delete Category</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <strong id="deleteCategoryName"></strong>?
                    <div class="text-muted small mt-1">This will remove the category from all charts.</div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteCategoryBtn"><i class="bi bi-trash"></i> Delete</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const canCreate = <?= json_encode($canCreate) ?>;
        const canEdit   = <?= json_encode($canEdit) ?>;
        const canDelete = <?= json_encode($canDelete) ?>;

        $(function() { fetchData(); });

        let table = null;
        let currentEditRow = null;
        let deleteRow = null;

        function ajaxErrorHandler(jqXHR) {
            toastr.error(jqXHR.responseJSON?.message || "An unexpected error occurred.");
        }

        function colourSwatchHtml(colour) {
            if (!colour) {
                return '<span class="colour-swatch no-colour" title="No colour"></span>';
            }
            return '<span class="colour-swatch" style="background-color:' + colour + ';" title="' + colour + '"></span>';
        }

        function syncColourInputs(colorInputId, hexInputId) {
            const colorInput = document.getElementById(colorInputId);
            const hexInput = document.getElementById(hexInputId);

            colorInput.addEventListener('input', function() {
                hexInput.value = this.value;
            });
            hexInput.addEventListener('input', function() {
                const val = this.value.trim();
                if (/^#[0-9A-Fa-f]{6}$/.test(val)) {
                    colorInput.value = val;
                }
            });
        }

        // Initialize colour input sync
        syncColourInputs('categoryColour', 'categoryColourHex');
        syncColourInputs('editCategoryColour', 'editCategoryColourHex');

        // DataTable
        function fetchData() {
            if (table) {
                table.ajax.reload();
            } else {
                const columns = [
                    { data: 'categoryColour', title: 'Colour', render: function(d) { return colourSwatchHtml(d); }, orderable: false, searchable: false, className: 'text-center' },
                    { data: 'categoryName', title: 'Name' },
                ];
                const columnDefs = [
                    { targets: 0, width: '60px' },
                    { targets: 1, orderable: true, searchable: true },
                ];

                if (canEdit || canDelete) {
                    columns.push({ data: null, defaultContent: '' });
                    columnDefs.push({
                        targets: 2,
                        orderable: false,
                        searchable: false,
                        title: '',
                        render: function(data, type, row) {
                            let html = '';
                            if (canEdit) {
                                html += '<button class="btn btn-sm btn-outline-secondary edit-category-btn me-1"><i class="bi bi-pencil"></i> Edit</button>';
                            }
                            if (canDelete) {
                                html += '<button class="btn btn-sm btn-outline-danger delete-category-btn"><i class="bi bi-trash"></i> Delete</button>';
                            }
                            return html;
                        }
                    });
                }

                table = $('#categoryTable').DataTable({
                    processing: true,
                    serverSide: false,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    pageLength: 25,
                    ajax: {
                        url: 'lib/action.php',
                        type: 'POST',
                        data: function(d) { d.action = "getCategories"; },
                        error: ajaxErrorHandler
                    },
                    columns: columns,
                    columnDefs: columnDefs,
                    createdRow: function(row, data) {
                        $(row).attr('data-id', data.idCategory);
                    },
                    responsive: true,
                });
            }
        }

        // Create Modal
        $('#createCategoryModal').on('shown.bs.modal', function() {
            $('#categoryName').val('');
            $('#categoryColour').val('#6c757d');
            $('#categoryColourHex').val('#6c757d');
        });

        $('#saveCategoryBtn').on('click', function() {
            const categoryName = $('#categoryName').val().trim();
            if (!categoryName) {
                toastr.error('Category name is required.');
                return;
            }
            const categoryColour = $('#categoryColourHex').val().trim() || null;

            $.ajax({
                type: 'POST',
                url: 'lib/action.php',
                data: {
                    action: 'createCategory',
                    categoryName: categoryName,
                    categoryColour: categoryColour
                },
                dataType: 'JSON',
                success: function() {
                    bootstrap.Modal.getInstance(document.getElementById('createCategoryModal')).hide();
                    fetchData();
                    toastr.success('Category created successfully.');
                },
                error: ajaxErrorHandler
            });
        });

        // Edit Modal
        $('#categoryTable').on('click', '.edit-category-btn', function() {
            currentEditRow = table.row($(this).closest('tr')).data();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editCategoryModal')).show();
        });

        $('#editCategoryModal').on('shown.bs.modal', function() {
            if (!currentEditRow) return;
            $('#editCategoryId').val(currentEditRow.idCategory);
            $('#editCategoryName').val(currentEditRow.categoryName);
            const colour = currentEditRow.categoryColour || '#6c757d';
            $('#editCategoryColour').val(colour);
            $('#editCategoryColourHex').val(colour);
        });

        $('#editCategoryModal').on('hidden.bs.modal', function() {
            currentEditRow = null;
        });

        $('#updateCategoryBtn').on('click', function() {
            const categoryName = $('#editCategoryName').val().trim();
            if (!categoryName) {
                toastr.error('Category name is required.');
                return;
            }
            const categoryColour = $('#editCategoryColourHex').val().trim() || null;

            $.ajax({
                type: 'POST',
                url: 'lib/action.php',
                data: {
                    action: 'updateCategory',
                    idCategory: $('#editCategoryId').val(),
                    categoryName: categoryName,
                    categoryColour: categoryColour
                },
                dataType: 'JSON',
                success: function() {
                    bootstrap.Modal.getInstance(document.getElementById('editCategoryModal')).hide();
                    fetchData();
                    toastr.success('Category updated successfully.');
                },
                error: ajaxErrorHandler
            });
        });

        // Delete Modal
        $('#categoryTable').on('click', '.delete-category-btn', function() {
            deleteRow = table.row($(this).closest('tr')).data();
            $('#deleteCategoryName').text(deleteRow.categoryName);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteCategoryModal')).show();
        });

        $('#deleteCategoryModal').on('hidden.bs.modal', function() {
            deleteRow = null;
        });

        $('#confirmDeleteCategoryBtn').on('click', function() {
            if (!deleteRow) return;
            $.ajax({
                type: 'POST',
                url: 'lib/action.php',
                data: { action: 'deleteCategory', idCategory: deleteRow.idCategory },
                dataType: 'JSON',
                success: function() {
                    bootstrap.Modal.getInstance(document.getElementById('deleteCategoryModal')).hide();
                    fetchData();
                    toastr.success('Category deleted successfully.');
                },
                error: ajaxErrorHandler
            });
        });
    </script>
</html>