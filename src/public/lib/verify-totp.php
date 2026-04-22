<?php
require_once __DIR__ . '/../../lib/util_all.php';
require_once __DIR__ . '/../../lib/class/User.php';
require_once __DIR__ . '/../../lib/class/Auth.php';
header('Content-Type: application/json');

use OTPHP\TOTP;

if (!isset($_SESSION['user'])) {
    $userId = $_POST['userId'] ?? '';
    $code = $_POST['code'] ?? '';

    if (empty($userId) || empty($code)) {
        http_response_code(400);
        echo json_encode(['message' => 'User ID and code are required']);
        exit;
    }

    try {
        $user = new User($userId);
        
        if (!$user->getTotpEnabled()) {
            http_response_code(400);
            echo json_encode(['message' => '2FA is not enabled for this user']);
            exit;
        }

        $secret = $user->getTotpSecret();
        $issuer = 'BandBinder';
        $label = $user->getUsername();
        $totp = TOTP::createFromSecret($secret);
        $totp = $totp->withIssuer($issuer)->withLabel($label);
        
        if ($totp->verify($code)) {
            Auth::SetSessionData($user);
            http_response_code(200);
            echo json_encode(['success' => true]);
        } else {
            http_response_code(401);
            echo json_encode(['message' => 'Invalid verification code']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['message' => $e->getMessage()]);
    }
} else {
    http_response_code(401);
    echo json_encode(['message' => 'Already logged in']);
}
