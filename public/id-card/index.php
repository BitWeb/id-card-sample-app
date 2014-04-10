<?php
use BitWeb\IdCard\Authentication\IdCardAuthentication;

chdir(dirname(dirname(__DIR__)));

// Autoload classes
include 'vendor/autoload.php';
include 'init_autoloader.php';
Zend\Mvc\Application::init(require 'config/application.config.php');

$redirectUrl = urldecode($_GET["redirectUrl"]);

// for testing use the following to mock auth result
//$_SERVER[IdCardAuthentication::SSL_CLIENT_VERIFY] = IdCardAuthentication::SSL_CLIENT_VERIFY_SUCCESSFUL;
//$_SERVER['SSL_CLIENT_S_DN'] = 'GN=Mari-Liis/SN=Männik/serialNumber=47101010033/C=EST';

if (!IdCardAuthentication::isSuccessful()) {
    $redirectUrl = '/id-card/no-card-found';
} else {
    IdCardAuthentication::login();
}
$headerStr = 'Location: ' . $redirectUrl;

header($headerStr);
