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
doStartSession();

if (isset($_REQUEST["is_return"])) {
    // useing the access token, request that authserver create a session for whoever is logged into portal
    list($sessionid, $userid) = loginViaPortalAuth($token, $CUSTOMERURL, $_SERVER["REMOTE_ADDR"]);
} else {
    $portalauthRequestTokenUrl = getPortalAuthAuthRequestTokenUrl();
    redirect($portalauthRequestTokenUrl);
}

$TITLE = "Portal Authentication Login";
include_once("logintop.inc.php");

?><div><?=$sessionid. " - ". $userid?></div><?

include_once("loginbottom.inc.php");
?>
