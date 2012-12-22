<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/form.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/formatters.inc.php");
require_once("inc/date.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/rulesutils.inc.php");
require_once("obj/Person.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/ListEntry.obj.php");
require_once("obj/RenderedList.obj.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/Rule.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Email.obj.php");
require_once("obj/Sms.obj.php");
require_once("obj/FormRuleWidget.fi.php");
require_once("obj/SectionWidget.fi.php");
require_once("obj/ValSections.val.php");
require_once("obj/ValRules.val.php");
require_once("obj/Address.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/JobType.obj.php");
require_once("obj/UserSetting.obj.php");


require_once("inc/reportutils.inc.php"); //used by list.inc.php
require_once("inc/list.inc.php");
require_once("ruleeditform.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createlist')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////



//handle list search mode switches (contactsearchformdata.inc.php)
if (isset($_GET['listsearchmode'])) {

	if ($_GET['listsearchmode'] == "rules" && !isset($_SESSION['listsearch']['rules'])) {
		unset($_SESSION['listsearch']); //defaults to rules mode with no search criteria
	}
	
	if ($_GET['listsearchmode'] == "individual" && !isset($_SESSION['listsearch']['individual'])) {
		$_SESSION['listsearch'] = array ("individual" => array ("quickaddsearch" => ''));
	}
	
	if ($_GET['listsearchmode'] == "sections" && !isset($_SESSION['listsearch']['sectionx'])) {
		$_SESSION['listsearch'] = array ("sectionids" => array ());
	}
	
	if ($_GET['listsearchmode'] == "showall" && !isset($_SESSION['listsearch']['showall'])) {
		$_SESSION['listsearch'] = array("showall" => true);
	}
}

//get the list to edit from the request params or session
if (isset($_GET['new'])) {
	
	$quickpicklist = new PeopleList();
	$quickpicklist->name = "QuickPick";
	$quickpicklist->description = "Created in MessageSender";
	$quickpicklist->deleted = 1;
	$quickpicklist->modifydate = date("Y-m-d H:i:s");
	$quickpicklist->userid = $USER->id;
	$quickpicklist->type = 'person';
	
	$quickpicklist->create();
	
	setCurrentList($quickpicklist->id);
	
	// NOTE: maintaing previous behavior while removing errors from httpd log files. See bug:4605
	$referer = (isset($_SERVER['HTTP_REFERER'])?$_SERVER['HTTP_REFERER']:NULL);
	$_SESSION['listreferer'] = $referer;
	redirect();
}

handle_list_checkbox_ajax(); //for handling check/uncheck from the list

$list = new PeopleList($_SESSION['listid']);

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$renderedlist = new RenderedList2();
$renderedlist->pagelimit = 100;

// buttons must be defined before include 'contactsearchformdata.inc'
$buttons = array();

// variable for page redirect, used by include 'contactsearchformdata.inc'
$redirectpage = "searchquickadd.php";

include_once("contactsearchformdata.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

header('Content-type: text/html; charset=UTF-8') ;

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
	<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge" />
	
	<script src="script/prototype.js" type="text/javascript"></script> <!-- updated to prototype 1.7 -->
	<script src="script/scriptaculous.js" type="text/javascript"></script>
	<script src="script/prototip.js.php" type="text/javascript"></script>
	<script src="script/utils.js"></script>
	<script src="script/sorttable.js"></script>
	<script src="script/form.js.php" type="text/javascript"></script>
	<script src="script/livepipe/livepipe.js" type="text/javascript"></script>
	<script src="script/livepipe/window.js" type="text/javascript"></script>
	<script src="script/modalwrapper.js" type="text/javascript"></script>
	
	<link href="css.php?hash=<?=crc32(serialize($_SESSION['colorscheme']))?>" type="text/css" rel="stylesheet" media="screen, print" />
	<link href="css.forms.php?hash=<?=crc32(serialize($_SESSION['colorscheme']))?>" type="text/css" rel="stylesheet" media="screen, print" />
	<link href="css/datepicker.css.php" type="text/css" rel="stylesheet" />
	<link href="css/newui_datepicker.css" type="text/css" rel="stylesheet" />
	<link href="css/prototip.css.php" type="text/css" rel="stylesheet" />
	<link href="css/style_print.css" type="text/css" rel="stylesheet" media="print" />
	
	<!--[if IE 8]>
		<script src="script/respond.min.js" type="text/javascript"></script>
	<![endif]-->
	
</head>


<body style="margin: 0px; background-color: white;" onBeforeUnLoad="if(typeof(unloadsession) != 'undefined') {unloadsession();}">
	<script>
		var _brandtheme = "<?=getBrandTheme();?>";
	</script>
<? 
//load validator for rules, handle rule add/delete to form submit (contactsearchformdata.inc.php)
?>
	<script type="text/javascript">
		<? Validator::load_validators(array("ValSections", "ValRules")); ?>

		function rulewidget_add_rule(event) {
			$('listsearch_ruledata').value = Object.toJSON(event.memo.ruledata);
			form_submit(event, 'addrule');
		}

		function rulewidget_delete_rule(event) {
			$('listsearch_ruledata').value = event.memo.fieldnum;
			form_submit(event, 'deleterule');
		}

		document.observe('dom:loaded', function() {
			if (window.ruleWidget) {
				ruleWidget.delayActions = true;
				ruleWidget.container.observe('RuleWidget:AddRule', rulewidget_add_rule);
				ruleWidget.container.observe('RuleWidget:DeleteRule', rulewidget_delete_rule);
			}
		});
	</script>
	
<div class="content_wrap">
<div class="content">
<?
startWindow("Search Options");

echo $form->render();

endWindow();

startWindow("Search Results");
?>

<div id="renderedlistcontent">
<? 
if ($hassomesearchcriteria)
	showRenderedListTable($renderedlist, $list);
else
	echo "<h2>Select some search options to begin.</h2>";
?>
</div>
<?
endWindow();
?>
</div><!-- end content -->
</div><!-- end content_wrap -->
</body>
</html>
