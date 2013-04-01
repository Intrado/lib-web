<html>
	<head>
		<style type="text/css">
			img {
				margin-right: 5px;
				border: none;
			}
		</style>
	</head>
	<body>
<?php

$rel = "img/icons/";
$path = realpath(dirname(__FILE__) . "/{$rel}");
if ($d = opendir($path)) {
print "PNG Icons in [{$path}]:<br/>\n";
	while (false !== ($dirent = readdir($d))) {
		if (! preg_match("/\.png$/", $dirent)) continue;
print "<img src=\"{$rel}{$dirent}\" alt=\"{$dirent}\" title=\"{$dirent}\"/>";
	}
	closedir($d);
}
?>
	</body>
</html>
