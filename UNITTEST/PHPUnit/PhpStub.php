<?php

/**
 * Stubbing out some problematic PHP functions used in the application that we
 * need to have under our control for test purposes.
 *
 * NOTE: requires PECL, 'runkit':
 *
 * pecl install https://github.com/downloads/zenovich/runkit/runkit-1.0.3.tgz
 * mv /usr/commsuite/server/php//lib/php/extensions/no-debug-non-zts-20090626/runkit.so /usr/commsuite/server/php/lib/php/extensions/runkit.so
 * rmdir /usr/commsuite/server/php//lib/php/extensions/no-debug-non-zts-20090626
 *
 * ; Add these to /usr/commsuite/server/php/lib/php.ini
 * extension=runkit.so
 * runkit.internal_override=1
 *
 */

$HEADERS = array();

function stub_header($text) {
	global $HEADERS;

	$HEADERS[] = $text;
}

if (function_exists('runkit_function_rename')) {
	runkit_function_rename('header', 'orig_header');
	runkit_function_rename('stub_header', 'header');
}
else {
	print "ERROR in PhpStub.php - it looks like PECL runkit is not installed! Check out the code comments here.\n";
}

