<?php
require_once __DIR__ . '/../../lib/util_all.php';
header('Content-Type: application/json');

$result['status'] = false;
$result['message'] = "Unknown Error";

$clock = New PsrClock();

try {
    $userData = Auth::UserLogin($_POST['username'], $_POST['password']);
    Auth::SetSessionData($userData);
    http_response_code(200);
    $result['status'] = true;
    $result['message'] = "Login Successful";
} catch (Exception $e) {
    $result['status'] = false;
    $result['message'] = $e->getMessage();
    http_response_code(401);
    echo json_encode($result);
    exit;
}



echo json_encode($result);
