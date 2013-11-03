<?php

error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);
ini_set('display_errors', '1');
ini_set('html_errors', '1');


require_once('Sodapop/Application.php');

ini_set('unserialize_callback_func', '__unserialize');


if(strpos($_SERVER['REQUEST_URI'], '.php') !== false) {
    header('Location: /');
    exit;
}

// determine the environment
$environment = 'production';
if (getenv('APPLICATION_ENVIRONMENT') && in_array(strtolower(getenv('APPLICATION_ENVIRONMENT')), array('production', 'testing', 'development'))) {
    $environment = strtolower(getenv('APPLICATION_ENVIRONMENT'));
}

if ($environment == 'production') {
    ini_set('display_errors', '0');
}

// load the config file
$config = array();
if (file_exists('../configuration/configuration.ini')) {
    $parsedConfig = parse_ini_file('../configuration/configuration.ini', true);
    if ($parsedConfig) {
	$config = $parsedConfig;
    }
}

// instantiate the application
$application = new Sodapop_Application($environment, $config);


session_start();
$application->initUser();

$application->bootstrap()->run();
