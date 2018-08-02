<?php

use Phalcon\Loader;

$loader = new Loader();

/**
 * Register Namespaces
 */
$loader->registerNamespaces([
    'Route\Models' => APP_PATH . '/common/models/',
    'Route'        => APP_PATH . '/common/library/',
]);

/**
 * Register module classes
 */
$loader->registerClasses([
    'Route\Modules\Frontend\Module' => APP_PATH . '/modules/frontend/Module.php',
    'Route\Modules\Cli\Module'      => APP_PATH . '/modules/cli/Module.php'
]);

$loader->register();

require_once APP_PATH . '/common/library/Requests/library/Requests.php';
Requests::register_autoloader();