<?php
/*

Strips unnecessary whitespace and comments from HTML.

*/

if ($argc < 2)
	exit("Please specify file name");

$files = $argv;
array_shift($files); //remove php script

$patterns = array(
	"/<!-- ([^-]+)-->/" => "",
	"/ +/" => " ",
	"/\n/" => "",
	"/\r/" => "",
	"/> /" => ">",
	"/ </" => "<"
);


foreach ($files as $filename) {
	echo "doing $filename...";
	$doc = file_get_contents($filename);

	$originalSize = strlen($doc);

	foreach ($patterns as $p => $r) {
		$doc = preg_replace($p, $r, $doc);
	}

	echo " was " . number_format($originalSize) . " now " . number_format(strlen($doc)) . "\n";

	file_put_contents($filename, $doc);


}

?>
