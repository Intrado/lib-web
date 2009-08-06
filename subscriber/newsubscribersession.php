<?
$isNotLoggedIn = 1;

require_once("common.inc.php");
require_once("../jpgraph/jpgraph_antispam.php");

subscriberCreateAnonymousSession();
doStartSession();
	
$captcha = new AntiSpam();
// NOTE captcha value is lowercase even when display shows uppercase letters, ValCaptcha made case-insensitive for this
$_SESSION['captcha'] = $captcha->Rand(5);

$_SESSION['codegen'] = 'reset';

redirect("newsubscriber.php");
?>