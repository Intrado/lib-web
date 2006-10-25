<?
$time = microtime(true);

$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("../inc/sessiondata.inc.php");

include_once("XmlToArray.obj.php");

$xmlparser = new XmlToArray();
if (!$BFXML_DOC = $xmlparser->parse($HTTP_RAW_POST_DATA)) {
?>
	<bfxml>
		<error>Parse error.</error>
	</bfxml>
<?
} else {


	ob_start();

?>
	<bfxml>
<?
	foreach ($BFXML_DOC['children'] as $BFXML_ELEMENT) {
		switch($BFXML_ELEMENT['name']) {
			case "AUTH":
				include("auth.php");
				break;
			case "DMANNOUNCE":
				include("dmannounce.php");
				break;
			case "TASKREQUEST":
				include("taskrequest.php");
				break;
			case "CONTENTREQUEST":
				include("contentrequest.php");
				break;
			default:
?>
				<error>Unknown BFXML type</error>
<?
		}
	}
?>
	</bfxml>
<?
	$stuff = ob_get_flush();
	ob_end_flush();

	$fp = fopen("output.txt","a");
	fwrite($fp,"------" . date("Y-m-d H:i:s") . "------\n");
	fwrite($fp,$stuff);
	fwrite($fp,"time: " . (microtime(true) - $time) . "\n");
	fwrite($fp,"-------------------------------\n");
	fclose($fp);
}


?>