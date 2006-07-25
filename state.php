<?
include_once("inc/common.inc.php");
include_once('inc/html.inc.php');
state($_GET['_state'], $_GET['_set'], $_GET['_page']);
header('Content-type: image/gif');
readfile('img/spacer.gif');
?>
