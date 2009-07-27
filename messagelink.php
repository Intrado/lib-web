<?
// Cannot use form.inc because no session has started

$isindexpage = true;
require_once("inc/common.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/auth.inc.php");


$code = '';
if (isset($_GET['s'])) {
	$code = $_GET['s'];
}

if ($SETTINGS['feature']['has_ssl']) {
	if ($IS_COMMSUITE)
		$secureurl = "https://" . $_SERVER["SERVER_NAME"] . "/messagelink.php?s=" . $code;
	/*CSDELETEMARKER_START*/
	else
		$secureurl = "https://" . $_SERVER["SERVER_NAME"] . "/$CUSTOMERURL/messagelink.php?s=" . $code;
	/*CSDELETEMARKER_END*/

	if ($SETTINGS['feature']['force_ssl'] && !isset($_SERVER["HTTPS"])) {
		redirect($secureurl);
	}
}

// find the message from authserver for this code
$messageid = loginMessageLink($code);


$TITLE = "Message";
//primary colors are pulled in login top
include_once("logintop.inc.php");
if (!$messageid) {
?>
	<table  style="color: #365F8D;" >
		<tr>
			<td>&nbsp;</td>
			<td>
				<div style="margin:5px; font-size:12px">
					Sorry, an error occurred.
				</div>
			</td>
		</tr>
	</table>
<?
} else {
?>
	<table  style="color: #365F8D;" >
		<tr>
			<td>&nbsp;</td>
			<td>
				<div style="margin:5px; font-size:12px">
					Your message id was found <?=$messageid?>
				</div>
			</td>
		</tr>
	</table>
<?
}
include_once("loginbottom.inc.php");
?>