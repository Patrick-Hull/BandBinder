<?php
require_once __DIR__ . '/../../lib/util_all.php';
header('Content-Type: application/json');

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
    $config['password_reset_enabled'] = $config['password_reset_enabled'] ?? '0';
    $config['two_factor_mandatory'] = $config['two_factor_mandatory'] ?? '0';
    
    http_response_code(200);
    echo json_encode($config);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['message' => $e->getMessage()]);
}
