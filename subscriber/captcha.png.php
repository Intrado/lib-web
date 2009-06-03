<?
require_once("../jpgraph/jpgraph_antispam.php");

if (isset($_GET['iData'])) {
	$spam = new AntiSpam($_GET['iData']);
} else if (isset($_GET['len'])) {
	$spam = new AntiSpam();
	$spam->Rand($_GET['len']);
}

$spam->Stroke();
?>
