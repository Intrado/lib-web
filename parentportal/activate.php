<?
// activate.php link was emailed to users prior to ASP_9-6 release
// forward all requests to new portalauth login, they can recreate a forgotten password, or re-register

$ppNotLoggedIn = 1;
require_once("common.inc.php");

// redirect to portalauth
redirect($SETTINGS['portalauth']['cmUrl']);

?>