<?php

// We're just going to dump all the GET/POST data that we received
// into a log file so that we can see what the request had in it;
// note that this is not thread-safe, so try not to send multiple
// concurrent requests to it, or strangeness may appear in the log.

$logfile = 'apispy.log';
if (! ($fout = fopen($logfile, 'a'))) die('failed to open log file');
date_default_timezone_set('America/Los_Angeles');

// Opening line with a separator/date to make reviewing the log easier
fwrite($fout, "\n\n" . str_repeat('-', 100) . "\n" . date('Y-m-d H:i:s') . "\n");

fwrite($fout, "_POST:\n" . print_r($_POST, true) . "\n\n");
fwrite($fout, "_GET:\n" . print_r($_GET, true) . "\n\n");
fwrite($fout, "_FILES:\n" . print_r($_FILES, true) . "\n\n");
fwrite($fout, "_SERVER:\n" . print_r($_SERVER, true) . "\n\n");
fwrite($fout, "_ENV:\n" . print_r($_ENV, true) . "\n\n");

fclose($fout);

