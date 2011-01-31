<?
require_once("inc/utils.inc.php");

$code = '';

if (isset($_GET['s']))
	$code = $_GET['s'];

redirect('unsubscribeemail.php?s=' . $code);

?>