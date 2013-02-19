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
		this.id = <?= $USER->id; ?>;
		this.firstname = '<?= $USER->firstname; ?>';
		this.lastname = '<?= $USER->lastname; ?>';
		this.login = '<?= $USER->login; ?>';
		this.email = '<?= $USER->email; ?>';
		this.phone = '<?= $USER->phone; ?>';
	}

	window.top.USER = new user();
}) ();

