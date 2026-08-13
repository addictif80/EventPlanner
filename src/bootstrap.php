<?php

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/Core/helpers.php';

use App\Core\Session;

$config = require dirname(__DIR__) . '/config/config.php';

if ($config['app']['debug']) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
}

date_default_timezone_set('Europe/Paris');

Session::start();

return $config;
