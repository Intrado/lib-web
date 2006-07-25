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
include_once("obj/PersonData.obj.php");
include_once("obj/PeopleList.obj.php");
include_once("obj/ListEntry.obj.php");
include_once("obj/RenderedList.obj.php");
include_once("obj/Rule.obj.php");
include_once("obj/FieldMap.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('viewcontacts')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if(isset($_GET['deleterule'])) {
	unset($_SESSION['contactrules'][(int)$_GET['deleterule']]);
	if(!count($_SESSION['contactrules']))
		$_SESSION['contactrules'] = false;
	redirect();
}

/****************** main list section ******************/

$f = "contacts";
$s = "main";
$reloadform = 0;

$fieldmaps = DBFindMany("FieldMap", "from fieldmap where customerid='" . $USER->customerid . "'");

if(CheckFormSubmit($f,$s))
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
			$fieldnum = GetFormData($f,$s,"newrulefieldnum");
			if ($fieldnum != "") {
				$type = GetFormData($f,$s,"newruletype");

				if ($type == "text")
					$logic = "and";
				else
					$logic = GetFormData($f,$s,"newrulelogical_$type");

				if ($type == "multisearch")
					$op = "in";
				else
					$op = GetFormData($f,$s,"newruleoperator_$type");

				$value = GetFormData($f,$s,"newrulevalue_" . $fieldnum);
				if (count($value) > 0) {
					$rule = new Rule();
					$rule->logical = $logic;
					$rule->op = $op;
					$rule->val = ($type == 'multisearch' && is_array($value)) ? implode("|",$value) : $value;
					$rule->fieldnum = $fieldnum;
					if(is_array($_SESSION['contactrules']))
					$_SESSION['contactrules'][] = $rule;
					else
						$_SESSION['contactrules'] = array($rule);
					$rule->id = array_search($rule, $_SESSION['contactrules']);
				}
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

	PutFormData($f,$s,"newrulefieldnum","");
	PutFormData($f,$s,"newruletype","text","text",1,50);
	PutFormData($f,$s,"newrulelogical_text","and","text",1,50);
	PutFormData($f,$s,"newrulelogical_multisearch","and","text",1,50);
	PutFormData($f,$s,"newruleoperator_text","sw","text",1,50);
	PutFormData($f,$s,"newruleoperator_multisearch","in","text",1,50);

}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "system:contacts";
$TITLE = 'Contact Database';

include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, 'showallcontacts','showallcontacts','showallcontacts'));

startWindow('Search for ' . help('System_Contacts',NULL,'blue'));
echo "Select your search criteria: ";
//ruleeditform expects $RULES to be set
if(CheckFormSubmit($f, 'showallcontacts'))
	$_SESSION['contactrules'] = array();
elseif(!isset($_SESSION['contactrules']) || is_null($_SESSION['contactrules']))
	$_SESSION['contactrules'] = false;

$RULES = &$_SESSION['contactrules'];
$RULEMODE = array('multisearch' => true, 'text' => true, 'reldate' => true);

include("ruleeditform.inc.php");


endWindow();
?>
<br>
<?
$list = new PeopleList();
$renderedlist = new RenderedList($list);
$renderedlist->setSearch($RULES);
$showpagemenu = true;

startWindow('Search Results ', 'padding: 3px;');

//contactlist.inc.php expects renderedlist, showpagemenu to be set

include("contactlist.inc.php");


endWindow();
EndForm();
include_once("navbottom.inc.php");

?>