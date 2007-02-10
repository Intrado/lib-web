<?

include_once('inc/common.inc.php');
include_once('obj/SpecialTask.obj.php');
include_once('obj/Message.obj.php');
include_once('obj/MessagePart.obj.php');
include_once('obj/AudioFile.obj.php');
include_once('obj/Phone.obj.php');
include_once("obj/JobType.obj.php");
include_once("obj/PeopleList.obj.php");
include_once("obj/Job.obj.php");
include_once("obj/JobLanguage.obj.php");
include_once("obj/Language.obj.php");
include_once('inc/html.inc.php');
include_once("inc/form.inc.php");
include_once('inc/table.inc.php');
include_once('inc/utils.inc.php');

// AUTHORIZATION //////////////////////////////////////////////////
if (!$USER->authorize("starteasy")) {
	redirect("unauthorized.php");
}

// DATA SECTION
$VALIDJOBTYPES = JobType::getUserJobTypes();

$languages = DBFindMany("Language","from language where customerid=" . $USER->customerid);
if(isset($_REQUEST['id'])) {
	$_SESSION['easycallid'] = null;
	redirect();
}


// FORM HANDLING
$f = "easycall";
$s = "main";
$reloadform = 0;

foreach($languages as $lang){
	if(CheckFormSubmit($f, 'remove_'.$lang->name)) {
		$removedlang = true;
		break;
	}
}

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f, 'add') || $removedlang)
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		$phone = Phone::parse(GetFormData($f,$s,"phone"));

		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if (!Phone::validate($phone)) {
			if ($IS_COMMSUITE)
				error('The phone number must be 2-6 digits or exactly 10 digits long (including area code)','You do not need to include a 1 for long distance');
			else
				error('The phone number must be exactly 10 digits long (including area code)','You do not need to include a 1 for long distance');
		} else if (GetFormData($f,$s,"listid") <=0 ) {
			error('Please choose a list');
			
		} else if(QuickQuery("select * from job where userid = '$USER->id' and deleted = 0 
					and name = '" . DBSafe(GetFormData($f, $s, 'name')) ."'")) {
			error('This job name is already in use, please make another');
		
		} else {
			if (isset($_GET['retry'])) {
				$task = new SpecialTask($_GET['retry']);
				$messages = $task->getData('messagelangs');
				$task->setData('progress', 'Creating Call');
				if($messages){
					$messages = unserialize($messages);
					$count = count($messages);
					$task->setData('count', $count);
				}	
				$task->setData('error', "0");
				$task->status = "queued";
				$task->update();
				redirect('easycallrecord.php?taskid=' . $task->id);
			}
			
			if($_SESSION['easycallid'] == null){
				$task = new SpecialTask();
			} else {
				$task = new SpecialTask($_SESSION['easycallid']);
			}
			$task->type = 'EasyCall';
			$task->setData('phonenumber', $phone);	
			$task->setData('callerid', getSystemSetting('callerid'));				
			$task->setData('name', GetFormData($f, $s, 'name'));	
			$task->setData('origin', "start");			
			$task->setData('userid', $USER->id);				
			$task->setData('listid', GetFormData($f,$s,"listid"));			
			$task->setData('jobtypeid', GetFormData($f,$s,"jobtypeid"));
			$task->setData('progress', "Creating Call");	
			$task->setData('count', "0");	
			$task->lastcheckin = date("Y-m-d H:i:s");
			if(CheckFormSubmit($f, 'add') || $removedlang)				
				$task->status = "new";
			else			
				$task->status = "queued";
			$task->customerid=$USER->customerid;
			if($USER->authorize('sendmulti')) {
				$langlist = $task->getData('languagelist');
				if($langlist == null) {
					$languagearray = array();
					$languagearray[] = "Default";
				} else {
					$languagearray = explode("|", $langlist);
				}
				$selectedlangs = getFormData($f, $s, "newlang");
				if($selectedlangs && CheckFormSubmit($f, 'add')){
					$used = false;
					if(isset($languagearray)) {
						foreach ($languagearray as $lang) {
							if ($lang == $selectedlangs) {
								$used = true;
								break;
							}
						}
					}
					if (!$used)
						$languagearray[]=$selectedlangs;
				}
				foreach($languagearray as $lang){
					if(CheckFormSubmit($f, 'remove_'.$lang)){
						$newarray = array();
						foreach($languagearray as $language) {
							if($language != $lang) {
								$newarray[] = $language;
							}
						}
						$languagearray = $newarray;
					}
				}
				
				$languagelist = implode("|",$languagearray);
				$task->setData('languagelist', $languagelist);
			} else {
				$task->setData('languagelist', "Default");
			}		
			if($task->id){
				$task->update();
			} else {
				$task->create();
			}		
			$_SESSION['easycallid'] = $task->id;
			if(!CheckFormSubmit($f, 'add') && !$removedlang)
				redirect('easycallrecord.php?taskid=' . $task->id);
			else
				$reloadform = 1;
		}
	}
} else {
	$reloadform = 1;
}

if($reloadform == 1) {
	ClearFormData($f);

	if (isset($_GET['retry'])){
		$specialtask = new SpecialTask(DBSafe($_GET['retry']));
		$languagearray = explode("|", $specialtask->getData('languagelist'));
	}else{
		if($_SESSION['easycallid'] != null)
			$specialtask = new SpecialTask($_SESSION['easycallid']);
		else 
			$specialtask = false;
	}

	PutFormData($f,$s,"listid",$specialtask ? $specialtask->getData('listid') : 0);
	PutFormData($f,$s,"jobtypeid",$specialtask ? $specialtask->getData('jobtypeid') : end($VALIDJOBTYPES)->id);

	if ($specialtask) {
		$phone = Phone::format($specialtask->getData('phonenumber'));
		PutFormData($f,$s,"name",$specialtask->getData('name'),"text",1,20);
	} else {
		PutFormData($f, $s, 'name', GetFormData($f, $s, 'name'), 'text', 1, 50, true);
		if ($USER->phone)
			$phone = Phone::format($USER->phone);
		else
			$phone = "";
	}
	PutFormData($f,$s,"phone",$phone,"text","2","20"); // 20 is the max to accomodate formatting chars
	PutFormData($f,$s,"newlang", "");
}


//////////////
//FUNCTIONS

function language_select($form, $section, $name, $type) {
	global $languages, $languagearray;
	
	if((count($languagearray) == 1 || count($languagearray) == 0) && ($type == "remove"))
		return 1;

	if((count($languages) == (count($languagearray)-1)) && ($type == "add"))
		return 1;
	
	NewFormItem($form, $section, $name, 'selectstart', NULL, NULL, "");
	if($type == "add"){
		NewFormItem($form, $section, $name, 'selectoption',"Languages to Add","");
	} else {
		NewFormItem($form, $section, $name, 'selectoption',"Languages Selected","");
	}
	foreach ($languages as $language) {
		$used = ($type == "add") ? false : true;
		if(isset($languagearray)) {
			foreach ($languagearray as $lang) {
				if ($lang == $language->name) {
					$used = ($type == "add") ? true : false;
					break;
				}
			}
		}
		if ($used)
		continue;
		NewFormItem($form, $section, $name, 'selectoption', $language->name, $language->name);
	}
	NewFormItem($form, $section, $name, 'selectend');
	return 0;
}



$TITLE = 'EasyCall';

include_once('popup.inc.php');

NewForm($f);

buttons(submit($f, $s, 'submit','callmetorecord'), button('cancel', 'window.close()'));
startWindow("EasyCall");

?>
	<table border="0" cellpadding="3" cellspacing="0" width="400">
		<tr>
			<th align="right" class="windowRowHeader bottomBorder" style="width: 100px;">Job&nbsp;Name:&nbsp;<?= help("EasyCall_Name", null, 'small'); ?></td>
			<td class="bottomBorder"><? NewFormItem($f,$s,"name","text",30,30); ?></td>
		</tr>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder" style="width: 100px;">Priority:&nbsp;<?= help('EasyCall_Priority', NULL, "small"); ?></td>
			<td class="bottomBorder">

<?
				NewFormItem($f,$s,"jobtypeid", "selectstart");
				foreach ($VALIDJOBTYPES as $item) {
					NewFormItem($f,$s,"jobtypeid", "selectoption", $item->name, $item->id);
				}
				NewFormItem($f,$s,"jobtypeid", "selectend");
?>
			</td>
		</tr>

		<tr>
			<th align="right" class="windowRowHeader bottomBorder" style="width: 100px;">List: <?= help('EasyCall_List', NULL, "small"); ?></td>
			<td class="bottomBorder">

<?
				$peoplelists = DBFindMany("PeopleList",", (name +0) as foo from list where userid=$USER->id and deleted=0 order by foo,name");
				NewFormItem($f,$s,"listid", "selectstart");
				NewFormItem($f,$s,"listid", "selectoption", "-- Select a list --", NULL);
				foreach ($peoplelists as $plist) {
					NewFormItem($f,$s,"listid", "selectoption", $plist->name, $plist->id);
				}
				NewFormItem($f,$s,"listid", "selectend");
?>
			</td>
		</tr>

		<tr>
			<th align="right" class="windowRowHeader bottomBorder" style="width: 100px;">Call&nbsp;Me&nbsp;At:&nbsp;<?= help("EasyCall_PhoneNumber", null, 'small'); ?></td>
			<td class="bottomBorder"><? NewFormItem($f,$s,"phone","text",20); ?></td>
		</tr>
<?
		if($USER->authorize('sendmulti')) {
			?>
			<tr>
				<th align="right" class="windowRowHeader bottomBorder" style="width: 100px;">Multilingual Language Option:</td>
				<td class="bottomBorder">
				<table border="0" cellpadding="2" cellspacing="1" class="list">
					<tr class="listHeader" align="left" valign="bottom">
						<th>Language Preference</th>
						<? if(!isset($_GET['retry'])){ ?>
							<th>&nbsp;</th>
						<? } ?>
					</tr>
					<?	
						if(isset($languagearray)) {
							foreach($languagearray as $lang){
								if($lang == "Default") continue;
								?>
								<tr>
									<td><?=$lang ?></td>
									<td>
										<?
										if(!isset($_GET['retry']))
											echo submit($f, 'remove_'.$lang, 'delete', 'delete');
										?>
									</td>
								</tr>
								<?
							}
						}
					?>
					
					<tr><td>
						<?
						if(!language_select($f,$s,"newlang", "add")) {
						 	?></td><td><?
						 	if(!isset($_GET['retry']))
								echo submit($f, 'add', 'Add', 'add');
						}
						?>
					</td></tr>
				</table>
				</td>
			</tr>
			<?
		}

		if($IS_COMMSUITE){
			?> <tr><td colspan=3 style="padding: 10px;">If dialing an outside line, please include the area code.</td><tr> <?
		} else {
			?> <tr><td colspan=3 style="padding: 10px;">Enter the 10-digit direct-dial phone number where you are currently located.</td><tr> <?
		}
?>
		</table>

<?
endWindow();
buttons();
include_once('popupbottom.inc.php');

?>