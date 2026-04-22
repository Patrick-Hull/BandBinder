<?php
require_once __DIR__ . '/../../../lib/util_all.php';
header('Content-Type: application/json');
http_response_code(418);

if(!isset($_SESSION['user'])) {
    http_response_code(401);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;

switch($action) {

    // ── Users ──────────────────────────────────────────────────────────

    case 'getUsers':
        if(!in_array('users.view', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }

        try {
            $users = User::GetAll();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }

        $datatable = [];
        foreach($users as $user){
            $instruments = $user->getInstruments();
            $instrumentNames = array_map(fn($i) => $i->getInstrumentName(), $instruments);

            $userType = $user->getUserType();
            $userTypeName = $userType ? $userType->getUserTypeName() : '';

            $datatable[] = [
                'idUser'        => $user->getIdUser(),
                'username'      => $user->getUsername(),
                'email'         => $user->getEmail(),
                'nameShort'     => $user->getNameShort() ?? '',
                'nameFirst'     => $user->getNameFirst() ?? '',
                'nameLast'      => $user->getNameLast() ?? '',
                'hasPassword'   => $user->hasPassword(),
                'instruments'   => implode(', ', $instrumentNames),
                'userTypeName'  => $userTypeName,
            ];
        }

        http_response_code(200);
        echo json_encode(['data' => $datatable]);
        break;

    case 'getUserDetail':
        if(!in_array('users.edit', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }

        $idUser = trim($_POST['idUser'] ?? '');
        if($idUser === ''){
            http_response_code(400);
            echo json_encode(['message' => 'User ID is required']);
            exit;
        }

        try {
            $user = new User($idUser);
            $instrumentIds = $user->getRawInstrumentIds();
            $permissionRows = $user->getUserPermissionRows();
            $userType = $user->getUserType();

            $individualPermissions = [];
            $groupPermissions = [];
            $userTypeId = null;

            foreach ($permissionRows as $row) {
                if ($row['permissionType'] === 'individual') {
                    $individualPermissions[] = $row['permissionValueHtml'];
                } elseif ($row['permissionType'] === 'group') {
                    $groupPermissions[] = $row['permissionValueHtml'];
                } elseif ($row['permissionType'] === 'userType') {
                    $userTypeId = $row['permissionValueHtml'];
                }
            }

            http_response_code(200);
            echo json_encode([
                'idUser'                => $user->getIdUser(),
                'username'              => $user->getUsername(),
                'email'                 => $user->getEmail(),
                'nameShort'             => $user->getNameShort() ?? '',
                'nameFirst'             => $user->getNameFirst() ?? '',
                'nameLast'              => $user->getNameLast() ?? '',
                'instrumentIds'         => $instrumentIds,
                'userTypeId'            => $userTypeId,
                'individualPermissions' => $individualPermissions,
                'groupPermissions'      => $groupPermissions,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }
        break;

    case 'createUser':
        if(!in_array('users.create', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }

        $username  = trim($_POST['username'] ?? '');
        $password  = $_POST['password'] ?? '';
        $email     = trim($_POST['email'] ?? '');
        $nameShort = trim($_POST['nameShort'] ?? '') ?: null;
        $nameFirst = trim($_POST['nameFirst'] ?? '') ?: null;
        $nameLast  = trim($_POST['nameLast'] ?? '') ?: null;

        if($username === '' || $email === ''){
            http_response_code(400);
            echo json_encode(['message' => 'Username and email are required']);
            exit;
        }

        try {
            $user = User::CreateUser($username, $password, $email, $nameShort, $nameFirst, $nameLast);

            // Set instruments if provided and user has edit permission
            $instrumentIds = $_POST['instrumentIds'] ?? [];
            if (is_array($instrumentIds) && !empty($instrumentIds)) {
                $user->setInstruments($instrumentIds);
            }

            // Set permissions if provided and user has editPermissions permission
            if (in_array('users.editPermissions', $_SESSION['user']['permissions'])) {
                $permRows = [];

                $userTypeId = trim($_POST['userTypeId'] ?? '');
                if ($userTypeId !== '') {
                    $permRows[] = ['permissionType' => 'userType', 'permissionValueHtml' => $userTypeId];
                }

                $individualPerms = $_POST['individualPermissions'] ?? [];
                if (is_array($individualPerms)) {
                    foreach ($individualPerms as $perm) {
                        $permRows[] = ['permissionType' => 'individual', 'permissionValueHtml' => $perm];
                    }
                }

                $groupPerms = $_POST['groupPermissions'] ?? [];
                if (is_array($groupPerms)) {
                    foreach ($groupPerms as $group) {
                        $permRows[] = ['permissionType' => 'group', 'permissionValueHtml' => $group];
                    }
                }

                if (!empty($permRows)) {
                    $user->setPermissions($permRows);
                }
            }

            http_response_code(200);
            echo json_encode(['id' => $user->getIdUser()]);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'Duplicate entry') !== false) {
                if (stripos($msg, 'username') !== false) {
                    $msg = 'Username already exists';
                } elseif (stripos($msg, 'email') !== false) {
                    $msg = 'Email already in use';
                }
            }
            http_response_code(500);
            echo json_encode(['message' => $msg]);
            exit;
        }
        break;

    case 'updateUser':
        if(!in_array('users.edit', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }

        $idUser    = trim($_POST['idUser'] ?? '');
        $username  = trim($_POST['username'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $nameShort = trim($_POST['nameShort'] ?? '') ?: null;
        $nameFirst = trim($_POST['nameFirst'] ?? '') ?: null;
        $nameLast  = trim($_POST['nameLast'] ?? '') ?: null;

        if($idUser === '' || $username === '' || $email === ''){
            http_response_code(400);
            echo json_encode(['message' => 'User ID, username, and email are required']);
            exit;
        }

        try {
            $user = new User($idUser);
            $user->UpdateUser($username, $email, $nameShort, $nameFirst, $nameLast);

            // Update password if provided
            $password = $_POST['password'] ?? '';
            if ($password !== '') {
                $user->UpdatePassword($password);
            }

            // Update instruments
            $instrumentIds = $_POST['instrumentIds'] ?? [];
            if (is_array($instrumentIds)) {
                $user->setInstruments($instrumentIds);
            }

            // Update permissions if caller has editPermissions
            if (in_array('users.editPermissions', $_SESSION['user']['permissions'])) {
                $permRows = [];

                $userTypeId = trim($_POST['userTypeId'] ?? '');
                if ($userTypeId !== '') {
                    $permRows[] = ['permissionType' => 'userType', 'permissionValueHtml' => $userTypeId];
                }

                $individualPerms = $_POST['individualPermissions'] ?? [];
                if (is_array($individualPerms)) {
                    foreach ($individualPerms as $perm) {
                        $permRows[] = ['permissionType' => 'individual', 'permissionValueHtml' => $perm];
                    }
                }

                $groupPerms = $_POST['groupPermissions'] ?? [];
                if (is_array($groupPerms)) {
                    foreach ($groupPerms as $group) {
                        $permRows[] = ['permissionType' => 'group', 'permissionValueHtml' => $group];
                    }
                }

                $user->setPermissions($permRows);
            }

            http_response_code(200);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            $msg = $e->getMessage();
            if (stripos($msg, 'Duplicate entry') !== false) {
                if (stripos($msg, 'username') !== false) {
                    $msg = 'Username already exists';
                } elseif (stripos($msg, 'email') !== false) {
                    $msg = 'Email already in use';
                }
            }
            http_response_code(500);
            echo json_encode(['message' => $msg]);
            exit;
        }
        break;

    case 'deleteUser':
        if(!in_array('users.delete', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }

        $idUser = trim($_POST['idUser'] ?? '');
        if($idUser === ''){
            http_response_code(400);
            echo json_encode(['message' => 'User ID is required']);
            exit;
        }

        // Prevent self-delete
        if($idUser === $_SESSION['user']['me']){
            http_response_code(400);
            echo json_encode(['message' => 'You cannot delete your own account']);
            exit;
        }

        try {
            $user = new User($idUser);
            $user->DeleteUser();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }

        http_response_code(200);
        echo json_encode(['success' => true]);
        break;

    case 'sendWelcomeEmail':
        if(!in_array('users.edit', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }

        $idUser = trim($_POST['idUser'] ?? '');
        if($idUser === ''){
            http_response_code(400);
            echo json_encode(['message' => 'User ID is required']);
            exit;
        }

        try {
            $user = new User($idUser);
            $sent = $user->sendWelcomeEmail();
            if ($sent) {
                http_response_code(200);
                echo json_encode(['success' => true, 'message' => 'Welcome email sent successfully.']);
            } else {
                http_response_code(500);
                echo json_encode(['message' => 'Failed to send welcome email. Check mail configuration.']);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }
        break;

    // ── Instruments list (for user assignment) ─────────────────────────

    case 'getInstrumentsList':
        if(!in_array('users.edit', $_SESSION['user']['permissions']) && !in_array('users.create', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }

        try {
            $instruments = Instrument::GetAll();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }

        $data = [];
        foreach ($instruments as $inst) {
            $data[] = [
                'value' => $inst->getIdInstrument(),
                'text'  => $inst->getInstrumentName(),
            ];
        }

        http_response_code(200);
        echo json_encode(['data' => $data]);
        break;

    // ── Permissions list (for permission checkboxes) ───────────────────

    case 'getPermissionsList':
        if(!in_array('users.editPermissions', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }

        try {
            $db = new DatabaseManager();
            $groups = $db->query("SELECT * FROM `site__permissionGroups` ORDER BY `permissionGroupName`");
            $result = [];
            foreach ($groups as $group) {
                $perms = $db->query(
                    "SELECT `permissionTypeHtml`, `permissionTypeName` FROM `site__permissions` WHERE `permissionGroupHtml` = ? ORDER BY `permissionTypeName`",
                    [$group['permissionGroupHtml']]
                );
                $result[] = [
                    'groupHtml' => $group['permissionGroupHtml'],
                    'groupName' => $group['permissionGroupName'],
                    'permissions' => array_map(fn($p) => [
                        'html' => $p['permissionTypeHtml'],
                        'name' => $p['permissionTypeName'],
                    ], $perms),
                ];
            }

            http_response_code(200);
            echo json_encode(['groups' => $result]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }
        break;

    // ── User Types ─────────────────────────────────────────────────────

    case 'getUserTypes':
        if(!in_array('users.view', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }

        try {
            $userTypes = UserType::GetAll();
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }

        $data = [];
        foreach ($userTypes as $ut) {
            $permCount = count($ut->getPermissions());
            $data[] = [
                'idUserType'      => $ut->getIdUserType(),
                'userTypeName'    => $ut->getUserTypeName(),
                'permissionCount' => $permCount,
            ];
        }

        http_response_code(200);
        echo json_encode(['data' => $data]);
        break;

    case 'getUserTypeDetail':
        if(!in_array('users.editPermissions', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }

        $idUserType = trim($_POST['idUserType'] ?? '');
        if($idUserType === ''){
            http_response_code(400);
            echo json_encode(['message' => 'User Type ID is required']);
            exit;
        }

        try {
            $userType = new UserType($idUserType);
            http_response_code(200);
            echo json_encode([
                'idUserType'    => $userType->getIdUserType(),
                'userTypeName'  => $userType->getUserTypeName(),
                'permissions'   => $userType->getPermissions(),
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }
        break;

    case 'createUserType':
        if(!in_array('users.editPermissions', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }

        $userTypeName = trim($_POST['userTypeName'] ?? '');
        if($userTypeName === ''){
            http_response_code(400);
            echo json_encode(['message' => 'User Type name is required']);
            exit;
        }

        $permissions = $_POST['permissions'] ?? [];
        if(!is_array($permissions)){
            $permissions = [];
        }

        try {
            $userType = UserType::CreateUserType($userTypeName, $permissions);
            http_response_code(200);
            echo json_encode([
                'id'   => $userType->getIdUserType(),
                'name' => $userType->getUserTypeName(),
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }
        break;

    case 'updateUserType':
        if(!in_array('users.editPermissions', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }

        $idUserType   = trim($_POST['idUserType'] ?? '');
        $userTypeName = trim($_POST['userTypeName'] ?? '');

        if($idUserType === '' || $userTypeName === ''){
            http_response_code(400);
            echo json_encode(['message' => 'User Type ID and name are required']);
            exit;
        }

        $permissions = $_POST['permissions'] ?? [];
        if(!is_array($permissions)){
            $permissions = [];
        }

        try {
            $userType = new UserType($idUserType);
            $userType->UpdateUserType($userTypeName, $permissions);
            http_response_code(200);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }
        break;

    case 'deleteUserType':
        if(!in_array('users.editPermissions', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['message' => 'Permission denied']);
            exit;
        }

        $idUserType = trim($_POST['idUserType'] ?? '');
        if($idUserType === ''){
            http_response_code(400);
            echo json_encode(['message' => 'User Type ID is required']);
            exit;
        }

        try {
            $userType = new UserType($idUserType);
            $userType->DeleteUserType();
            http_response_code(200);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
