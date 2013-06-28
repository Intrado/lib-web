<?

/**
 * sessionspy.php - Session Spy Developer Tool
 *
 * Allow developers to spy on and manipulate session data while operating the application in a separate window.
 *
 * @author Sean M. Kelly (skelly@schoolmessenger.com)
 * @date 2013-06-13
 */

// All the Kona scripts require pathing relative to root, or else they can't find themselves
$konaroot = realpath(dirname(__FILE__) . '/../..');
chdir($konaroot);

require_once("inc/common.inc.php");

?>
<html>
	<head>
		<title>SessionSpy (tm)</title>
		<style>
			div.notice {
				background-color: blue;
				color: white;
				padding: 10px;
				margin: 15px 75px;
				border: 1px solid black;
			}
		</style>
	<body>
		<a href="sessionspy.php">Refresh</a><br/><br/>

<?
if (isset($_REQUEST['action'])) switch ($_REQUEST['action']) {
	case 'set':
		$_SESSION[$_REQUEST['key']] = $_REQUEST['value'];
		print '<div class="notice">Value set for session key [' . $_REQUEST['key'] . "]</div><br/>\n";
		break;

	case 'del':
		unset($_SESSION[$_REQUEST['key']]);
		print '<div class="notice">Value deleted for session key [' . $_REQUEST['key'] . "]</div><br/>\n";
		break;
}

?>
		<table>
			<form method="POST">
				<tr>
					<td rowspan="4" style="vertical-align: top; padding-right: 50px; border-right: 1px solid #CCCCCC;">
						<h3>Quick Toggles</h3>
						&#149; DEBUG: <? print (isset($_SESSION['DEBUG']) ? '<a href="?action=del&key=DEBUG">DISable</a>' : '<a href="?action=set&key=DEBUG&value=1">ENable</a>'); ?><br/>
						&#149; OLDMS: <? print (isset($_SESSION['OLDMS']) ? '<a href="?action=del&key=OLDMS">DISable</a>' : '<a href="?action=set&key=OLDMS&value=1">ENable</a>'); ?><br/>
					</td>
					<td colspan="2">
						<h3>Set Session Data</h3>
					</td>
				</tr>
				<input type="hidden" name="action" value="set"/>
				<tr>
					<td>KEY</td>
					<td><input type="text" name="key" value=""/></td>
				</tr>
				<tr>
					<td>VALUE</td>
					<td><textarea name="value"></textarea></td>
				</tr>
				<tr>
					<td colspan="2"><input type="submit" value="SET IT!"/></td>
				</tr>
			</form>
		</table>

		<h3>Current Session Data</h3>
		<table cellspacing="10" border="1">
			<tr bgcolor="#CCCCCC">
				<td><b>Delete</b</td>
				<td><b>Key</b></td>
				<td><b>Value</b></td>
			</tr>

<?

$hasaccess = false;
foreach ($_SESSION as $key => $value) {

	// If this is the access structure, save it for last because
	// it is long, exhausting, and rarely consulted
	if ($key == 'access') {
		$hasaccess = true;
		continue;
	}

	switch ($valtype = gettype($value)) {
		case 'boolean':
			$val = $value ? 'true' : 'false';
			break;

		case 'integer':
		case 'double':
		case 'string':
			$val = $value;
			break;

		case 'NULL':
			$val = 'null';
			break;

		default:
			$val = "{$valtype}: " . print_r($value, true);
			break;
	}

	show_sess_data($key, $val);
}

// Now show the access data if there was any - good grief!
if ($hasaccess) {
	show_sess_data('access', print_r($_SESSION['access'], true));
}

function show_sess_data($key, $val) {
	print "<tr><td valign=\"top\"><a href=\"?action=del&key={$key}\">X</a></td><td class=\"left\" valign=\"top\">{$key}</td><td valign=\"top\"><pre>{$val}</pre></td></tr>\n";
}
?>
		</table>
	</body>
</html>

