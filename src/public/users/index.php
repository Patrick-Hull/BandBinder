<?php
require_once __DIR__ . '/../../lib/util_all.php';
$pageName = "Users";
$canCreateUser      = in_array('users.create', $_SESSION['user']['permissions']);
$canEditUser        = in_array('users.edit',   $_SESSION['user']['permissions']);
$canDeleteUser      = in_array('users.delete', $_SESSION['user']['permissions']);
$canEditPermissions = in_array('users.editPermissions', $_SESSION['user']['permissions']);
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <title><?php echo $pageName; ?></title>
        <?php require_once __DIR__ . '/../../lib/html_header/all.php'; ?>
        <?php require_once __DIR__ . '/../../lib/html_header/tomselect.php'; ?>
    </head>
    <body>
        <?php require_once __DIR__ . '/../../lib/navbar.php'; ?>
        <div class="container-fluid mt-4">
            <div class="row">
                <div class="col-10 mx-auto">

                    <ul class="nav nav-tabs" id="userPageTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="users-tab" data-bs-toggle="tab" data-bs-target="#usersTab" type="button" role="tab">Users</button>
                        </li>
                        <?php if($canEditPermissions): ?>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="user-types-tab" data-bs-toggle="tab" data-bs-target="#userTypesTab" type="button" role="tab">User Types</button>
                        </li>
                        <?php endif; ?>
                    </ul>

                    <div class="tab-content mt-3">
                        <!-- Users Tab -->
                        <div class="tab-pane fade show active" id="usersTab" role="tabpanel">
                            <h1>Users</h1>

                            <?php if($canCreateUser): ?>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserModal">Create User</button>
                            <?php endif; ?>

                            <table id="userTable" class="table table-striped table-bordered table-sm mt-3"></table>
                        </div>

                        <!-- User Types Tab -->
                        <?php if($canEditPermissions): ?>
                        <div class="tab-pane fade" id="userTypesTab" role="tabpanel">
                            <h1>User Types</h1>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createUserTypeModal">Create User Type</button>
                            <table id="userTypeTable" class="table table-striped table-bordered table-sm mt-3"></table>
                        </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>
        <?php require_once __DIR__ . '/../../lib/html_footer/all.php'; ?>
    </body>


    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- Create User Modal                                              -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="createUserModalLabel">Create User</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="createUsername" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="createUsername" placeholder="e.g. jdoe">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="createEmail" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="createEmail" placeholder="e.g. jdoe@example.com">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="createPassword" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="createPassword" autocomplete="new-password">
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="createNameShort" class="form-label">Display Name</label>
                            <input type="text" class="form-control" id="createNameShort" placeholder="e.g. John">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="createNameFirst" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="createNameFirst">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="createNameLast" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="createNameLast">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="createInstrumentsSelect" class="form-label">Instruments</label>
                        <select id="createInstrumentsSelect" class="form-control" multiple></select>
                    </div>
                    <?php if($canEditPermissions): ?>
                    <div class="mb-3">
                        <label for="createUserTypeSelect" class="form-label">User Type</label>
                        <select id="createUserTypeSelect" class="form-control">
                            <option value="">None</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <a class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" href="#createAdvancedPerms" role="button">
                            <i class="bi bi-gear"></i> Advanced Permissions
                        </a>
                        <div class="collapse mt-2" id="createAdvancedPerms">
                            <div class="card card-body" id="createPermissionsContainer"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveUserBtn">Save User</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- Edit User Modal                                                -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="editUserModalLabel">Edit User</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editUserId">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="editUsername" class="form-label">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="editUsername">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="editEmail" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="editEmail">
                        </div>
                    </div>
                    <div class="mb-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="editChangePassword">
                            <label class="form-check-label" for="editChangePassword">Change Password</label>
                        </div>
                        <input type="password" class="form-control mt-1 d-none" id="editPassword" placeholder="New password" autocomplete="new-password">
                    </div>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="editNameShort" class="form-label">Display Name</label>
                            <input type="text" class="form-control" id="editNameShort">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="editNameFirst" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="editNameFirst">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="editNameLast" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="editNameLast">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editInstrumentsSelect" class="form-label">Instruments</label>
                        <select id="editInstrumentsSelect" class="form-control" multiple></select>
                    </div>
                    <?php if($canEditPermissions): ?>
                    <div class="mb-3">
                        <label for="editUserTypeSelect" class="form-label">User Type</label>
                        <select id="editUserTypeSelect" class="form-control">
                            <option value="">None</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <a class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse" href="#editAdvancedPerms" role="button">
                            <i class="bi bi-gear"></i> Advanced Permissions
                        </a>
                        <div class="collapse mt-2" id="editAdvancedPerms">
                            <div class="card card-body" id="editPermissionsContainer"></div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="updateUserBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- Delete User Modal                                              -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="deleteUserModal" tabindex="-1" aria-labelledby="deleteUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="deleteUserModalLabel">Delete User</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <strong id="deleteUserName"></strong>?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteUserBtn"><i class="bi bi-trash"></i> Delete</button>
                </div>
            </div>
        </div>
    </div>

    <?php if($canEditPermissions): ?>
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- Create User Type Modal                                         -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="createUserTypeModal" tabindex="-1" aria-labelledby="createUserTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="createUserTypeModalLabel">Create User Type</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="createUserTypeName" class="form-label">User Type Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="createUserTypeName" placeholder="e.g. Admin">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Permissions</label>
                        <div id="createUserTypePermissionsContainer"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveUserTypeBtn">Save User Type</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- Edit User Type Modal                                           -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="editUserTypeModal" tabindex="-1" aria-labelledby="editUserTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="editUserTypeModalLabel">Edit User Type</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="editUserTypeId">
                    <div class="mb-3">
                        <label for="editUserTypeName" class="form-label">User Type Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editUserTypeName">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Permissions</label>
                        <div id="editUserTypePermissionsContainer"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="updateUserTypeBtn">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ═══════════════════════════════════════════════════════════════ -->
    <!-- Delete User Type Modal                                         -->
    <!-- ═══════════════════════════════════════════════════════════════ -->
    <div class="modal fade" id="deleteUserTypeModal" tabindex="-1" aria-labelledby="deleteUserTypeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="deleteUserTypeModalLabel">Delete User Type</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete <strong id="deleteUserTypeName"></strong>? This will remove it from all users who have it assigned.
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteUserTypeBtn"><i class="bi bi-trash"></i> Delete</button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>


    <script>
        const canEditUser        = <?= json_encode($canEditUser) ?>;
        const canDeleteUser      = <?= json_encode($canDeleteUser) ?>;
        const canEditPermissions = <?= json_encode($canEditPermissions) ?>;

        $(function () {
            fetchUsers();
            if (canEditPermissions) fetchUserTypes();
        });

        let userTable     = null;
        let userTypeTable = null;
        let currentEditRow = null;

        // TomSelect instances
        let createInstrumentsTS = null;
        let editInstrumentsTS   = null;

        // Cache for user type permissions (to avoid repeated AJAX calls)
        let userTypePermCache = {};

        function ajaxErrorHandler(jqXHR) {
            const errorMsg = jqXHR.responseJSON?.message || "An unexpected error occurred.";
            toastr.error(errorMsg);
        }

        // ════════════════════════════════════════════════════════════════
        // Permission checkbox builder
        // ════════════════════════════════════════════════════════════════

        function buildPermissionCheckboxes(containerId, groups, prefix) {
            const container = $('#' + containerId);
            container.empty();
            groups.forEach(function (group) {
                const groupCbId = prefix + '_group_' + group.groupHtml;
                let html = '<div class="mb-2">';
                html += '<div class="form-check">';
                html += '<input class="form-check-input group-checkbox" type="checkbox" value="' + group.groupHtml + '" id="' + groupCbId + '" data-group="' + group.groupHtml + '">';
                html += '<label class="form-check-label" for="' + groupCbId + '"><strong>' + group.groupName + '</strong></label>';
                html += '</div>';
                html += '<div class="ms-3" data-group-children="' + group.groupHtml + '">';
                group.permissions.forEach(function (perm) {
                    const cbId = prefix + '_' + perm.html.replace(/\./g, '_');
                    html += '<div class="form-check">';
                    html += '<input class="form-check-input perm-checkbox" type="checkbox" value="' + perm.html + '" id="' + cbId + '" data-perm="' + perm.html + '" data-parent-group="' + group.groupHtml + '">';
                    html += '<label class="form-check-label" for="' + cbId + '">' + perm.name + '</label>';
                    html += '</div>';
                });
                html += '</div></div>';
                container.append(html);
            });
        }

        // Delegated handler for group checkboxes
        $(document).on('change', '.group-checkbox', function () {
            const groupHtml = $(this).data('group');
            const container = $(this).closest('div[id]');
            const children = container.find('.perm-checkbox[data-parent-group="' + groupHtml + '"]');
            if ($(this).is(':checked')) {
                children.prop('checked', true).prop('disabled', true).closest('.form-check').addClass('opacity-50');
            } else {
                children.prop('checked', false).prop('disabled', false).closest('.form-check').removeClass('opacity-50');
            }
        });

        function getCheckedPermissions(containerId) {
            const perms = [];
            $('#' + containerId + ' .perm-checkbox:checked:not(:disabled)').each(function () {
                perms.push($(this).val());
            });
            return perms;
        }

        function getCheckedGroups(containerId) {
            const groups = [];
            $('#' + containerId + ' .group-checkbox:checked').each(function () {
                groups.push($(this).val());
            });
            return groups;
        }

        function autoCheckGroupsIfAllChildren(containerId) {
            $('#' + containerId + ' .group-checkbox').each(function () {
                const groupHtml = $(this).data('group');
                const children = $('#' + containerId + ' .perm-checkbox[data-parent-group="' + groupHtml + '"]');
                const allChecked = children.length > 0 && children.filter(':checked').length === children.length;
                if (allChecked) {
                    $(this).prop('checked', true).trigger('change');
                }
            });
        }

        function applyUserTypeToCheckboxes(containerId, userTypeId) {
            // Re-enable all perm checkboxes first (but respect group-locked ones)
            $('#' + containerId + ' .perm-checkbox').each(function () {
                const parentGroup = $(this).data('parent-group');
                const groupCb = $('#' + containerId + ' .group-checkbox[data-group="' + parentGroup + '"]');
                if (groupCb.is(':checked')) return; // still locked by group
                $(this).prop('disabled', false).closest('.form-check').removeClass('opacity-50');
            });

            if (!userTypeId) return Promise.resolve();

            // Fetch user type permissions and apply
            if (userTypePermCache[userTypeId]) {
                setUserTypeCheckboxes(containerId, userTypePermCache[userTypeId]);
                return Promise.resolve();
            }

            return new Promise(function (resolve) {
                $.ajax({
                    type: 'POST',
                    url: 'lib/action.php',
                    data: { action: 'getUserTypeDetail', idUserType: userTypeId },
                    dataType: 'JSON',
                    success: function (resp) {
                        userTypePermCache[userTypeId] = resp.permissions || [];
                        setUserTypeCheckboxes(containerId, resp.permissions || []);
                        resolve();
                    },
                    error: function (xhr) {
                        ajaxErrorHandler(xhr);
                        resolve();
                    }
                });
            });
        }

        function setUserTypeCheckboxes(containerId, permissions) {
            permissions.forEach(function (perm) {
                const cb = $('#' + containerId + ' .perm-checkbox[data-perm="' + perm + '"]');
                cb.prop('checked', true).prop('disabled', true).closest('.form-check').addClass('opacity-50');
            });
        }


        // ════════════════════════════════════════════════════════════════
        // Users DataTable
        // ════════════════════════════════════════════════════════════════

        const userColumns = canEditUser
            ? [
                { data: 'nameShort', title: 'Name' },
                { data: 'username', title: 'Username' },
                { data: 'email', title: 'Email' },
                { data: 'instruments', title: 'Instruments' },
                { data: 'userTypeName', title: 'User Type' },
                { data: null, defaultContent: '', title: '' },
              ]
            : [
                { data: 'nameShort', title: 'Name' },
                { data: 'username', title: 'Username' },
                { data: 'email', title: 'Email' },
                { data: 'instruments', title: 'Instruments' },
                { data: 'userTypeName', title: 'User Type' },
              ];

        const userColumnDefs = canEditUser
            ? [
                { targets: 5, orderable: false, searchable: false,
                    render: function (data, type, row) {
                        let html = '';
                        if (canEditUser)   html += '<button class="btn btn-sm btn-outline-secondary edit-user-btn me-1"><i class="bi bi-pencil"></i> Edit</button>';
                        if (canDeleteUser) html += '<button class="btn btn-sm btn-outline-danger delete-user-btn"><i class="bi bi-trash"></i> Delete</button>';
                        return html;
                    }
                },
              ]
            : [];

        const fetchUsers = () => {
            if (userTable) {
                userTable.ajax.reload();
            } else {
                userTable = $('#userTable').DataTable({
                    processing: true,
                    serverSide: false,
                    lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                    pageLength: -1,
                    ajax: {
                        url: 'lib/action.php',
                        type: 'POST',
                        data: function (d) { d.action = "getUsers"; },
                        error: ajaxErrorHandler
                    },
                    columns: userColumns,
                    columnDefs: userColumnDefs,
                    responsive: true,
                });
                setTimeout(() => { userTable.draw(); }, 200);
            }
        };


        // ════════════════════════════════════════════════════════════════
        // Delete User
        // ════════════════════════════════════════════════════════════════

        let deleteRow = null;

        $('#userTable').on('click', '.delete-user-btn', function () {
            deleteRow = userTable.row($(this).closest('tr')).data();
            $('#deleteUserName').text(deleteRow.username);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteUserModal')).show();
        });

        $('#deleteUserModal').on('hidden.bs.modal', function () { deleteRow = null; });

        $('#confirmDeleteUserBtn').on('click', function () {
            if (!deleteRow) return;
            $.ajax({
                type: 'POST',
                url: 'lib/action.php',
                data: { action: 'deleteUser', idUser: deleteRow.idUser },
                dataType: 'JSON',
                success: function () {
                    bootstrap.Modal.getInstance(document.getElementById('deleteUserModal')).hide();
                    fetchUsers();
                    toastr.success('User deleted successfully.');
                },
                error: ajaxErrorHandler
            });
        });


        // ════════════════════════════════════════════════════════════════
        // Edit User
        // ════════════════════════════════════════════════════════════════

        $('#userTable').on('click', '.edit-user-btn', function () {
            currentEditRow = userTable.row($(this).closest('tr')).data();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editUserModal')).show();
        });

        // Toggle password field visibility
        $('#editChangePassword').on('change', function () {
            $('#editPassword').toggleClass('d-none', !this.checked).val('');
        });

        $('#editUserModal').on('shown.bs.modal', function () {
            if (!currentEditRow) return;

            $('#editUserId').val(currentEditRow.idUser);
            $('#editChangePassword').prop('checked', false);
            $('#editPassword').addClass('d-none').val('');

            // Load full user detail
            $.ajax({
                type: 'POST',
                url: 'lib/action.php',
                data: { action: 'getUserDetail', idUser: currentEditRow.idUser },
                dataType: 'JSON',
                success: function (user) {
                    $('#editUsername').val(user.username);
                    $('#editEmail').val(user.email);
                    $('#editNameShort').val(user.nameShort);
                    $('#editNameFirst').val(user.nameFirst);
                    $('#editNameLast').val(user.nameLast);

                    // Load instruments for TomSelect
                    $.ajax({
                        type: 'POST',
                        url: 'lib/action.php',
                        data: { action: 'getInstrumentsList' },
                        dataType: 'JSON',
                        success: function (resp) {
                            editInstrumentsTS = new TomSelect('#editInstrumentsSelect', {
                                options: resp.data || [],
                                valueField: 'value',
                                labelField: 'text',
                                searchField: 'text',
                                plugins: ['remove_button'],
                                placeholder: 'Select instruments...',
                                maxItems: null,
                            });
                            editInstrumentsTS.setValue(user.instrumentIds || [], true);
                        },
                        error: ajaxErrorHandler
                    });

                    // Load permissions if allowed
                    if (canEditPermissions) {
                        // Load user types for dropdown
                        $.ajax({
                            type: 'POST',
                            url: 'lib/action.php',
                            data: { action: 'getUserTypes' },
                            dataType: 'JSON',
                            success: function (resp) {
                                const sel = $('#editUserTypeSelect');
                                sel.find('option:not(:first)').remove();
                                (resp.data || []).forEach(function (ut) {
                                    sel.append('<option value="' + ut.idUserType + '">' + ut.userTypeName + '</option>');
                                });
                                sel.val(user.userTypeId || '');
                            },
                            error: ajaxErrorHandler
                        });

                        // Load permission checkboxes
                        $.ajax({
                            type: 'POST',
                            url: 'lib/action.php',
                            data: { action: 'getPermissionsList' },
                            dataType: 'JSON',
                            success: function (resp) {
                                buildPermissionCheckboxes('editPermissionsContainer', resp.groups || [], 'edit');

                                // Check group permissions (tick group checkbox + lock children)
                                (user.groupPermissions || []).forEach(function (groupHtml) {
                                    const groupCb = $('#editPermissionsContainer .group-checkbox[data-group="' + groupHtml + '"]');
                                    groupCb.prop('checked', true).trigger('change');
                                });

                                // Check individual permissions
                                (user.individualPermissions || []).forEach(function (perm) {
                                    $('#editPermissionsContainer .perm-checkbox[data-perm="' + perm + '"]').prop('checked', true);
                                });

                                // Auto-check group boxes where all children are already checked
                                autoCheckGroupsIfAllChildren('editPermissionsContainer');

                                // Apply user type locked permissions
                                if (user.userTypeId) {
                                    applyUserTypeToCheckboxes('editPermissionsContainer', user.userTypeId);
                                }
                            },
                            error: ajaxErrorHandler
                        });
                    }
                },
                error: ajaxErrorHandler
            });
        });

        // User type change in edit modal
        $(document).on('change', '#editUserTypeSelect', function () {
            applyUserTypeToCheckboxes('editPermissionsContainer', $(this).val());
        });

        $('#editUserModal').on('hidden.bs.modal', function () {
            if (editInstrumentsTS) { editInstrumentsTS.destroy(); editInstrumentsTS = null; }
            currentEditRow = null;
            if (canEditPermissions) {
                $('#editPermissionsContainer').empty();
                $('#editUserTypeSelect').find('option:not(:first)').remove();
                // Ensure the collapse is closed
                $('#editAdvancedPerms').removeClass('show');
            }
        });

        $('#updateUserBtn').on('click', function () {
            const idUser   = $('#editUserId').val();
            const username = $('#editUsername').val().trim();
            const email    = $('#editEmail').val().trim();

            if (!username || !email) {
                toastr.error('Username and email are required.');
                return;
            }

            const data = {
                action:    'updateUser',
                idUser:    idUser,
                username:  username,
                email:     email,
                nameShort: $('#editNameShort').val().trim(),
                nameFirst: $('#editNameFirst').val().trim(),
                nameLast:  $('#editNameLast').val().trim(),
            };

            // Password
            if ($('#editChangePassword').is(':checked')) {
                const pw = $('#editPassword').val();
                if (!pw) {
                    toastr.error('Please enter a new password or uncheck "Change Password".');
                    return;
                }
                data.password = pw;
            }

            // Instruments
            data.instrumentIds = editInstrumentsTS ? editInstrumentsTS.getValue() : [];

            // Permissions
            if (canEditPermissions) {
                data.userTypeId = $('#editUserTypeSelect').val() || '';
                data.individualPermissions = getCheckedPermissions('editPermissionsContainer');
                data.groupPermissions = getCheckedGroups('editPermissionsContainer');
            }

            $.ajax({
                type: 'POST',
                url: 'lib/action.php',
                data: data,
                dataType: 'JSON',
                success: function () {
                    bootstrap.Modal.getInstance(document.getElementById('editUserModal')).hide();
                    fetchUsers();
                    toastr.success('User updated successfully.');
                },
                error: ajaxErrorHandler
            });
        });


        // ════════════════════════════════════════════════════════════════
        // Create User
        // ════════════════════════════════════════════════════════════════

        $('#createUserModal').on('shown.bs.modal', function () {
            $('#createUsername, #createEmail, #createPassword, #createNameShort, #createNameFirst, #createNameLast').val('');

            // Load instruments
            $.ajax({
                type: 'POST',
                url: 'lib/action.php',
                data: { action: 'getInstrumentsList' },
                dataType: 'JSON',
                success: function (resp) {
                    createInstrumentsTS = new TomSelect('#createInstrumentsSelect', {
                        options: resp.data || [],
                        valueField: 'value',
                        labelField: 'text',
                        searchField: 'text',
                        plugins: ['remove_button'],
                        placeholder: 'Select instruments...',
                        maxItems: null,
                    });
                },
                error: ajaxErrorHandler
            });

            if (canEditPermissions) {
                // Load user types
                $.ajax({
                    type: 'POST',
                    url: 'lib/action.php',
                    data: { action: 'getUserTypes' },
                    dataType: 'JSON',
                    success: function (resp) {
                        const sel = $('#createUserTypeSelect');
                        sel.find('option:not(:first)').remove();
                        (resp.data || []).forEach(function (ut) {
                            sel.append('<option value="' + ut.idUserType + '">' + ut.userTypeName + '</option>');
                        });
                    },
                    error: ajaxErrorHandler
                });

                // Load permission checkboxes
                $.ajax({
                    type: 'POST',
                    url: 'lib/action.php',
                    data: { action: 'getPermissionsList' },
                    dataType: 'JSON',
                    success: function (resp) {
                        buildPermissionCheckboxes('createPermissionsContainer', resp.groups || [], 'create');
                    },
                    error: ajaxErrorHandler
                });
            }
        });

        // User type change in create modal
        $(document).on('change', '#createUserTypeSelect', function () {
            applyUserTypeToCheckboxes('createPermissionsContainer', $(this).val());
        });

        $('#createUserModal').on('hidden.bs.modal', function () {
            if (createInstrumentsTS) { createInstrumentsTS.destroy(); createInstrumentsTS = null; }
            if (canEditPermissions) {
                $('#createPermissionsContainer').empty();
                $('#createUserTypeSelect').find('option:not(:first)').remove();
                $('#createAdvancedPerms').removeClass('show');
            }
        });

        $('#saveUserBtn').on('click', function () {
            const username = $('#createUsername').val().trim();
            const password = $('#createPassword').val();
            const email    = $('#createEmail').val().trim();

            if (!username || !password || !email) {
                toastr.error('Username, password, and email are required.');
                return;
            }

            const data = {
                action:    'createUser',
                username:  username,
                password:  password,
                email:     email,
                nameShort: $('#createNameShort').val().trim(),
                nameFirst: $('#createNameFirst').val().trim(),
                nameLast:  $('#createNameLast').val().trim(),
            };

            // Instruments
            data.instrumentIds = createInstrumentsTS ? createInstrumentsTS.getValue() : [];

            // Permissions
            if (canEditPermissions) {
                data.userTypeId = $('#createUserTypeSelect').val() || '';
                data.individualPermissions = getCheckedPermissions('createPermissionsContainer');
                data.groupPermissions = getCheckedGroups('createPermissionsContainer');
            }

            $.ajax({
                type: 'POST',
                url: 'lib/action.php',
                data: data,
                dataType: 'JSON',
                success: function () {
                    bootstrap.Modal.getInstance(document.getElementById('createUserModal')).hide();
                    fetchUsers();
                    toastr.success('User created successfully.');
                },
                error: ajaxErrorHandler
            });
        });


        // ════════════════════════════════════════════════════════════════
        // User Types DataTable
        // ════════════════════════════════════════════════════════════════

        <?php if($canEditPermissions): ?>

        const fetchUserTypes = () => {
            if (userTypeTable) {
                userTypeTable.ajax.reload();
            } else {
                userTypeTable = $('#userTypeTable').DataTable({
                    processing: true,
                    serverSide: false,
                    lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
                    pageLength: -1,
                    ajax: {
                        url: 'lib/action.php',
                        type: 'POST',
                        data: function (d) { d.action = "getUserTypes"; },
                        error: ajaxErrorHandler
                    },
                    columns: [
                        { data: 'userTypeName', title: 'User Type' },
                        { data: 'permissionCount', title: 'Permissions' },
                        { data: null, defaultContent: '', title: '' },
                    ],
                    columnDefs: [
                        { targets: 2, orderable: false, searchable: false,
                            render: function (data, type, row) {
                                return '<button class="btn btn-sm btn-outline-secondary edit-usertype-btn me-1"><i class="bi bi-pencil"></i> Edit</button>'
                                     + '<button class="btn btn-sm btn-outline-danger delete-usertype-btn"><i class="bi bi-trash"></i> Delete</button>';
                            }
                        },
                    ],
                    responsive: true,
                });
                setTimeout(() => { userTypeTable.draw(); }, 200);
            }
        };


        // ── Create User Type ──────────────────────────────────────────

        $('#createUserTypeModal').on('shown.bs.modal', function () {
            $('#createUserTypeName').val('');
            $.ajax({
                type: 'POST',
                url: 'lib/action.php',
                data: { action: 'getPermissionsList' },
                dataType: 'JSON',
                success: function (resp) {
                    buildPermissionCheckboxes('createUserTypePermissionsContainer', resp.groups || [], 'createUT');
                },
                error: ajaxErrorHandler
            });
        });

        $('#createUserTypeModal').on('hidden.bs.modal', function () {
            $('#createUserTypePermissionsContainer').empty();
        });

        $('#saveUserTypeBtn').on('click', function () {
            const name = $('#createUserTypeName').val().trim();
            if (!name) {
                toastr.error('User Type name is required.');
                return;
            }

            const perms = [];
            $('#createUserTypePermissionsContainer .perm-checkbox:checked').each(function () {
                perms.push($(this).val());
            });

            $.ajax({
                type: 'POST',
                url: 'lib/action.php',
                data: { action: 'createUserType', userTypeName: name, permissions: perms },
                dataType: 'JSON',
                success: function () {
                    bootstrap.Modal.getInstance(document.getElementById('createUserTypeModal')).hide();
                    fetchUserTypes();
                    userTypePermCache = {};
                    toastr.success('User Type created successfully.');
                },
                error: ajaxErrorHandler
            });
        });


        // ── Edit User Type ────────────────────────────────────────────

        let currentEditUserType = null;

        $('#userTypeTable').on('click', '.edit-usertype-btn', function () {
            currentEditUserType = userTypeTable.row($(this).closest('tr')).data();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('editUserTypeModal')).show();
        });

        $('#editUserTypeModal').on('shown.bs.modal', function () {
            if (!currentEditUserType) return;

            $('#editUserTypeId').val(currentEditUserType.idUserType);
            $('#editUserTypeName').val(currentEditUserType.userTypeName);

            // Load permissions checkboxes, then check the ones this type has
            $.when(
                $.ajax({ type: 'POST', url: 'lib/action.php', data: { action: 'getPermissionsList' }, dataType: 'JSON' }),
                $.ajax({ type: 'POST', url: 'lib/action.php', data: { action: 'getUserTypeDetail', idUserType: currentEditUserType.idUserType }, dataType: 'JSON' })
            ).done(function (permsResp, detailResp) {
                const groups = permsResp[0].groups || [];
                const typePerms = detailResp[0].permissions || [];
                buildPermissionCheckboxes('editUserTypePermissionsContainer', groups, 'editUT');
                typePerms.forEach(function (perm) {
                    $('#editUserTypePermissionsContainer .perm-checkbox[data-perm="' + perm + '"]').prop('checked', true);
                });
                autoCheckGroupsIfAllChildren('editUserTypePermissionsContainer');
            }).fail(ajaxErrorHandler);
        });

        $('#editUserTypeModal').on('hidden.bs.modal', function () {
            currentEditUserType = null;
            $('#editUserTypePermissionsContainer').empty();
        });

        $('#updateUserTypeBtn').on('click', function () {
            const id   = $('#editUserTypeId').val();
            const name = $('#editUserTypeName').val().trim();
            if (!name) {
                toastr.error('User Type name is required.');
                return;
            }

            const perms = [];
            $('#editUserTypePermissionsContainer .perm-checkbox:checked').each(function () {
                perms.push($(this).val());
            });

            $.ajax({
                type: 'POST',
                url: 'lib/action.php',
                data: { action: 'updateUserType', idUserType: id, userTypeName: name, permissions: perms },
                dataType: 'JSON',
                success: function () {
                    bootstrap.Modal.getInstance(document.getElementById('editUserTypeModal')).hide();
                    fetchUserTypes();
                    fetchUsers(); // Refresh in case user type names changed
                    userTypePermCache = {};
                    toastr.success('User Type updated successfully.');
                },
                error: ajaxErrorHandler
            });
        });


        // ── Delete User Type ──────────────────────────────────────────

        let deleteUserTypeRow = null;

        $('#userTypeTable').on('click', '.delete-usertype-btn', function () {
            deleteUserTypeRow = userTypeTable.row($(this).closest('tr')).data();
            $('#deleteUserTypeName').text(deleteUserTypeRow.userTypeName);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('deleteUserTypeModal')).show();
        });

        $('#deleteUserTypeModal').on('hidden.bs.modal', function () { deleteUserTypeRow = null; });

        $('#confirmDeleteUserTypeBtn').on('click', function () {
            if (!deleteUserTypeRow) return;
            $.ajax({
                type: 'POST',
                url: 'lib/action.php',
                data: { action: 'deleteUserType', idUserType: deleteUserTypeRow.idUserType },
                dataType: 'JSON',
                success: function () {
                    bootstrap.Modal.getInstance(document.getElementById('deleteUserTypeModal')).hide();
                    fetchUserTypes();
                    fetchUsers();
                    userTypePermCache = {};
                    toastr.success('User Type deleted successfully.');
                },
                error: ajaxErrorHandler
            });
        });

        <?php endif; ?>
    </script>
</html>
