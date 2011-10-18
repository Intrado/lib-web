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


if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}

if(isset($_GET['type'])){
	$_SESSION['destinationtype'] = $_GET['type'];
	redirect();
}

$type = isset($_SESSION['destinationtype']) ? $_SESSION['destinationtype'] : "phone";
$default = 3;
if($type == "email" || $type == "sms")
	$default = 2;
$name = $type;
if($name == "email" || $name == "phone"){
	$name .= "s";
}

$max = getSystemSetting("max".$name, $default);


switch ($type) {
default:
case "phone":
	$presetlabels = array("Home", "Work", "Cell", "");
	break;
case "email":
	$presetlabels = array("Home", "Work", "Cell", "PDA", "");
	break;
case "sms":
	$presetlabels = array("Cell", "PDA", "");
	break;
}




$reloadform = 0;
$f="destinationlabels";
$s="main";

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

		$lableerror = false;
		for($i = 0; $i < $max; $i++){
			if(preg_match("/[#@]+/", GetFormData($f, $s, $type . $i . "other"))) {
				$lableerror = true;
			}
		}
		
		//do check
		if( CheckFormSection($f, $s) )
		{
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		}else if($lableerror) {
			error('There was a problem trying to save your changes', 'Please do not use # or @ symbols in label');
		}else {
			$warning = false;
			QuickUpdate("begin");
			QuickUpdate("delete from destlabel where type = '" . DBSafe($type) . "'");
			for($i = 0; $i < $max; $i++){
				$label = GetFormData($f, $s, $type . $i);
				if($label == "other"){
					$label = trim(GetFormData($f, $s, $type . $i . "other"));
				}
				if(preg_match("/[0-9\)\(]+/", $label) || strripos($label,"phone") || strripos($label, "email") || strripos($label, "sms")){
					$warning = true;
				}
				QuickUpdate("insert into destlabel (type, sequence, label, notes) values
								('" . DBSafe($type) . "', '" . $i . "', '" . DBSafe($label) . "', '" . DBSafe(GetFormData($f, $s, $type . $i . "notes")) . "')");
			}
			QuickUpdate("commit");

/* disabled per howard
			if($warning){
				?>
				<script language="javascript">
					changelabel=confirm('We recommend that labels do not contain numbers, parentheses or the type it describes.\nWould you like to stay and change your labels?');
					if(changelabel){
						location.href="destinationlabel.php?type=<?=$type?>";
					} else {
						location.href="settings.php";
					}
				</script>
				<?
			} else {
*/
				redirect("settings.php");
//			}
		}
	}
} else {
	$reloadform = 1;
}

if($reloadform){
	ClearFormData($f);
	for($i=0; $i<$max; $i++){
		$label = fetch_labels($type, $i, true);
		if(in_array($label, $presetlabels)){
			PutFormData($f, $s, $type . $i, $label, "text");
			PutFormData($f, $s, $type . $i . "other", "", "text");
		} else {
			PutFormData($f, $s, $type . $i, "other", "text");
			PutFormData($f, $s, $type . $i . "other", $label, "text");
		}
		$notes = QuickQuery("select notes from destlabel where type = '" . $type . "' and sequence = " . $i);
		PutFormData($f, $s, $type . $i . "notes", $notes, "text", 0, 255);
	}
}
////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////




////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$TITLE="Destination Labels - " . format_delivery_type($type);
$PAGE = "admin:settings";
include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, $s, "Done"));
$hoverHelpLinkType = '';
if ($type == 'phone') {
	$hoverHelpLinkType = 'DestinationLabels_Phone';
} else if ($type == 'email') {
	$hoverHelpLinkType = 'DestinationLabels_Email';
} else if ($type == 'sms') {
	$hoverHelpLinkType = 'DestinationLabels_SMS';
}
startWindow("Labels" . help($hoverHelpLinkType));
?>
<table border="0" cellpadding="3" cellspacing="1">
	<tr class="listheader">
		<th>Destination</th>
		<th colspan="2">Label</th>
		<th>Notes</th>
	</tr>
<?
	for($i=0; $i< $max; $i++){
?>
		<tr>
			<td><?=format_delivery_type($type)?>&nbsp;<?=$i+1?></td>
			<td>
				<?
					NewFormItem($f, $s, $type . $i, "selectstart", NULL, NULL, "onchange='if(this.value == \"other\"){ $(\"otherlabel$i\").show() } else { $(\"otherlabel$i\").hide() }'");
					NewFormItem($f, $s, $type . $i, 'selectoption'," -- None -- ", "");


					foreach ($presetlabels as $label) {
						if (strlen($label))
							NewFormItem($f, $s, $type . $i, 'selectoption',$label, $label);
					}

					NewFormItem($f, $s, $type . $i, 'selectoption'," -- Other -- ", "other");
					NewFormItem($f, $s, $type . $i, "selectend");
				?>
			</td>
			<td>
				<?
					$display=" display:none; ";
					if(GetFormData($f, $s, $type . $i) == "other"){
						$display = " display:block; ";
					}
				?>
				<div id="otherlabel<?=$i?>" style="<?=$display?>"><? NewFormItem($f, $s, $type . $i . "other", "text", 20, 20) ?></div>
			</td>
			<td>
				<? NewFormItem($f, $s, $type . $i . "notes", "textarea", 40, 3) ?>
			</td>
		</tr>
<?
	}
?>
</table>
<?
endWindow();
buttons();
EndForm();
include("navbottom.inc.php");
?>