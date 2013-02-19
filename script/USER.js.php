<?

/**
 * Bring PHP USER data into the client via JavaScript (read only)
 */

require_once("../inc/subdircommon.inc.php");
require_once("../inc/html.inc.php");

//set expire time to + 5 minutes to minimize browser caching on this file
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 5 * 60) . " GMT");
header("Content-Type: text/javascript");
header("Cache-Control: private");
?>

(function () {
	user = function () {
		this.id = <?= intval($USER->id); ?>;
		this.firstname = <? echo json_encode($USER->firstname); ?>;
		this.lastname = <? echo json_encode($USER->lastname); ?>;
		this.login = <? echo json_encode($USER->login); ?>;
		this.email = <? echo json_encode($USER->email); ?>;
		this.phone = <? echo json_encode($USER->phone); ?>;
	}

	window.top.USER = new user();
}) ();

