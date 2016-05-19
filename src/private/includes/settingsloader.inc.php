<?php

global $incdir;

if (($SETTINGS = parse_ini_file("{$incdir}/settings.ini.php", true)) === false ) {
	if (isset($incdir)) {
		if (is_dir($incdir)) {
			error_log("Cannot read ini file \"$incdir/settings.ini.php\"");
		} else {
			error_log("Cannot find directory \"$incdir\"");
		}
	} else {
		error_log('Expecting $incdir to be set to a directory, but it is not set');
	}
}
