<?
$SETTINGS = parse_ini_file("inc/settings.ini.php",true);

include_once("inc/db.inc.php");
require_once("inc/DBMappedObject.php");
include_once("obj/Content.obj.php");

$c = new Content();

$c->contenttype = $_SERVER['CONTENT_TYPE'];
$c->data = base64_encode($HTTP_RAW_POST_DATA);

$c->create();

echo $c->id;


?>