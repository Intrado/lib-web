<?

if (!isset($_POST['s']) || !isset($_POST['mal'])) {
	header("HTTP/1.0 400 Bad Request");
	exit();
}

require_once('apiGetDocument.php');
apiGetDocument($_POST['s'], $_POST['mal'], ((isset($_POST['v']) && $_POST['v'] == true) ? $_POST['p'] : null));

