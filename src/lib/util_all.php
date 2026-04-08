<?php

$startupMicroTime = microtime(true);


set_time_limit(12000);


date_default_timezone_set('Australia/Brisbane');
error_reporting(E_ALL | E_WARNING);


session_set_cookie_params(24 * 60 * 60 * 30); // 30 days?
// session_set_cookie_params(0); // session = until browser closes... hopefully
session_start();


//	no cache!
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Cache-Control: post-check=0, pre-check=0', FALSE);
header('Pragma: no-cache');

if(!isset($_SERVER['REMOTE_ADDR']))
{
    $_SERVER['REMOTE_ADDR'] = '';
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . "/class/DatabaseManager.php";
require_once __DIR__ . "/class/Auth.php";
require_once __DIR__ . "/class/User.php";
require_once __DIR__ . "/class/PsrClock.php";
require_once __DIR__ . "/class/Log.php";
require_once __DIR__ . "/class/Helper.php";

require_once __DIR__ . "/class/Instrument.php";
require_once __DIR__ . "/class/InstrumentFamily.php";
require_once __DIR__ . "/class/UserType.php";
require_once __DIR__ . "/class/Artist.php";
require_once __DIR__ . "/class/Arranger.php";
require_once __DIR__ . "/class/Chart.php";

$authUrl = [
    "/login.php",
    "/lib/login.php"
];



if(in_array($_SERVER['REQUEST_URI'], $authUrl))
    return;

if(isset($_SESSION['user']['me'])){
    /**
     * User Logged In
     */
    $_SESSION['user']['permissions'] = new User($_SESSION['user']['me'])->getSitePermissions();
} else {
    /**
     * User Not Logged In
     */
    header("Location: /login.php");
}