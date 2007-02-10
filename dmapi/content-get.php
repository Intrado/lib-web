<?
$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

include_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
include_once("../obj/Content.obj.php");

$cmid = $_GET['cmid'] + 0;

if ($c = contentGet($cmid, false)) {
	list($contenttype,$data) = $c;

	header("Content-Type: " . $contenttype);
	echo $data;
}

?>