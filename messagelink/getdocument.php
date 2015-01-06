<?

// Only non-password protected documents may be retrieved with this script - prevents password leakage in the URL
if (! isset($_GET['s']) || !isset($_GET['mal'])) {
	header("HTTP/1.0 400 Bad Request");
	exit();
}

require_once('apiGetDocument.php');
apiGetDocument($_GET['s'], $_GET['mal'], null);

