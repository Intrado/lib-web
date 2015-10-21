<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/form.inc.php");
include_once("obj/NotificationType.obj.php");
include_once("obj/Setting.obj.php");
include_once("obj/Phone.obj.php");

if (isset($_REQUEST['api'])) {
	if (!$USER->authorize('managesystem')) {
		header("HTTP/1.1 403 Forbidden");
		header("Content-Type: application/json");
		exit();
	}
}

if(isset($_GET['clear'])){
	unset($_SESSION['jobtypemanagement']['radio']);
	redirect();
}
$maxphones = getSystemSetting("maxphones", 3);
$maxemails = getSystemSetting("maxemails", 2);
$maxsms = getSystemSetting("maxsms", 2);
$max = array("phone" => $maxphones,
			"email" => $maxemails);
if(getSystemSetting("_hassms"))
	$max["sms"] = $maxsms;
$maxcolumns = max($maxphones, $maxemails, $maxsms);

/****************** main message section ******************/

$f = "setting";
$s = "main";
$reloadform = 0;


if(CheckFormSubmit($f,$s))
{
	//check to see if formdata is valid
	if(!isset($_REQUEST['api']) && CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		// API-MODE requests are state-less -- clear any left-over formdata from session
		//
		if (isset($_REQUEST['api'])) {
			ClearFormData($f);
		}

		MergeSectionFormData($f, $s);

		//do check
		if(!isset($_REQUEST['api']) && CheckFormSection($f, $s))
		{
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if(QuickQuery("select count(*) from notificationtype where not deleted and name = '" . DBSafe(strtolower(GetFormData($f, $s, "jobtypename"))) . "'")){
			if (isset($_REQUEST['api'])) {
				header("HTTP/1.1 400 Bad Request");
				header("Content-Type: application/json");
				exit(json_encode(Array("code" => "nameNotAvailable")));
			}

			error("That name is already in use");
		} else {

			$type = new NotificationType();
			$type->name = GetFormData($f, $s, "jobtypename");
			$type->info = GetFormData($f, $s, "jobtypedesc");
			$type->systempriority = 3;

			$issurvey = GetFormData($f, $s, "issurvey");
			if($issurvey && $type->systempriority != 3){
				error(_L("Survey %s types can only have a system priority of General",getJobTitle()));
			} else {
				if ($issurvey)
					$type->type = 'survey';
				else
					$type->type = 'job';
				$type->create();

				$survey = "";
				if($issurvey)
					$survey = "survey";
				foreach($max as $index => $maxvalue){
					if($issurvey && $index == "sms")
						continue;
					for($i=0; $i<$maxvalue; $i++){
						QuickUpdate("insert into jobtypepref (jobtypeid, type, sequence, enabled)
									values ('" . $type->id . "','$index','" . $i . "','"
									. DBSafe(GetFormData($f, $s, $index . $i . $survey)) . "')");
					}
				}

				if (isset($_REQUEST['api'])) {
					header("Content-Type: application/json");
					exit(json_encode(Array("type" => Array("id" => $type->id))));
				}

				redirect("jobtypemanagement.php");
			}
		}
	}
} else {
	$reloadform = 1;
}

if($reloadform){
	ClearFormData($f);
	PutFormData($f, $s, "jobtypename", "", "text", 0, 50, true);
	PutFormData($f, $s, "jobtypedesc", "", "text", 0, 255, true);

	PutFormData($f, $s, "issurvey", (bool)0, "bool", 0, 1);
	foreach($max as $index => $maxvalue){
		for($i=0; $i<$maxvalue; $i++){
			PutFormData($f, $s, $index . $i, 0, "bool", 0, 1);
		}
	}
	foreach($max as $index => $maxvalue){
		if($index == "sms") continue;
		for($i=0; $i<$maxvalue; $i++){
			PutFormData($f, $s, $index . $i . "survey", 0, "bool", 0, 1);
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Funcitons
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = _L("%s Type Editor: New %s Type" ,getJobTitle(),getJobTitle());
include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, $s, "Done"));
startWindow(_L("Add a %s Type",getJobTitle()));
?>

<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr>
		<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;"><?= _L("New %s Type:",getJobTitle())?></th>
		<td class="bottomBorder">
			<table border="0" cellpadding="2" cellspacing="0" width=100%>
				<tr>
					<td width="30%">Name</td>
					<td>
					<?
								NewFormItem($f, $s, "jobtypename", "text", 20, 50);
					?>
					</td>
				</tr>
				<tr>
					<td width="30%">System Priority</td>
					<td>General</td>
				</tr>

<? if (getSystemSetting('_hassurvey', true)) { ?>

				<tr>
					<td width="30%">Check if this is a survey</td>
					<td>
						<? NewFormItem($f, $s, "issurvey", "checkbox", 0, 1, "onclick='if(this.checked) displaysurveytable(); else hidesurveytable();'");?>
					</td>
				</tr>
<? } ?>

				<tr>
					<td width="30%">Display Information</td>
					<td ><? NewFormItem($f, $s, "jobtypedesc", "textarea", 20, 3);?></td>
				</tr>
				<tr>
					<td width="30%">Default Contact Preferences</td>
					<td>
						<table border="0" cellpadding="3" cellspacing="1" id="nonsurvey">
							<tr class="listheader">
								<th align="left">&nbsp;</th>
<?
									for($i=0; $i < $maxcolumns; $i++){
										?><th><?=$i+1?></th><?
									}
?>
							</tr>
<?
							foreach($max as $index => $maxvalue){
								if($index == 'sms' && !getSystemSetting('_hassms', false)) continue;
?>
								<tr>
									<th class="bottomBorder"><?=format_delivery_type($index)?></th>
<?
										for($i=0; $i < $maxcolumns; $i++){
											?><td class="bottomBorder" align="center"><?
											if($i < $maxvalue){
												echo destination_label_popup($index, $i, $f, $s, $index . $i);
											} else {
												echo "&nbsp;";
											}
											?></td><?
										}
?>
								</tr>
<?
							}
?>
						</table>
						<table  border="0" cellpadding="3" cellspacing="1" id="survey" style="display:none">
							<tr class="listheader">
								<th align="left">&nbsp;</th>
<?
								for($i=0; $i < $maxcolumns; $i++){
									?><th><?=$i+1?></th><?
								}
?>
							</tr>
<?
							foreach($max as $index => $maxvalue){
								if($index =="sms")
									continue;
?>
								<tr>
									<th class="bottomBorder"><?=format_delivery_type($index)?></th>
<?
									for($i=0; $i < $maxcolumns; $i++){
										?><td class="bottomBorder" align="center"><?
										if($i < $maxvalue){
											echo destination_label_popup($index, $i, $f, $s, $index . $i . "survey");
										} else {
											echo "&nbsp;";
										}
										?></td><?
									}
?>
								</tr>
<?
							}
?>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<?
endWindow();
buttons();
endForm();
include("navbottom.inc.php");

?>
<script>
	function hidesurveytable(){
		$('survey').hide();
		$('nonsurvey').show();
	}

	function displaysurveytable(){
		$('nonsurvey').hide();
		$('survey').show();
	}

</script>