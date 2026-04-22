<?php
require_once __DIR__ . '/../../../lib/util_all.php';
header('Content-Type: application/json');

if(!isset($_SESSION['user'])) {
    http_response_code(401);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;

use OTPHP\TOTP;

switch($action) {

    case 'setup2FA':
        try {
            $userId = $_SESSION['user']['me'];
            $user = new User($userId);
            
            $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
            $secretString = '';
            for ($i = 0; $i < 16; $i++) {
                $secretString .= $base32Chars[random_int(0, 31)];
            }
            
            $issuer = 'BandBinder';
            $label = $user->getUsername();
            
            $totp = TOTP::createFromSecret($secretString);
            $totp = $totp->withIssuer($issuer)->withLabel($label);
            
            $qrCodeUrl = $totp->getProvisioningUri();
            
            $qrCode = new \chillerlan\QRCode\QRCode();
            $qrCodeData = $qrCode->render($qrCodeUrl);
            
            if (strpos($qrCodeData, 'data:') === 0) {
                $qrCodeUrl = $qrCodeData;
            } else {
                $qrCodeUrl = 'data:image/png;base64,' . base64_encode($qrCodeData);
            }
            
            http_response_code(200);
            echo json_encode([
                'secret' => $secretString,
                'qrCodeUrl' => $qrCodeUrl,
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
        }
        break;

    case 'enable2FA':
        $secret = $_POST['secret'] ?? '';
        $code = $_POST['code'] ?? '';

        if (empty($secret) || empty($code)) {
            http_response_code(400);
            echo json_encode(['message' => 'Secret and code are required']);
            exit;
        }

        try {
            $userId = $_SESSION['user']['me'];
            $user = new User($userId);
            
            $issuer = 'BandBinder';
            $label = $user->getUsername();
            $totp = TOTP::createFromSecret($secret);
            $totp = $totp->withIssuer($issuer)->withLabel($label);
            
            if (!$totp->verify($code)) {
                http_response_code(400);
                echo json_encode(['message' => 'Invalid verification code. Please try again.']);
                exit;
            }
            
            $user->enableTOTP($secret);
            
            http_response_code(200);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
        }
        break;

    case 'disable2FA':
        $password = $_POST['password'] ?? '';

        if (empty($password)) {
            http_response_code(400);
            echo json_encode(['message' => 'Password is required']);
            exit;
        }

        try {
            $userId = $_SESSION['user']['me'];
            $db = new DatabaseManager();
            $result = $db->query("SELECT password FROM users WHERE id = ?", [$userId]);
            
            if (!$result || !password_verify($password, $result[0]['password'])) {
                http_response_code(400);
                echo json_encode(['message' => 'Invalid password']);
                exit;
            }
            
            $user = new User($userId);
            $user->disableTOTP();
            
            http_response_code(200);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
