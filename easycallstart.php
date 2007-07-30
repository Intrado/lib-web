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

if(isset($_REQUEST['id'])) {
	$_SESSION['easycallid'] = null;
	redirect();
}
$languages = DBFindMany("Language","from language order by name");


// FORM HANDLING
$f = "easycall";
$s = "main";
$reloadform = 0;

$removedlang = false;
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
		} else if ($phoneerror = Phone::validate($phone)){
			error($phoneerror);
		} else if (GetFormData($f,$s,"listid") <=0 ) {
			error('Please choose a list');
		} else if(QuickQuery("select count(*) from job where userid = '$USER->id' and deleted = 0
					and name = '" . DBSafe(GetFormData($f, $s, 'name')) ."'
					and status!='cancelling' and status !='complete' and status != 'cancelled'")) {
			error('This job name is already in use, please make another');

		} else {
			if (isset($_GET['retry'])) {
				$oldtask = new SpecialTask($_GET['retry']);
				$task = new SpecialTask();
				$task->data = $oldtask->data;
				$task->type = $oldtask->type;
				$task->setData('phonenumber', $phone);
				$task->setData('progress', 'Creating Call');
				$task->setData('error', "0");
				$task->status = "queued";
				$task->create();
				QuickUpdate("call start_specialtask(" . $task->id . ")");
				redirect('easycallrecord.php?taskid=' . $task->id);
			} else {

				if($_SESSION['easycallid'] == null){
					$task = new SpecialTask();
				} else {
					$task = new SpecialTask($_SESSION['easycallid']);
				}
				$task->type = 'EasyCall';
				$task->setData('phonenumber', $phone);
				$task->setData('callerid', getSystemSetting('callerid'));
				$name = GetFormData($f, $s, 'name');
				if($name == "")
					$name = "EasyCall - " . date("M d, Y G:i:s");
				$task->setData('name', $name);
				$task->setData('origin', "start");
				$task->setData('userid', $USER->id);
				$task->setData('listid', GetFormData($f,$s,"listid"));
				$task->setData('jobtypeid', GetFormData($f,$s,"jobtypeid"));
				$task->setData('progress', "Creating Call");
				$task->setData('count', 0);
				$task->lastcheckin = date("Y-m-d H:i:s");
				if(CheckFormSubmit($f, 'add') || $removedlang)
					$task->status = "new";
				else
					$task->status = "queued";
				if($USER->authorize('sendmulti') && GetFormData($f, $s,'addlangs')) {
					$languagearray = array();
					$langcount = $task->getData("totalamount");
					if($langcount == null) {
						$langcount = 1;
						$languagearray[0] = "Default";
						$task->setData("language0", "Default");
						$task->setData("totalamount", 1);
					} else {
						for($i = 0; $i < $langcount; $i++){
							$langnum = "language" . $i;
							$languagearray[$i] = $task->getData($langnum);
						}
					}

					$selectedlangs = getFormData($f, $s, "newlang");
					if($selectedlangs && CheckFormSubmit($f, 'add') || $selectedlangs && CheckFormSubmit($f, $s)){
						$used = false;
						foreach ($languagearray as $lang) {
							if ($lang == $selectedlangs) {
								$used = true;
								break;
							}
						}
						if (!$used){
							$languagearray[]=$selectedlangs;
							$langcount = $task->getData("totalamount");
							$langcount++;
							$task->setData('totalamount', $langcount);
							for($i = 0; $i < $langcount; $i++){
								$langnum = "language" . $i;
								$task->setData($langnum, $languagearray[$i]);
							}
						}
					}
					foreach($languagearray as $lang){
						if(CheckFormSubmit($f, 'remove_'.$lang)){
							$newarray = array();
							foreach($languagearray as $language) {
								if($language != $lang) {
									$newarray[] = $language;
								}
							}
							$langcount = $task->getData("totalamount");
							for($i = 0; $i< $langcount; $i++){
								$langnum = "language" . $i;
								$task->delData($langnum);
							}
							$languagearray = $newarray;
							$langcount = count($newarray);
							$task->setData("totalamount", $langcount);
							for($i = 0; $i < $langcount; $i++){
								$langnum = "language" . $i;
								$task->setData($langnum, $newarray[$i]);
							}
							break;
						}
					}

				} else {
					$languagearray = array();
					$langcount = 1;
					$languagearray[0] = "Default";
					$task->setData("language0", "Default");
					$task->setData("totalamount", 1);
				}
				$task->setData('currlang', $task->getdata('language0'));
				$task->setData('langchkbox', GetFormData($f, $s, 'addlangs'));

				if($task->id){
					$task->update();
				} else {
					$task->create();
				}
				$_SESSION['easycallid'] = $task->id;
				if(!CheckFormSubmit($f, 'add') && !$removedlang){
					QuickUpdate("call start_specialtask(" . $task->id . ")");
					redirect('easycallrecord.php?taskid=' . $task->id);
				}
			}
		}
	}
} else {
	$reloadform = 1;
}

if($reloadform == 1) {

	ClearFormData($f);

	if (isset($_GET['retry'])){
		$specialtask = new SpecialTask(DBSafe($_GET['retry']));
	}else{
		if($_SESSION['easycallid'] != null)
			$specialtask = new SpecialTask($_SESSION['easycallid']);
		else
			$specialtask = false;
	}
	PutFormData($f,$s,"listid",$specialtask ? $specialtask->getData('listid') : 0);
	PutFormData($f,$s,"jobtypeid",$specialtask ? $specialtask->getData('jobtypeid') : end($VALIDJOBTYPES)->id);
	$checked=false;
	if ($specialtask) {
		$phone = Phone::format($specialtask->getData('phonenumber'));
		$name=$specialtask->getData("name");
		$langcount = $specialtask->getData("totalamount");
		$languagearray = array();
		for($i = 0; $i < $langcount; $i++){
			$langnum = "language" . $i;
			$languagearray[$i] = $specialtask->getData($langnum);
		}
		if($specialtask->getData('langchkbox'))
			$checked=true;
	} else {
		if ($USER->phone)
			$phone = Phone::format($USER->phone);
		else
			$phone = "";
		$name = "";
	}
	PutFormData($f,$s,"phone",$phone,"text","2","20");
	PutFormData($f,$s,"addlangs",(bool)$checked, "bool", 0, 1);
	PutFormData($f, $s, 'name', $name , 'text', 1, 50);
	PutFormData($f, $s, 'newlang', "");
	PutFormData($f, $s, 'hidden', "");

}


//////////////
//FUNCTIONS

function language_select($form, $section, $name, $type) {
	global $languages, $languagearray;

	NewFormItem($form, $section, $name, 'selectstart', NULL, NULL, 'id="addlang"');
	$languagenamearray = array();
	foreach($languages as $language)
		$languagenamearray[] = $language->name;

	if($type == "add"){
		NewFormItem($form, $section, $name, 'selectoption'," -- Select a Language -- ", "");
	}
	foreach ($languagenamearray as $language) {
		$used = ($type == "add") ? false : true;
		if(isset($languagearray)) {
			foreach ($languagearray as $lang) {
				if ($lang == $language) {
					$used = ($type == "add") ? true : false;
					break;
				}
			}
		}
		if($used) continue;
		if($language == "English") continue;
		NewFormItem($form, $section, $name, 'selectoption', $language, $language);
	}
	NewFormItem($form, $section, $name, 'selectend');
}



$TITLE = 'EasyCall';

include_once('popup.inc.php');

NewForm($f);

buttons(submit($f, $s, 'Call Me To Record'), button('Cancel', 'window.close()'));
startWindow("EasyCall");

?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr>
			<th align="right" class="windowRowHeader bottomBorder" style="width: 100px;">Job&nbsp;Name:&nbsp;<?= help("EasyCall_Name", null, 'small'); ?></th>
			<td class="bottomBorder"><? NewFormItem($f,$s,"name","text",30,30,'id="name"'); ?></td>
		</tr>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder" style="width: 100px;">Job Type:&nbsp;<?= help('EasyCall_Priority', NULL, "small"); ?></th>
			<td class="bottomBorder">
<?
				NewFormItem($f,$s,"jobtypeid", "selectstart", null, null, 'id="jobtype"');
				foreach ($VALIDJOBTYPES as $item) {
					NewFormItem($f,$s,"jobtypeid", "selectoption", $item->name, $item->id);
				}
				NewFormItem($f,$s,"jobtypeid", "selectend");
?>
			</td>
		</tr>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder" style="width: 100px;">List: <?= help('EasyCall_List', NULL, "small"); ?></th>
			<td class="bottomBorder">
<?
				$peoplelists = DBFindMany("PeopleList",", (name +0) as foo from list where userid=$USER->id and deleted=0 order by foo,name");
				NewFormItem($f,$s,"listid", "selectstart", null, null, 'id="list"');
				NewFormItem($f,$s,"listid", "selectoption", "-- Select a list --", NULL);
				foreach ($peoplelists as $plist) {
					NewFormItem($f,$s,"listid", "selectoption", $plist->name, $plist->id);
				}
				NewFormItem($f,$s,"listid", "selectend");
?>
			</td>
		</tr>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder" style="width: 100px;">Call&nbsp;Me&nbsp;At:&nbsp;<?= help("EasyCall_PhoneNumber", null, 'small'); ?></th>
			<td class="bottomBorder"><? NewFormItem($f,$s,"phone","text",20, "nomax",'id="phone"'); ?></td>
		</tr>
<?
		if($USER->authorize('sendmulti')) {
?>
			<tr>
				<th align="right" class="windowRowHeader bottomBorder" style="width: 100px;">Record in Additional Languages:</th>
				<td class="bottomBorder">
				<table>
					<tr>
						<td>
<?
						$langcount = isset($languagearray) ? count($languagearray) : 1;
							NewFormItem($f,$s,'addlangs','checkbox',NULL,NULL,"id='add'; onclick=\"new getObj('addlang').obj.disabled=!this.checked;
								setVisibleIfChecked(this,'shownifchecked');
								setHiddenIfChecked(this,'hiddenifchecked');
								new getObj('hiddendropdown').obj.disabled=!this.checked;\"" );
?>
						</td>
					</tr>
					<tr>
						<td>
							<div id="shownifchecked">
							<table  border="0" cellpadding="2" cellspacing="1" class="list">
								<tr>
									<td class="bottomBorder">Default - English</td>
									<td class="bottomBorder">&nbsp;</td>
								</tr>
<?
								if(isset($languagearray)) {
									foreach($languagearray as $lang){
										if($lang == "Default") continue;
?>
										<tr>
											<td class="bottomBorder"><?=$lang ?></td>
											<td class="bottomBorder">
<?
												if(!isset($_GET['retry']))
													echo submit($f, 'remove_'.$lang, 'Delete');
												else
													echo "&nbsp;";
?>
											</td>
										</tr>
<?
									}
								}
?>
								<tr>
									<td><? language_select($f,$s,"newlang", "add")?></td>
									<td>
<?
										if(!isset($_GET['retry']))
											echo submit($f, 'add', 'Add');
?>
									</td>
								</tr>

							</table>
							</div>
							<div id="hiddenifchecked">
							<table>
								<tr>
									<td>
<?
										NewFormItem($f, $s, "hidden", 'selectstart', NULL, NULL, 'id="hiddendropdown"');
										NewFormItem($f, $s, "hidden", 'selectoption'," -- Select a Language -- ");
										NewFormItem($f, $s, "hidden", 'selectend');
?>
									</td>
								</tr>
							</table>
							</div>
							<script>
								var box = new getObj('add').obj;
								setVisibleIfChecked(box,'shownifchecked');
								setHiddenIfChecked(box,'hiddenifchecked');
								new getObj('addlang').obj.disabled=!box.checked;
								new getObj('hiddendropdown').obj.disabled=!box.checked;
<?
								if(isset($_GET['retry'])){
?>
									new getObj('add').obj.disabled=true;
									new getObj('jobtype').obj.disabled=true;
									new getObj('list').obj.disabled=true;
									new getObj('name').obj.disabled=true;
									new getObj('addlang').obj.disabled=true;
<?
								}
?>
							</script>
						</td>
					</tr>
				</table>
				</td>
			</tr>
			<?
		}

		if($IS_COMMSUITE){
			?> <tr><td colspan=2 style="padding: 10px;"><img src="img/bug_important.gif" > If dialing an outside line, please include the area code.</td><tr> <?
		} else {
			?> <tr><td colspan=2 style="padding: 10px;"><img src="img/bug_important.gif" > Enter the 10-digit direct-dial phone number where you are currently located.</td></tr> <?
		}
?>
		</table>

<?
endWindow();
buttons();
include_once('popupbottom.inc.php');

?>