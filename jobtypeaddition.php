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
include_once("inc/text.inc.php");
include_once("obj/JobType.obj.php");
include_once("obj/Setting.obj.php");
include_once("obj/Phone.obj.php");

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
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		//do check
		if( CheckFormSection($f, $s) )
		{
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if(QuickQuery("select count(*) from jobtype where not deleted and name = '" . DBSafe(strtolower(GetFormData($f, $s, "jobtypename"))) . "'")){
			error("That name is already in use");
		} else {

			$type = new JobType();
			$type->name = GetFormData($f, $s, "jobtypename");
			$type->info = GetFormData($f, $s, "jobtypedesc");
			$type->systempriority = 3;
			if($IS_COMMSUITE){
				$type->systempriority = GetFormData($f, $s, "systempriority");
			}
			$type->issurvey = GetFormData($f, $s, "issurvey");
			if($type->issurvey && $type->systempriority != 3){
				error("Survey job types can only have a system priority of General");
			} else {
				$type->create();

				$survey = "";
				if($type->issurvey)
					$survey = "survey";
				foreach($max as $index => $maxvalue){
					if($type->issurvey && $index == "sms")
						continue;
					for($i=0; $i<$maxvalue; $i++){
						QuickUpdate("insert into jobtypepref (jobtypeid, type, sequence, enabled)
									values ('" . $type->id . "','$index','" . $i . "','"
									. DBSafe(GetFormData($f, $s, $index . $i . $survey)) . "')");
					}
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
	if($IS_COMMSUITE){
		PutFormData($f, $s, "systempriority", "3", "number");
	}
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

$PAGE = "admin:contactsettings";
$TITLE = "Job Type Editor: New Job Type";
include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, $s, "Done"));
startWindow("Add a Job Type");
?>

<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr>
		<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">New Job Type:</th>
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
					<td>
<?
						if($IS_COMMSUITE){
								NewFormItem($f, $s, "systempriority", "selectstart");
								NewFormItem($f, $s, "systempriority", "selectoption", "High Priority", "2");
								NewFormItem($f, $s, "systempriority", "selectoption", "General", "3");
								NewFormItem($f, $s, "systempriority", "selectend");
						} else {
							echo "General";
						}
?>
					</td>
				</tr>
				<tr>
					<td width="30%">Is Survey?</td>
					<td>
						<? NewFormItem($f, $s, "issurvey", "checkbox", 0, 1, "onclick='if(this.checked) displaysurveytable(); else hidesurveytable();'");?>
					</td>
				</tr>
				<tr>
					<td width="30%">Info For Parents</td>
					<td ><? NewFormItem($f, $s, "jobtypedesc", "textarea", 20, 3);?></td>
				</tr>
				<tr>
					<td width="30%">Contact Preferences</td>
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
								if($IS_COMMSUITE && $index == 'sms') continue;
								if($index == 'sms' && !getSystemSetting('_hassms', false)) continue;
?>
								<tr>
									<td class="bottomBorder"><?=destination_label_popup_paragraph($index)?></td>
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
									<td class="bottomBorder"><?=destination_label_popup_paragraph($index)?></td>
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
		hide("survey");
		show("nonsurvey");
	}

	function displaysurveytable(){
		hide("nonsurvey");
		show("survey");
	}

</script>