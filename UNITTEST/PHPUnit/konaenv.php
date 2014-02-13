<?php

/**
 * konaenv.php - PHPUnit environment file for kona tests
 *
 * Anything that we need to set up and make available for many/all tests should go in here
 *
 * @package unittests
 * @author Sean M. Kelly, <skelly@schoolmessenger.com>
 * @version 1.0
 */

$GLOBALS['konadir'] = $konadir = dirname(dirname(dirname(__FILE__)));

// ...because this is missing from our php.ini
date_default_timezone_set('America/Los_Angeles');

// ...because our code spits out headers all kinds which breaks PHPUnit
// ... disabled because we don't have PECL apd compiled into our PHP version
// ref: http://php.net/manual/en/function.override-function.php
//override_function('header', '$string,$replace,$http_response_code', 'return();');

