<?
/**
 * Handles routing of the user to the appropriate message sender client application
 * 
 * Nickolas Heckman
 * 05-30-2013
 */
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");

$redirectUrl = 'message_sender.php';

$queryString = (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING']) ? "?" . $_SERVER['QUERY_STRING'] : "";
redirect($redirectUrl . $queryString);
?>
