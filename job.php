<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/table.inc.php");
require_once("inc/form.inc.php");
require_once("obj/Job.obj.php");
require_once("obj/JobLanguage.obj.php");
require_once("obj/JobType.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/Schedule.obj.php");
require_once("inc/formatters.inc.php");
require_once("obj/Phone.obj.php");
require_once("obj/Sms.obj.php");
require_once("obj/Voice.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('sendphone') && !$USER->authorize('sendemail') && !$USER->authorize('sendprint') && !$USER->authorize('sendsms')) {
	redirect('unauthorized.php');
}


$JOBTYPE = "normal";

include("jobedit.inc.php");