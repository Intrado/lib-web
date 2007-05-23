<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/html.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/table.inc.php");
include_once("inc/form.inc.php");
include_once("obj/Job.obj.php");
include_once("obj/JobLanguage.obj.php");
include_once("obj/JobType.obj.php");
include_once("obj/PeopleList.obj.php");
include_once("obj/Message.obj.php");
include_once("obj/MessagePart.obj.php");
include_once("obj/AudioFile.obj.php");
include_once("obj/FieldMap.obj.php");
include_once("obj/Language.obj.php");
include_once("obj/Schedule.obj.php");
include_once("inc/formatters.inc.php");
include_once("obj/Phone.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createrepeat')) {
	redirect('unauthorized.php');
}


$JOBTYPE = "repeating";

include("jobedit.inc.php");