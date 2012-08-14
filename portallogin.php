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

if (isset($_REQUEST["is_return"]) && isset($_REQUEST["oauth_token"])) {
    // Get the authorized request token
    $authRequestToken = $_REQUEST["oauth_token"];
    // submit it to be turned into an access token
    list($token, $secret) = getPortalAuthAccessToken($authRequestToken);
    // useing the access token, request that authserver create a session for whoever is logged into portal
    list($sessionid, $userid) = loginViaPortalAuth($token, $CUSTOMERURL, $_SERVER["REMOTE_ADDR"]);

} else {
    $portalauthRequestTokenUrl = getPortalAuthAuthRequestTokenUrl("http://". $_SERVER['SERVER_NAME']. $_SERVER['REQUEST_URI']. "?is_return");
    redirect($portalauthRequestTokenUrl);
}

$TITLE = "Portal Authentication Login";
include_once("logintop.inc.php");

?><div><?=$sessionid. " - ". $userid?></div><?

include_once("loginbottom.inc.php");
?>