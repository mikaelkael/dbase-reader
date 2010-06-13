<?php

namespace Mkk\Tests;

ini_set('date.timezone', 'Europe/Paris');
error_reporting(E_ALL | E_STRICT);

require_once 'PHPUnit/Framework.php';
require_once 'PHPUnit/TextUI/TestRunner.php';

set_include_path(
    realpath(__DIR__ . '/../library')
    . PATH_SEPARATOR .
    get_include_path()
);