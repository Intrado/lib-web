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
require_once("obj/PeopleList.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Rule.obj.php");
require_once("obj/ListEntry.obj.php");
require_once("obj/RenderedList.obj.php");
require_once("ruleeditform.inc.php");
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

$list = NULL;

if (isset($_GET['origin'])) {
	$_SESSION['origin'] = trim($_GET['origin']);
}

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

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f,'save') || CheckFormSubmit($f,'add') || CheckFormSubmit($f,'refresh') || CheckFormSubmit($f,'search') || CheckFormSubmit($f,'preview') || CheckFormSubmit($f,'manualAdd') || CheckFormSubmit($f,'addressBookAdd') || CheckFormSubmit($f,'uploadList'))
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
			$list->deleted = 0;
			$list->update();

			$fieldaddsubmit = false;
			if ($list->id) {
				//now see if there is a new list rule
				$rule = getRuleFromForm($f,$s);
				if ($rule != null) {
					$rule->create();

					$le = new ListEntry();
					$le->listid = $list->id;
					$le->type = "R";
					$le->ruleid = $rule->id;
					$le->create();
					$fieldaddsubmit = true;
				}
			}

			$_SESSION['listid'] = $list->id;

			if (CheckFormSubmit($f,'save')) {
				if (isset($_SESSION['origin']) && ($_SESSION['origin'] == 'start')) {
					unset($_SESSION['origin']);
					redirect('start.php');
				} else {
					unset($_SESSION['origin']);
					redirect('lists.php');
				}
			} elseif (CheckFormSubmit($f,'preview')) {
				redirect('showlist.php?id=' . $_SESSION['listid']);
			} elseif (CheckFormSubmit($f,'search')) {
				redirect('search.php');
			} elseif (CheckFormSubmit($f,'manualAdd')) {
				redirect('addressmanualadd.php?id=new');
			} elseif (CheckFormSubmit($f,'addressBookAdd')) {
				redirect('addressesmanualadd.php');
			} elseif (CheckFormSubmit($f,'uploadList')) {
				redirect('uploadlist.php');
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

	putRuleFormData($f, $s);
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
	buttons(submit($f,'refresh','Refresh'),submit($f,'save','Done'));

startWindow('List Information');
?>
<table border="0" cellpadding="3" cellspacing="0" width=100%>
	<tr>
		<th align="right" valign="top" class="windowRowHeader">List Name:</th>
		<td style="padding: 5px;" valign="bottom">
			<?
			NewFormItem($f,$s,"name","text", 20,50);
			?>
		</td>
	</tr>
	<tr>
		<th align="right" valign="top" class="windowRowHeader">Description:</th>
		<td style="padding: 5px;" valign="bottom">
			<?
			NewFormItem($f,$s,"description","text", 20,50);
			?>
		</td>
	</tr>
	<?
	if ($list->id) {
	?>
	<tr>
		<th align="right" valign="top" class="windowRowHeader">People:</th>
		<td style="padding: 5px;">
			<?
			$renderedlist->calcStats();
			?>
			<table border="0" cellspacing="3" cellpadding="2" width="150px">
				<tr>
					<td  class="border" valign="center" width="100px"><b><?=$renderedlist->total?></b></td>
					<td align="right"><?=submit($f, 'preview','Preview')?></td>
				</tr>
			</table>
		</td>
	</tr>
	<?
	}
	?>
</table>
<?
endWindow();

if (!$list->id) {
?>
	<div style="margin-left: 10px;"><img src="img/bug_lightbulb.gif" > Tip: Choose a name that clearly describes which people are included on this list. Most lists automatically update and can be reused indefinitely.
	</div><br>
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
$RULEMODE = array('multisearch' => true, 'text' => true, 'reldate' => true, 'numeric' => true);
$RULES = DBFindMany("Rule", "from rule r,listentry le
			where le.ruleid=r.id and le.listid='" . $_SESSION['listid'] . "'" ,"r");

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
</table>
<?
endWindow();

startWindow("List Tools");
if ($USER->authorize('listuploadids') || $USER->authorize('listuploadcontacts')) {
	button_bar(submit($f,'search','Search Contacts') . help('List_SearchAndAdd'),
			submit($f,'manualAdd',"Enter Contacts") . help('List_ManualAdd'),
			submit($f,'addressBookAdd',"Open Address Book") . help('List_AddressBookAdd'),
			submit($f,'uploadList',"Upload List") . help('List_UploadList'));
} else {
	button_bar(submit($f,'search','Search Contacts') . help('List_SearchAndAdd'),
			submit($f,'manualAdd',"Enter Contacts") . help('List_ManualAdd'),
			submit($f,'addressBookAdd',"Open Address Book") . help('List_AddressBookAdd'));
}
endWindow();

}

buttons();
EndForm();
?>
<script SRC="script/calendar.js"></script>
<?
include_once("navbottom.inc.php");
?>