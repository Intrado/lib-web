<?
$SETTINGS = parse_ini_file("inc/settings.ini.php",true);

include_once("inc/db.inc.php");
require_once("inc/DBMappedObject.php");
include_once("obj/Content.obj.php");

$c = new Content($_GET['cmid']+0);

header("Content-Type: " . $c->contenttype);

echo base64_decode($c->data);

?>