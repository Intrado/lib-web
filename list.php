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
include_once("obj/PersonData.obj.php");
include_once("obj/Address.obj.php");
include_once("obj/Phone.obj.php");
include_once("obj/Email.obj.php");
include_once("obj/PeopleList.obj.php");
include_once("obj/AudioFile.obj.php");
include_once("obj/FieldMap.obj.php");
include_once("obj/Rule.obj.php");
include_once("obj/ListEntry.obj.php");
include_once("obj/RenderedList.obj.php");

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

if (isset($_POST['addlist_x'])) {
	$_SESSION['listid'] = NULL;
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

		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if (QuickQuery('select id from list where name = \'' . DBSafe(GetFormData($f,$s,"name")) . "' and userid = $USER->id and deleted=0 and id != " . (0 + $_SESSION['listid']))) {
			error('A list named \'' . GetFormData($f,$s,"name") . '\' already exists');
		} else if(CheckFormSubmit($f,'add') && !GetFormData($f,$s,FieldMap::getFirstNameField())) {
			error('First Name is required to manually add');
		} else if (CheckFormSubmit($f,'add') && !GetFormData($f,$s,FieldMap::getLastNameField())) {
			error('Last Name is required to manually add');
		} else {
			//submit changes

			$list = new PeopleList($_SESSION['listid']);

			PopulateObject($f,$s,$list,array("name","description"));

			$list->userid = $USER->id;

			$list->update();

			if(CheckFormSubmit($f,'add')) {
				//submit changes
				$person = new Person();
				$person->userid = GetFormData($f,$s,"manualsave") ? $USER->id : 0;
				$person->customerid = $USER->customerid;
				$person->deleted = 0;
				$person->update();

				$data = getChildObject($person->id, 'PersonData', 'persondata');
				PopulateObject($f,$s,$data,array(FieldMap::getFirstNameField(),
												FieldMap::getLastNameField(),
												FieldMap::getLanguageField()));
				$data->personid = $person->id;
				$data->update();

				$address = getChildObject($person->id, 'Address', 'address');
				PopulateObject($f,$s,$address,array('addr1','addr2','city','state','zip'));
				$address->personid = $person->id;
				$address->update();

				$phone = getChildObject($person->id, 'Phone', 'phone');
				PopulateObject($f,$s,$phone,array('phone'));
				$phone->personid = $person->id;
				$phone->sequence = 0;
				$phone->phone = Phone::parse($phone->phone);
				$phone->update();

				$email = getChildObject($person->id, 'Email', 'email');
				PopulateObject($f,$s,$email,array('email'));
				$email->personid = $person->id;
				$email->sequence = 0;
				$email->update();

				$le = new ListEntry();
				$le->listid = $list->id;
				$le->type = "A";
				$le->personid = $person->id;
				$le->create();
			}

			$fieldaddsubmit = false;
			if ($list->id) {
				//now see if there is a new list rule
				$fieldnum = GetFormData($f,$s,"newrulefieldnum");
				if ($fieldnum != "") {
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

	//check to see if the name & desc is prepopulated from another form
	if (isset($_POST['addlist_x'])) {
		$_SESSION['listid'] = NULL;
		$list = new PeopleList();
		$list->name = get_magic_quotes_gpc() ? stripslashes($_POST['addlistname']) : $_POST['addlistname'];
		$list->description = get_magic_quotes_gpc() ? stripslashes($_POST['addlistdesc']) : $_POST['addlistdesc'];
		if(QuickQuery("select id from list where name = '" . DBSafe($list->name) . "' and userid = $USER->id and deleted=0"))
			error("A list named '$list->name' already exists");
	} else {
		$list = new PeopleList($_SESSION['listid']);
	}


	$fields = array(
				array("name","text",1,50,true),
				array("description","text",1,50,false)
				);

	PutFormData($f,$s,"manualsave",1,"bool",0,1,false);

	PutFormData($f,$s,FieldMap::getFirstNameField(),"","text",1,255, false);
	PutFormData($f,$s,FieldMap::getLastNameField(),"","text",1,255, false);
	PutFormData($f,$s,FieldMap::getLanguageField(),"","text",1,255);

	PutFormData($f,$s,"addr1","","text",1,50);
	PutFormData($f,$s,"addr2","","text",1,50);
	PutFormData($f,$s,"city","","text",1,50);
	PutFormData($f,$s,"state","","text",1,2);
	PutFormData($f,$s,"zip","","text",1,10);
	PutFormData($f,$s,"phone","","phone",1,20);
	PutFormData($f,$s,"email","","email",1,100);

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
$TITLE = 'List Editor: ' . ($_SESSION['listid'] == NULL ? "New List" : $list->name);

include_once("nav.inc.php");

$titles = array(	"id" => "ID",
					"name" => "Name",
					"phone" => "Phone",
					"email" => "Email",
					"address" => "Address"
					);

NewForm($f);
if (!$list->id)
	buttons(submit($f,'refresh','save','save'));
else
	buttons(submit($f,'search','search','search') , submit($f, 'preview','preview','preview'), submit($f,'refresh','refresh','refresh'), submit($f,'save','done','done'));

startWindow('List Information', 'padding: 3px;');
print 'Name: ';
NewFormItem($f,$s,"name","text", 20,50);
print '&nbsp;&nbsp;Description: ';
NewFormItem($f,$s,"description","text", 20,50);

$renderedlist->calcStats();
print("&nbsp;&nbsp; Total People in List: <b>$renderedlist->total</b>");

endWindow();

print '<br>';

if (!$list->id) {
?>
	<div style="margin-left: 10px;"><img src="img/bug_important.gif" > Please name your list and then click Save to continue.</div><br>
<?
} else {

StartWindow("List Content");
?>
<table border="0" cellpadding="3" cellspacing="1" width=100%>
	<tr>
		<th align="right" valign="top" class="windowRowHeader">Rules:<br><? print help('List_Rules', NULL, 'grey'); ?></th>
		<td style="padding: 5px;" valign="bottom">
<?
//ruleeditform expects $RULES to be set
$RULEMODE = array('multisearch' => true, 'text' => false, 'reldate' => true);
$RULES = DBFindMany("Rule", "from rule r,listentry le
			where le.ruleid=r.id and le.listid='" . $_SESSION['listid'] . "'" ,"r");
include("ruleeditform.inc.php");
?>
		</td>
	</tr>

	<tr>
		<th align="right" valign="top" class="windowRowHeader">Additions:<br><? print help('List_Additions', NULL, 'grey'); ?></th>
		<td style="padding: 5px;">
<?
if ($list->id) {
	$renderedlist->mode = "add";
//	$renderedlist->pagelimit = -1;
	$doscrolling = true;
	$showpagemenu = true;
	include("list.inc.php"); //expects $renderedlist, $showpagemenu to be set
}
?>
		</td>
	</tr>

	<tr>
		<th align="right" valign="top" class="windowRowHeader">Skip:<br><? print help('List_Skip', NULL, 'grey'); ?></th>
		<td style="padding: 5px;">
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

	<tr>
		<th align="right" valign="top" class="windowRowHeader">Manual Add:<br><? print help('List_ManualAdd', NULL, 'grey'); ?></th>
		<td style="padding: 5px;">
			<table border="0" cellpadding="2" cellspacing="1" class="list">
				<tr class="listHeader" align="left" valign="bottom">
					<th>First Name*</th>
					<th>Last Name*</th>
					<th>Language Preference</th>
					<th>Phone</th>
					<th>Email</th>
					<th>Address</th>
				</tr>
				<tr valign="top">
					<td><? NewFormItem($f,$s,FieldMap::getFirstNameField(),"text", 10,50); ?></td>
					<td><? NewFormItem($f,$s,FieldMap::getLastNameField(),"text", 10,50); ?></td>
					<td>
						<?
						NewFormItem($f,$s,FieldMap::getLanguageField(),"selectstart");
						$data = DBFindMany('Language', "from language where customerid='$USER->customerid' order by name");
						foreach($data as $language)
							NewFormItem($f,$s,FieldMap::getLanguageField(),"selectoption",$language->name,$language->name);
						NewFormItem($f,$s,FieldMap::getLanguageField(),"selectend");
						?>
					</td>
					<td><? NewFormItem($f,$s,"phone","text", 10,20); ?></td>
					<td><? NewFormItem($f,$s,"email","text", 10, 100); ?></td>
					<td rowspan="2">
						<table border="0" cellpadding="">
							<tr>
								<td nowrap>Line 1:</td>
								<td nowrap><? NewFormItem($f,$s,"addr1","text", 33,50); ?></td>
							</tr>
							<tr>
								<td nowrap align="right">Line 2:</td>
								<td nowrap><? NewFormItem($f,$s,"addr2","text", 33,50); ?></td>
							</tr>
							<tr>
								<td align="right">City:</td>
								<td nowrap>
									<? NewFormItem($f,$s,"city","text", 8,50); ?>
									State:<? NewFormItem($f,$s,"state","text", 2); ?>
									Zip:<? NewFormItem($f,$s,"zip","text", 5, 5); ?>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<td colspan="6"><div><? NewFormItem($f,$s,"manualsave","checkbox"); ?>Save to My Address Book <?= help('List_AddressBookAdd',NULL,"small"); ?>&nbsp;&nbsp;&nbsp;<span style="vertical-align: middle;"><?= submit($f,'add','add','add'); ?></span></div></td>
				</tr>
			</table>
			* Required field
		</td>
	</tr>
	<tr>
		<th align="right" valign="top" class="windowRowHeader">Address Book<br><? print help('List_AddressBookAdd', NULL, 'grey'); ?></th>
		<td style="padding: 5px;"><?= button("openaddbook","popup('addresses.php?origin=list',600,400);")?></td>
	</tr>

<? if ($USER->authorize('listuploadids') || $USER->authorize('listuploadcontacts')) { ?>
	<tr>
		<th align="right" valign="top" class="windowRowHeader">Upload List<br><? print help('List_UploadList', NULL, 'grey'); ?></th>
		<td style="padding: 5px;"><?= button("upload",NULL,"uploadlist.php"); ?></td>
	</tr>
<? } ?>
</table>
<?
EndWindow();

}

buttons();
EndForm();
include_once("navbottom.inc.php");
?>