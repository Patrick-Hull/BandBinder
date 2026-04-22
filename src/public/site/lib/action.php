<?php
require_once __DIR__ . '/../../../lib/util_all.php';
header('Content-Type: application/json');

if(!isset($_SESSION['user'])) {
    http_response_code(401);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

switch($action) {

    case 'getSiteConfig':
        if(!in_array('siteconfig', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }

        try {
            $db = new DatabaseManager();
            $result = $db->query("SELECT config_value FROM site_config WHERE config_key = 'mail_settings'");
            if ($result && count($result) > 0) {
                $row = $result[0];
                $config = json_decode($row['config_value'], true);
                // Ensure all expected keys exist with defaults
                $defaults = [
                    'protocol' => 'smtp',
                    'smtp_host' => 'localhost',
                    'smtp_port' => 25,
                    'smtp_username' => '',
                    'smtp_password' => '',
                    'from_email' => 'no-reply@example.com',
                    'from_name' => 'BandBinder',
                    'mailgun_api_key' => '',
                    'mailgun_domain' => ''
                ];
                $config = array_merge($defaults, $config ?? []);
                http_response_code(200);
                echo json_encode(['success' => true, 'config' => $config]);
            } else {
                // Return defaults if no config found
                $defaults = [
                    'protocol' => 'smtp',
                    'smtp_host' => 'localhost',
                    'smtp_port' => 25,
                    'smtp_username' => '',
                    'smtp_password' => '',
                    'from_email' => 'no-reply@example.com',
                    'from_name' => 'BandBinder',
                    'mailgun_api_key' => '',
                    'mailgun_domain' => ''
                ];
                http_response_code(200);
                echo json_encode(['success' => true, 'config' => $defaults]);
            }
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }
        break;

    case 'updateSiteConfig':
        if(!in_array('siteconfig', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }

        $config = $_POST['config'] ?? null;
        if(!is_array($config)){
            http_response_code(400);
            echo json_encode(['message' => 'Invalid configuration data']);
            exit;
        }

        // Validate required fields based on protocol
        $protocol = $config['protocol'] ?? 'smtp';
        if ($protocol === 'smtp') {
            if (empty($config['smtp_host']) || empty($config['smtp_port'])) {
                http_response_code(400);
                echo json_encode(['message' => 'SMTP host and port are required']);
                exit;
            }
        } elseif ($protocol === 'mailgun') {
            if (empty($config['mailgun_api_key']) || empty($config['mailgun_domain'])) {
                http_response_code(400);
                echo json_encode(['message' => 'Mailgun API key and domain are required']);
                exit;
            }
        }

        if (empty($config['from_email'])) {
            http_response_code(400);
            echo json_encode(['message' => 'From email is required']);
            exit;
        }

        try {
            $db = new DatabaseManager();
            $jsonConfig = json_encode($config);
            $db->query(
                "INSERT INTO site_config (config_key, config_value) VALUES ('mail_settings', ?) 
                 ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_at = CURRENT_TIMESTAMP",
                [$jsonConfig]
            );
            http_response_code(200);
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }
        break;

    case 'testSmtpConfig':
        if(!in_array('siteconfig', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }

        try {
            $db = new DatabaseManager();
            $result = $db->query("SELECT config_value FROM site_config WHERE config_key = 'mail_settings'");
            if ($result && count($result) > 0) {
                $row = $result[0];
                $config = json_decode($row['config_value'], true);
            } else {
                http_response_code(400);
                echo json_encode(['message' => 'No mail configuration found']);
                exit;
            }

            // Get the logged in user's email
            $userEmail = $_SESSION['user']['email'] ?? '';
            if (empty($userEmail)) {
                http_response_code(400);
                echo json_encode(['message' => 'Cannot determine logged in user email']);
                exit;
            }

            $mail = new PHPMailer(true);
            $protocol = $config['protocol'] ?? 'smtp';
            
            if ($protocol === 'mailgun') {
                $mail->isMailgun();
                $mail->Mailgun = array(
                    'api_key' => $config['mailgun_api_key'] ?? '',
                    'domain'  => $config['mailgun_domain'] ?? ''
                );
            } else {
                $mail->isSMTP();
                $mail->Host       = $config['smtp_host'] ?? 'localhost';
                $mail->Port       = $config['smtp_port'] ?? 25;
                $mail->SMTPAuth   = !empty($config['smtp_username']) || !empty($config['smtp_password']);
                $mail->Username   = $config['smtp_username'] ?? '';
                $mail->Password   = $config['smtp_password'] ?? '';
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->SMTPAutoTLS = false;
            }
            
            $mail->setFrom($config['from_email'] ?? 'no-reply@example.com', 
                          $config['from_name'] ?? 'BandBinder');
            $mail->addAddress($userEmail);
            $mail->Subject = 'BandBinder SMTP Test';
            $mail->msgHTML('<p>This is a test email from BandBinder to verify your mail configuration is working correctly.</p>');
            
            $mail->send();
            
            http_response_code(200);
            echo json_encode(['success' => true, 'message' => "Test email sent to $userEmail"]);
        } catch (Exception $e) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
        break;

    case 'getSecurityConfig':
        if(!in_array('siteconfig', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }

        try {
            $db = new DatabaseManager();
            $result = $db->query("SELECT config_key, config_value FROM site_config WHERE config_key IN ('password_reset_enabled', '2fa_mandatory')");
            $config = [];
            foreach ($result as $row) {
                if ($row['config_key'] === 'password_reset_enabled') {
                    $config['password_reset_enabled'] = $row['config_value'];
                } elseif ($row['config_key'] === '2fa_mandatory') {
                    $config['two_factor_mandatory'] = $row['config_value'];
                }
            }
            // Default values if not set
            $config['password_reset_enabled'] = $config['password_reset_enabled'] ?? '0';
            $config['two_factor_mandatory'] = $config['two_factor_mandatory'] ?? '0';
            http_response_code(200);
            echo json_encode(['success' => true, 'config' => $config]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['message' => $e->getMessage()]);
            exit;
        }
        break;

    case 'updateSecurityConfig':
        if(!in_array('siteconfig', $_SESSION['user']['permissions'])){
            http_response_code(403);
            echo json_encode(['error' => 'Permission denied']);
            exit;
        }

        $config = $_POST['config'] ?? null;
        if(!is_array($config)){
            http_response_code(400);
            echo json_encode(['message' => 'Invalid configuration data']);
            exit;
        }

        try {
            $db = new DatabaseManager();
            $passwordResetEnabled = $config['password_reset_enabled'] ?? '0';
            $twoFactorMandatory = $config['two_factor_mandatory'] ?? '0';

            $db->query(
                "INSERT INTO site_config (config_key, config_value) VALUES ('password_reset_enabled', ?) 
                 ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_at = CURRENT_TIMESTAMP",
                [$passwordResetEnabled]
            );
            $db->query(
                "INSERT INTO site_config (config_key, config_value) VALUES ('2fa_mandatory', ?) 
                 ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), updated_at = CURRENT_TIMESTAMP",
                [$twoFactorMandatory]
            );
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