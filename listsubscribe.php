<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/Publish.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/User.obj.php");
require_once("inc/securityhelper.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('subscribe') || !userCanSubscribe('list'))
	redirect('unauthorized.php');

$SUBSCRIBETYPE = 'list';

require("subscribe.inc.php");

?>