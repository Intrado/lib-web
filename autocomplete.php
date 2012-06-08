<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////

$SESSION_READONLY = true;

require_once("inc/common.inc.php");
require_once("inc/utils.inc.php");

?>
<ul>
<?

if (isset($_POST['test'])) {
	echo '<li>test1</li>
		<li>test2<span class="informal" style="color: gray;"> more info</span></li>
		<li>test3</li>
		<li>test4</li>
	';
} else {
	error_log("Unknown autocomplete request");
}

?>
</ul>