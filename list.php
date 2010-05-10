<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/form.inc.php");
require_once("inc/date.inc.php");
require_once("obj/Language.obj.php");
require_once("obj/Person.obj.php");
require_once("obj/Address.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Email.obj.php");
require_once("obj/Sms.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Rule.obj.php");
require_once("obj/ListEntry.obj.php");
require_once("obj/RenderedList.obj.php");
require_once("ruleeditform.inc.php");
require_once("inc/rulesutils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/ValSections.val.php");
require_once("obj/ValRules.val.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/FormRuleWidget.fi.php");
require_once("obj/SectionWidget.fi.php");
require_once("inc/rulesutils.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/JobType.obj.php");
require_once("inc/list.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

// Do Authentication in include file

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

$_SESSION['previewfrom'] = 'list.php';

// NOTE: editlist.inc.php will figure out which method to use.
include('editlist.inc.php');
?>