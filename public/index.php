<?php
$startTime = microtime(true);

/**
 * This makes our life easier when dealing with paths. Everything is relative
 * to the application root now.
 */
chdir(dirname(__DIR__));

// Decline static file requests back to the PHP built-in webserver
if (php_sapi_name() === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

// Setup autoloading
require 'init_autoloader.php';

$errorService = new \BitWeb\ErrorReporting\Service\ErrorService(array(
    'subject' => '[Errors][id-card-sample-app]',
    'emails' => array (
        'rain@bitweb.ee'
    ),
    'from_address' => 'rain@bitweb.ee',
    'ignore404' => false,
    'ignoreBot404' => false,
    'botList' => array(
        'AhrefsBot',
        'bingbot',
        'Ezooms',
        'Googlebot',
        'Mail.RU_Bot',
        'YandexBot',
    ),
));
$errorService->startErrorHandling($startTime);

// Run the application!
Zend\Mvc\Application::init(require 'config/application.config.php')->run();

$errorService->endErrorHandling();