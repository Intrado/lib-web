<?
include_once("common.inc.php");

function state($field, $set = false, $page = false) {
	if (!isset($_SESSION['state']))
		$_SESSION['state'] = array();


	$pageindex = $page ? $page : $_SERVER['SCRIPT_NAME'];
	if (!isset($_SESSION['state'][$pageindex]))
			$_SESSION['state'][$pageindex] = array();

	if($set !== false)
	{
		$_SESSION['state'][$pageindex][$field] = $set;
	}
	return (isset($_SESSION['state'][$pageindex][$field]) ? $_SESSION['state'][$pageindex][$field] : false);
}

state($_GET['_state'], $_GET['_set'], $_GET['_page']);
header('Content-type: image/gif');
readfile('img/spacer.gif');
?>
