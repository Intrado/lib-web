<?php

global $incdir, $basedir;

$iniPath = dirname($basedir) . "/settings.ini";
if (($SETTINGS = parse_ini_file($iniPath, true)) === false ) {
	if (isset($basedir)) {
		if (is_dir($basedir)) {
			error_log("Cannot read ini file \"{$iniPath}\"");
		} else {
			error_log("Cannot find directory \"$basedir\"");
		}
	} else {
		error_log('Expecting $basedir to be set to a directory, but it is not set');
	}
}
