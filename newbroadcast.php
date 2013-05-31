<?
/**
 * Handles routing of the user to the appropriate message sender client application
 * 
 * Nickolas Heckman
 * 05-30-2013
 */
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");

// get customer settings which may dictate destination
$allowOldMessageSender = getSystemSetting('_allowoldmessagesender', false);

// get user setting
$userOldMessageSender = $USER->getSetting('_allowoldmessagesender', false);

if ($allowOldMessageSender && $userOldMessageSender) {
	$redirectUrl = 'message_sender.php';
} else {
	$redirectUrl = 'messagesender.php';
}

$queryString = (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING']) ? "?" . $_SERVER['QUERY_STRING'] : "";
redirect($redirectUrl . $queryString);
?>