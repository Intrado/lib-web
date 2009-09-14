<?
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/css");
header("Cache-Control: private");

if (!isset($_GET["nocommoninc"])) {
	include_once("inc/common.inc.php");
	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
}

include_once("css/css.inc.php");
?>
