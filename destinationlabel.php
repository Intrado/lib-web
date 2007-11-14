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


if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}

if(isset($_GET['type'])){
	$_SESSION['destinationtype'] = $_GET['type'];
	redirect();
}

$type = isset($_SESSION['destinationtype']) ? $_SESSION['destinationtype'] : "phone";
$default = 4;
if($type == "email" || $type == "sms")
	$default = 2;
$name = $type;
if($name == "email" || $name == "phone"){
	$name .= "s";
}	

$max = getSystemSetting("max".$name, $default);


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

		//do check
		if( CheckFormSection($f, $s) )
		{
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			QuickUpdate("begin");
			QuickUpdate("delete from destlabel where type = '" . DBSafe($type) . "'");
			for($i = 0; $i < $max; $i++){
				QuickUpdate("insert into destlabel (type, sequence, label) values
								('" . DBSafe($type) . "', '" . $i . "', '" . GetFormData($f, $s, $type . $i) . "')");
			}
			QuickUpdate("commit");
			redirect("contactsettings.php");
		}
	}
} else {
	$reloadform = 1;
}

if($reloadform){
	ClearFormData($f);
	$labels = fetch_labels($type, true);
	for($i=0; $i<$max; $i++){
		PutFormData($f, $s, $type . $i, isset($labels[$i]) ? $labels[$i] : "", "text");
	}
}
////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////




////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$TITLE="Destination Labels - " . ucfirst_withexceptions($type);
$PAGE = "admin:contactsettings";
include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, $s, "Done"));
startWindow("Labels");
?>
<table border="0" cellpadding="3" cellspacing="1">
	<tr class="listheader">
		<th>Destination</th>
		<th>Label</th>
	</tr>
<?
	for($i=0; $i< $max; $i++){
?>
		<tr>
			<td><?=ucfirst_withexceptions($type)?>&nbsp;<?=$i+1?></td>
			<td><? NewFormItem($f, $s, $type . $i, "text", 10, 20);?></td>
		</tr>
<?
	}
?>
</table>
<?
endWindow();
buttons();
EndForm();
?>