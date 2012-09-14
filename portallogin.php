<?
/**
 * Handle login requests via portalauth through AuthServer
 *
 * User: nrheckman
 * Date: 8/13/12
 * Time: 9:32 AM
 */
$isindexpage = true;
require_once("inc/common.inc.php");
require_once("inc/DBMappedObject.php");
require_once("obj/User.obj.php");
require_once("obj/Access.obj.php");

$doRedirect = false;
if (isset($_REQUEST["is_return"])) {
	doStartSession();
	$doRedirect = true;
	// useing the access token, request that authserver create a session for whoever is logged into portal
	$userid = loginViaPortalAuth($CUSTOMERURL, $_SERVER["REMOTE_ADDR"]);
	loadCredentials($userid);
	$redirectLoc = "index.php";
} else {
	// create a brand new session
	newSession();
	doStartSession();
	$doRedirect = true;
	$http = ($_SERVER["HTTPS"]?"https://":"http://");
	$redirectLoc = getPortalAuthAuthRequestTokenUrl($http. $_SERVER['SERVER_NAME']. $_SERVER['REQUEST_URI']. "?is_return");
}

$TITLE = "Portal Authentication Login";
include_once("logintop.inc.php");
?>

<div><h2>Please wait while we attempt to log you in...</h2></div>

<?if ($doRedirect) {?>
	<script type="text/javascript">
		!function ($) {
			window.location = "<?=addslashes($redirectLoc)?>";
		}(window.jQuery);
	</script>
<?}?>
<script type="text/javascript" src="script/jquery.1.7.2.min.js"></script>

<?
include_once("loginbottom.inc.php");
?>
