<?php
use BitWeb\IdCard\Authentication\IdCardAuthentication;
chdir(dirname(dirname(__DIR__)));

// Autoload classes
include 'vendor/autoload.php';

session_start();

$redirectUrl = urldecode($_GET["redirectUrl"]);

$_SERVER[IdCardAuthentication::SSL_CLIENT_VERIFY] = IdCardAuthentication::SSL_CLIENT_VERIFY_SUCCESSFUL;
$_SERVER['SSL_CLIENT_S_DN'] = 'GN=Rain/SN=Ramm/serialNumber=39211192796/C=EST';

if (!IdCardAuthentication::isSuccessful()){
    $redirectUrl = '/id-card/no-card-found';
} else{
    IdCardAuthentication::login();
}
$headerStr = 'Location: ' . $redirectUrl;

header($headerStr);