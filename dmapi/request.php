<?
$time = microtime(true);

$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);
$IS_COMMSUITE = $SETTINGS['feature']['is_commsuite'];

require_once("../inc/auth.inc.php");
require_once("dmapidb.inc.php");
require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("../inc/sessiondata.inc.php");
require_once("../inc/utils.inc.php");


include_once("XmlToArray.obj.php");

if ($SETTINGS['feature']['log_dmapi'])
	ob_start();

$xmlparser = new XmlToArray();
if (!$BFXML_DOC = $xmlparser->parse($HTTP_RAW_POST_DATA)) {
?>
	<bfxml>
		<error>Parse error.</error>
	</bfxml>
<?
} else {
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
}

	if ($SETTINGS['feature']['log_dmapi']) {
		$rawoutput = ob_get_flush();

		$logfilename = $SETTINGS['feature']['log_dir'] . "output.txt";

		//rotate log?

		if (file_exists($logfilename) && filesize($logfilename) > 1000000000) {
			if (file_exists($logfilename . ".1"))
				unlink($logfilename . ".1");
			rename($logfilename,$logfilename . ".1");
		}

		$fp = fopen($logfilename,"a");
		fwrite($fp,"------" . date("Y-m-d H:i:s") . "------\n");
		fwrite($fp,$HTTP_RAW_POST_DATA);
		fwrite($fp,"-------------RESPONSE----------\n");
		fwrite($fp,$rawoutput);
		fwrite($fp,"time: " . (microtime(true) - $time) . "\n");
		fwrite($fp,"-------------------------------\n");
		fclose($fp);
	}
?>