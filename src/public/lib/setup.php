<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../lib/class/DatabaseManager.php';
require_once __DIR__ . '/../../lib/class/Helper.php';
require_once __DIR__ . '/../../lib/class/User.php';
require_once __DIR__ . '/../../vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed.']);
    exit;
}

$db = new DatabaseManager();

// Guard: only allow setup when no users exist
$userCount = (int)($db->query("SELECT COUNT(*) as cnt FROM `users`")[0]['cnt'] ?? 0);
if ($userCount > 0) {
    http_response_code(403);
    echo json_encode(['message' => 'Setup is already complete.']);
    exit;
}

$username  = trim($_POST['username']  ?? '');
$email     = trim($_POST['email']     ?? '');
$password  = $_POST['password']       ?? '';
$confirm   = $_POST['confirm']        ?? '';
$nameShort = trim($_POST['nameShort'] ?? '') ?: null;

if (empty($username) || empty($email) || empty($password)) {
    http_response_code(400);
    echo json_encode(['message' => 'Username, email and password are required.']);
    exit;
}

if ($password !== $confirm) {
    http_response_code(400);
    echo json_encode(['message' => 'Passwords do not match.']);
    exit;
}

if (strlen($password) < 8) {
    http_response_code(400);
    echo json_encode(['message' => 'Password must be at least 8 characters.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid email address.']);
    exit;
}

try {
    $user = User::CreateUser($username, $password, $email, $nameShort ?? $username, null, null);

    // Grant all permissions individually so the admin has full access
    $permissions = $db->query("SELECT `permissionTypeHtml` FROM `site__permissions`");
    $permRows = array_map(fn($p) => [
        'permissionType'      => 'individual',
        'permissionValueHtml' => $p['permissionTypeHtml'],
    ], $permissions);

    $user->setPermissions($permRows);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => $e->getMessage()]);
}
