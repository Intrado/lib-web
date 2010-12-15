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

$PAGETIME = microtime(true);

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createlist')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

//get the list to edit from the request params or session
if (isset($_GET['id'])) {
	setCurrentList($_GET['id']);
	unset($_SESSION['listsearch']);
	$_SESSION['listreferer'] = $_SERVER['HTTP_REFERER'];
	redirect();
}

if (isset($_GET['showall']))
	$_SESSION['listsearch'] = array("showall" => true);

handle_list_checkbox_ajax(); //for handling check/uncheck from the list



$list = new PeopleList($_SESSION['listid']);

$renderedlist = new RenderedList2();
$renderedlist->pagelimit = 100;
$initedrenderedlist = false;


////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$formdata = array(
	"quickaddsearch" => array(
		"label" => _L("Search"),
		"value" => "",
		"validators" => array(
			array("ValLength","min" => 2, "max" => 255)
		),
		"control" => array("TextField", "size" => 30),
		"helpstep" => 1
	)
);

$buttons = array(
	submit_button(_L('Search'),"search","find"),
);

$form = new Form('quickaddsearch',$formdata,array(),$buttons);

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

$form->handleRequest();

$datachange = false;
$errors = false;

//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response	
	
	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData();		
	
		$renderedlist->initWithQuickAddSearch($postdata['quickaddsearch']);
		$_SESSION['quickaddsearch'] = $postdata['quickaddsearch'];
		
		Query("COMMIT");
		if ($ajax) {
			//get a copy of rendered list output, return in a form modify json
			
			$renderedlisthtml = "";
			if ($renderedlist->getTotal() > 0) {
				ob_start();
				$_GET['pagestart'] = 0; //override previous paging offsets which are still stuck in the GET query
				showRenderedListTable($renderedlist, $list);
				$renderedlisthtml = ob_get_clean();
				ob_end_clean();
			} else {
				$renderedlisthtml = '<div style="margin: 15px;"><h2>No records match that search</h2>
					<img src="img/bug_lightbulb.gif" alt="Tip">You may enter a name, phone number, email address, or ID #. 
					You may also enter both a first and last name to narrow the search.</div>
				';
			}
			if (isset($PAGETIME)) error_log(sprintf("<!-- %0.2f -->", microtime(true) - $PAGETIME)); 
			$form->modifyElement("quickaddcontent", $renderedlisthtml);
		} else {
			//else fall through and display via regular post
			$initedrenderedlist = true; //will show results in page
		}
	}
}


if (!$initedrenderedlist && isset($_SESSION['quickaddsearch']) && strlen($_SESSION['quickaddsearch']) >= 2) {
	$initedrenderedlist = true;
	$renderedlist->initWithQuickAddSearch($_SESSION['quickaddsearch']);
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:lists";
$TITLE = "List Quick Add: " . escapehtml($list->name);
require_once("nav.inc.php");


startWindow("Quick Add");

echo $form->render();

?>
<div id="quickaddcontent">
<? 
if ($initedrenderedlist) { 
	showRenderedListTable($renderedlist, $list);
} else {
?>
<div style="margin: 15px;">
<h2>Start typing in the search box to begin.</h2>
<img src="img/bug_lightbulb.gif" alt="Tip">You may enter a name, phone number, email address, or ID #. You may also enter both a first and last name to narrow the search.
</div>
<?
}
?>
</div>
<?

endWindow();

require_once("navbottom.inc.php");
?>
