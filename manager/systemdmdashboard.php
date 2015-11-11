<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/formatters.inc.php");
require_once("dbmo//authserver/DmGroup.obj.php");
include_once("../inc/memcache.inc.php");

if (!$MANAGERUSER->authorized("systemdm"))
	exit("Not Authorized");


/////////////////////////////
// Display
/////////////////////////////
$TITLE = _L("Dashboard");
$PAGE = "dm:systemdmdashboard";

include_once("nav.inc.php");

startWindow(_L('Dashboard'));
?>
<a href="https://insights.newrelic.com/apps/accounts/379119/notification-dashboard" target="_blank">NewRelic Notification Dashboard</a><br/>

<iframe src="https://insights.newrelic.com/apps/accounts/379119/notification-dashboard"></iframe>
<?

endWindow();

include_once("navbottom.inc.php");
?>
