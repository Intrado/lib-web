<?
$isNotLoggedIn = 1;

require_once("common.inc.php");
require_once("../jpgraph/jpgraph_antispam.php");

doStartSession();

if (isset($_SESSION['captcha'])) {
	$spam = new AntiSpam($_SESSION['captcha']);
} else {
	error_log("SESSION Captcha value not set");
	$spam = new AntiSpam();
	$spam->Rand(5);
}

$spam->Stroke();
?>
