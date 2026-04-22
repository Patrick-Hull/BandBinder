<?php
require_once __DIR__ . '/../../lib/util_all.php';
header('Content-Type: application/json');

$result['status'] = false;
$result['message'] = "Unknown Error";

try {
    $userData = Auth::UserLogin($_POST['username'], $_POST['password']);
    
    $db = new DatabaseManager();
    $configResult = $db->query("SELECT config_value FROM site_config WHERE config_key = '2fa_mandatory'");
    $twoFactorMandatory = $configResult && count($configResult) > 0 && $configResult[0]['config_value'] === '1';
    
    if ($userData->getTotpEnabled() || $twoFactorMandatory) {
        http_response_code(200);
        $result['status'] = true;
        $result['requiresTwoFactor'] = true;
        $result['userId'] = $userData->getIdUser();
        $result['message'] = "Two-factor authentication required";
    } else {
        Auth::SetSessionData($userData);
        http_response_code(200);
        $result['status'] = true;
        $result['message'] = "Login Successful";
    }
} catch (Exception $e) {
    $result['status'] = false;
    $result['message'] = $e->getMessage();
    http_response_code(401);
    echo json_encode($result);
    exit;
}



echo json_encode($result);
