<?
$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

include_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
include_once("../obj/Content.obj.php");

$tmpname = secure_tmpname("tmp","dmapicontent",".dat");
put_file_contents($tmpname,$HTTP_RAW_POST_DATA);
unset($data); // don't keep in memory

echo contentPut($tmpname,$_SERVER['CONTENT_TYPE'],false);

?>