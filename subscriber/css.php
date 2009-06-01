<?
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/css");
header("Cache-Control: private");

// skipcommon for login page, no session to keep
if (!isset($_GET['skipcommon'])) {
	include_once("common.inc.php");
	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
}

require_once("../css/css.inc.php");
?>
