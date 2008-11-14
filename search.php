<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/form.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/date.inc.php");
include_once("obj/Person.obj.php");
include_once("obj/PeopleList.obj.php");
include_once("obj/ListEntry.obj.php");
include_once("obj/RenderedList.obj.php");
include_once("obj/Rule.obj.php");
include_once("obj/FieldMap.obj.php");
include_once("obj/Phone.obj.php");
include_once("ruleeditform.inc.php");
require_once("inc/rulesutils.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createlist')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if(isset($_GET['deleterule'])) {
	unset($_SESSION['searchrules'][(int)$_GET['deleterule']]);
	if(!count($_SESSION['searchrules']))
		$_SESSION['searchrules'] = false;
	redirect();
}

/****************** main list section ******************/

$f = "search";
$s = "main";
$reloadform = 0;

$fieldmaps = DBFindMany("FieldMap", "from fieldmap");

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f,'refresh'))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		print '<div class="warning">Form was edited in another window, reloading data.</div>';
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);
		//do check
		if( CheckFormSection($f, $s) )
		{
			print '<div class="warning">There was a problem trying to save your changes. <br> Please verify that all required field information has been entered properly.</div>';
		} else {
			//submit changes
			
			$rule = getRuleFromForm($f,$s);
			if ($rule != null) {
				if(is_array($_SESSION['searchrules']))
					$_SESSION['searchrules'][] = $rule;
				else
					$_SESSION['searchrules'] = array($rule);
				$rule->id = array_search($rule, $_SESSION['searchrules']);
			}

			$reloadform = 1;
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);

	//TODO add the extra rules
	//pkey, phone1-4, email1-2, address, etc


	foreach ($fieldmaps as $fieldmap) {
		if (!$fieldmap->isOptionEnabled("searchable"))
			continue;

		$fieldname = $fieldmap->name;
		$field = $fieldmap->fieldnum;

	}

	putRuleFormData($f, $s);


}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:lists";
$TITLE = 'List Search: ' . escapehtml(QuickQuery("select name from list where id = $_SESSION[listid]"));

include_once("nav.inc.php");

NewForm($f);
buttons(submit($f,'refresh','Refresh'), submit($f, 'showall','Show All Contacts'),button("Done","","list.php"));

startWindow('Search for ' . help('SearchList_SearchFor'));

//ruleeditform expects $RULES to be set
if(CheckFormSubmit($f, 'showall'))
	$_SESSION['searchrules'] = array();
elseif(!isset($_SESSION['searchrules']) || is_null($_SESSION['searchrules']))
	$_SESSION['searchrules'] = false;

$RULES = &$_SESSION['searchrules'];
$RULEMODE = array('multisearch' => true, 'text' => true, 'reldate' => true, 'numeric' => true);

drawRuleTable($f, $s, false, true, true, true);

endWindow();
?>
<br>
<?
$list = new PeopleList($_SESSION['listid']);
$renderedlist = new RenderedList($list);
$renderedlist->setSearch($RULES);
$renderedlist->pagelimit=500;
$showpagemenu = true;

startWindow('Search Results ' . help('SearchList_SearchResults'), 'padding: 3px;');

//list.inc.php expects renderedlist, showpagemenu to be set
include("list.inc.php");

endWindow();
buttons();
EndForm();
?>
<script SRC="script/calendar.js"></script>
<?

include_once("navbottom.inc.php");

?>
