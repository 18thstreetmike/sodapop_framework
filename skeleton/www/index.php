<?php

// error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED & ~E_WARNING);
// ini_set('display_startup_errors', '1');
// ini_set('display_errors', '1');
// ini_set('html_errors', '1');

require('Sodapop/Application.php');

Sodapop_Application::getInstance()->initialize();

Sodapop_Application::getInstance()->run();