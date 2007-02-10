<?
$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

include_once("../inc/db.inc.php");
include_once("../inc/utils.inc.php");
include_once("../inc/content.inc.php");
require_once("../inc/DBMappedObject.php");
include_once("../obj/Content.obj.php");

$tmpname = secure_tmpname("../tmp","dmapicontent",".dat");
file_put_contents($tmpname,$HTTP_RAW_POST_DATA);

echo contentPut($tmpname,$_SERVER['CONTENT_TYPE'],false);

?>