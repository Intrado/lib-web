<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/form.inc.php");
include_once("inc/text.inc.php");
include_once("inc/date.inc.php");
include_once("obj/Language.obj.php");
include_once("obj/Person.obj.php");
include_once("obj/Address.obj.php");
include_once("obj/Phone.obj.php");
include_once("obj/Email.obj.php");
include_once("obj/PeopleList.obj.php");
include_once("obj/AudioFile.obj.php");
include_once("obj/FieldMap.obj.php");
include_once("obj/Rule.obj.php");
include_once("obj/ListEntry.obj.php");
include_once("obj/RenderedList.obj.php");
include_once("ruleeditform.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createlist')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$list = NULL;

if (isset($_GET['id'])) {
	setCurrentList($_GET['id']);
	redirect();
}


if (isset($_GET['deleterule'])) {
	$ruleid = DBSafe($_GET['deleterule']);
	$listid = QuickQuery("select le.listid from listentry le where le.ruleid='$ruleid'");
	if (userOwns("list",$listid)) {
		QuickUpdate("delete from listentry where ruleid='$ruleid'");
		QuickUpdate("delete from rule where id='$ruleid'");
	}
	redirect();
}


if (isset($_GET['clearall'])) {
	if (isset($_SESSION['listid'])) {
		switch ($_GET['clearall']) {
			case "skips":
				QuickUpdate("delete from listentry where type='N' and listid='" . $_SESSION['listid'] . "'");
				break;
			case "adds":
				QuickUpdate("delete from listentry where type='A' and listid='" . $_SESSION['listid'] . "'");
				break;
			case "rules":
				QuickUpdate("delete from listentry where type='R' and listid='" . $_SESSION['listid'] . "'");
				break;
		}
	}
	redirect();
}


/****************** main message section ******************/

$f = "list";
$s = "main";
$reloadform = 0;

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f,'save') || CheckFormSubmit($f,'add') || CheckFormSubmit($f,'refresh') || CheckFormSubmit($f,'search') || CheckFormSubmit($f,'preview'))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data.');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		$name = trim(GetFormData($f,$s,"name"));
		if ( empty($name) ) {
			PutFormData($f,$s,"name",'',"text",1,50,true);
		}		
		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if (QuickQuery('select id from list where name = \'' . DBSafe($name) . "' and userid = $USER->id and deleted=0 and id != " . (0 + $_SESSION['listid']))) {
			error('A list named \'' . $name . '\' already exists');
		} else {
			//submit changes

			$list = new PeopleList($_SESSION['listid']);

			$list->name = $name;
			$list->description = trim(GetFormData($f,$s,"description"));
			
			$list->userid = $USER->id;
			$list->deleted = '0';
			$list->update();

			$fieldaddsubmit = false;
			if ($list->id) {
				//now see if there is a new list rule
				$fieldnum = GetFormData($f,$s,"newrulefieldnum");
				if ($fieldnum != "" && $fieldnum != -1) {
					$type = GetFormData($f,$s,"newruletype");
					$logic = GetFormData($f,$s,"newrulelogical_$type");
					$op = GetFormData($f,$s,"newruleoperator_$type");
					$value = GetFormData($f,$s,"newrulevalue_" . $fieldnum);
					if (count($value) > 0) {
						$rule = new Rule();
						$rule->logical = $logic;
						$rule->op = $op;
						$rule->val = ($type == 'multisearch' && is_array($value)) ? implode("|",$value) : $value;
						$rule->fieldnum = $fieldnum;

						$rule->create();

						$le = new ListEntry();
						$le->listid = $list->id;
						$le->type = "R";
						$le->ruleid = $rule->id;
						$le->create();
						$fieldaddsubmit = true;
					}
				}
			}

			$_SESSION['listid'] = $list->id;

			if (CheckFormSubmit($f,'save')) {
				redirect('lists.php');
			} elseif (CheckFormSubmit($f,'preview')) {
				redirect('showlist.php?id=' . $_SESSION['listid']);
			} elseif (CheckFormSubmit($f,'search')) {
				redirect('search.php');
			}

			//$reloadform = 1;
			//instead of reloading the form here, redirect to this page so popup windows don't double post
			redirect();
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);

	$list = new PeopleList($_SESSION['listid']);


	$fields = array(
				array("name","text",1,50,true),
				array("description","text",1,50,false)
				);
	PopulateForm($f,$s,$list,$fields);

	PutFormData($f,$s,"newrulefieldnum","");
	PutFormData($f,$s,"newruletype","text","text",1,50);
	PutFormData($f,$s,"newrulelogical_text","and","text",1,50);
	PutFormData($f,$s,"newrulelogical_multisearch","and","text",1,50);
	PutFormData($f,$s,"newruleoperator_text","eq","text",1,50);
	PutFormData($f,$s,"newruleoperator_multisearch","in","text",1,50);

}
//if we don't already have a list loaded, get one
if ($list == NULL)
	$list = new PeopleList($_SESSION['listid']);
$renderedlist = new RenderedList($list);


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:lists";
$TITLE = 'List Editor: ' . ($_SESSION['listid'] == NULL ? "New List" : escapehtml($list->name));

include_once("nav.inc.php");

$titles = array(	"id" => "ID",
					"name" => "Name",
					"phone" => "Phone",
					"email" => "Email",
					"address" => "Address"
					);

NewForm($f);
if (!$list->id)
	buttons(submit($f,'refresh','Save'));
else
	buttons(submit($f,'refresh','Refresh'),
		submit($f,'search','Search &amp; Add') , submit($f, 'preview','Preview'),submit($f,'save','Done'));

startWindow('List Information', 'padding: 3px;');
print 'Name: ';
NewFormItem($f,$s,"name","text", 20,50);
print '&nbsp;&nbsp;Description: ';
NewFormItem($f,$s,"description","text", 20,50);

if ($list->id) {
	$renderedlist->calcStats();
	print("&nbsp;&nbsp; Total People in List: <b>$renderedlist->total</b>");
}
endWindow();

print '<br>';

if (!$list->id) {
?>
	<div style="margin-left: 10px;"><img src="img/bug_important.gif" > Please name your list and then click Save to continue.</div><br>
<?
} else {

startWindow("List Content");
?>
<table border="0" cellpadding="3" cellspacing="0" width=100%>
	<tr>
		<th align="right" valign="top" class="windowRowHeader bottomBorder">Rules:<br><? print help('List_Rules'); ?></th>
		<td class="bottomBorder" style="padding: 5px;" valign="bottom">
		<a href="?clearall=rules" onclick="return confirm('Are you sure you want to clear all rules?');">Clear All</a>
<?
//ruleeditform expects $RULES to be set
$RULEMODE = array('multisearch' => true, 'text' => true, 'reldate' => true);
$RULES = DBFindMany("Rule", "from rule r,listentry le
			where le.ruleid=r.id and le.listid='" . $_SESSION['listid'] . "'" ,"r");
//include("ruleeditform.inc.php");
drawRuleTable($f, $s, false, true, true, true);

?>
		</td>
	</tr>

<?
$numAdd = 0;
$numSkip = 0;

if ($list->id) {
	$renderedlist->mode = "totals";
	$renderedlist->hasstats = false;//reset the totals stats
	$renderedlist->calcStats();
	$numAdd = $renderedlist->totaladded;
	$numSkip = $renderedlist->totalremoved;
}

// if list additions, then show them, otherwise hide section
if ($numAdd > 0) {
?>

	<tr>
		<th align="right" valign="top" class="windowRowHeader bottomBorder">Additions:<br><? print help('List_Additions'); ?></th>
		<td class="bottomBorder" style="padding: 5px;">
		<a href="?clearall=adds" onclick="return confirm('Are you sure you want to clear all additions?');">Clear All</a>
<?
if ($list->id) {
	$renderedlist->mode = "add";
	$renderedlist->hasstats = false;//reset the totals stats
	//$renderedlist->pagelimit = -1;
	$doscrolling = true;
	$showpagemenu = ($numAdd > $renderedlist->pagelimit);
	include("list.inc.php"); //expects $renderedlist, $showpagemenu to be set
}
?>
		</td>
	</tr>
<?
// end of list additions
}

// if list skips, then show them, otherwise hide section
if ($numSkip > 0) {
?>

	<tr>
		<th align="right" valign="top" class="windowRowHeader bottomBorder">Skip:<br><? print help('List_Skip'); ?></th>
		<td class="bottomBorder" style="padding: 5px;">
		<a href="?clearall=skips" onclick="return confirm('Are you sure you want to clear all skips?');">Clear All</a>
<?
if ($list->id) {
	$renderedlist->mode = "remove";
	$renderedlist->pagelimit = -1;
	$doscrolling = true;
	$showpagemenu = false;
	include("list.inc.php"); //expects $renderedlist, $showpagemenu to be set
}
?>
		</td>
	</tr>
<?
// end of list skips
}
?>

	<tr>
		<th align="right" valign="top" class="windowRowHeader">Search Database:<br><? print help('List_SearchAndAdd'); ?></th>
		<td style="padding: 5px;"><?= submit($f,'search','Search &amp; Add') ?></td>
	</tr>

	<tr>
		<th align="right" valign="top" class="windowRowHeader">Manual Add:<br><? print help('List_ManualAdd'); ?></th>
		<td style="padding: 5px;"><?= button("Enter Contacts",NULL,"addressmanualadd.php?id=new")?></td>
	</tr>

	<tr>
		<th align="right" valign="top" class="windowRowHeader">Address Book:<br><? print help('List_AddressBookAdd'); ?></th>
		<td style="padding: 5px;"><?= button("Open Address Book",NULL,"addressesmanualadd.php"); ?></td>
	</tr>

<? if ($USER->authorize('listuploadids') || $USER->authorize('listuploadcontacts')) { ?>
	<tr>
		<th align="right" valign="top" class="windowRowHeader">Upload List:<br><? print help('List_UploadList'); ?></th>
		<td style="padding: 5px;"><?= button("Upload List",NULL,"uploadlist.php"); ?></td>
	</tr>
<? } ?>
</table>
<?
endWindow();

}

buttons();
EndForm();
include_once("navbottom.inc.php");
?>