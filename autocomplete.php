<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/utils.inc.php");

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

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