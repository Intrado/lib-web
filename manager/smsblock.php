<?
include_once("common.inc.php");
include_once("../obj/Phone.obj.php");
include_once("../obj/Customer.obj.php");
include_once("../inc/table.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/formatters.inc.php");
include_once("../inc/html.inc.php");
include_once("AspAdminUser.obj.php");
require_once("XML/RPC.php");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$accountcreator = new AspAdminUser($_SESSION['aspadminuserid']);

$number="";
if(isset($_GET['sms'])){
	$number = $_GET['sms']+0;
}

$f = "smsblock";
$s = "main";
$reloadform = 0;
$error = 0;

$data = array();
$titles = array();
$formatters = array();

if(CheckFormSubmit($f, "search") || CheckFormSubmit($f, "operate"))
{
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$error = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
			if(CheckFormSubmit($f, "operate"))
				$number = CheckFormSubmit($f, "operate");
		} else if(CheckFormSubmit($f, "operate") && !$accountcreator->runCheck(GetFormData($f, $s, 'managerpassword'))) {
			error('Bad Manager Password');
		} else {
			if(CheckFormSubmit($f, "operate")){
				$number = ereg_replace("[^0-9]*","",CheckFormSubmit($f, "operate"));
			} else {
				$number = ereg_replace("[^0-9]*","",GetFormData($f, $s, "number"));
			}
			if(strlen($number) != 10){
				error("Invalid phone number");
				$error = 1;
			} else if ($number[0] < 2 || $number[3] < 2){ //check for valid looking area code and prefix
				error("The phone number seems to be invalid");
				$error = 1;
			} else {
				if(CheckFormSubmit($f, "operate")){
					blocksms($number, GetFormData($f, $s, "operation"), GetFormData($f, $s, "notes"));
				}
				redirect("?sms=$number");
			}
		}
	}
}

//Always reload the form
if(!$error){
	ClearFormData($f);
	PutFormData($f, $s, "operation", "", "text");
	PutFormData($f, $s, "number", $number, "phone");
	PutFormData($f, "search", "Go!", "");
	PutFormData($f, "operate", "Make it so...", "");
	PutFormData($f, $s, "managerpassword", "", "text");
	PutFormData($f, $s, "sms", $number, "phone");
}

// Search if number is valid
if($number && !$error){
	$shard = QuickQueryRow("select id, dbhost, dbusername, dbpassword from shard limit 1");

	$sharddb = mysql_connect($shard[1],$shard[2], $shard[3], true)
		or die("Could not connect to shard database: " . mysql_error());
	mysql_select_db("aspshard", $sharddb);
	$data = QuickQueryRow("select sms, status, lastupdate, notes from smsblock where sms like '" . DBSafe($number) . "%'", false, $sharddb);

}

// Customer phone formatter function because we can't use phone.obj.php
function fmt_phone_number($number){
	if(strlen($number) == 10)
		return "(" . substr($number,0,3) . ") " . substr($number,3,3) . "-" . substr($number,6,4);
	else
		return "";
}

function fmt_block_status($status){
	if($status == 'pendingoptin'){
		return "Pending Opt-In";
	} else {
		return ucfirst($status);
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

include_once("nav.inc.php");
//Only require manager password if user can block

if(!$number){
	NewForm($f);
} else {
	NewForm($f,"onSubmit='if(new getObj(\"managerpassword\").obj.value == \"\"){ window.alert(\"Enter Your Manager Password\"); return false;}'");
}
?>
<div>SMS Block</div>
<?
if(!$number){
?>
<table>
	<tr>
		<td>Number:</td>
		<td><? NewFormItem($f, $s, "number", "text", 14); ?></td>
		<td><? NewFormItem($f, "search", "Go!", "submit")?></td>
	</tr>
</table>
<?
}
if($number){
?>
	<table class="list">
		<tr>
			<th class="listheader">SMS</th>
			<th class="listheader">Status</th>
			<th class="listheader">Last Modified</th>
			<th class="listheader">Notes</th>
		</tr>
		<tr>
			<td><?= $data ? fmt_phone_number($data[0]) : fmt_phone_number($number) ?></td>
			<td><?= $data ? fmt_block_status($data[1]) : "No Record" ?></td>
			<td><?= $data ? fmt_date($data, 2) : "" ?></td>
			<td><?= $data ? $data[3] : ""?></td>
		</tr>
	</table>
	<table>
		<tr>
			<td>Operation:</td>
			<td>
<?
				NewFormItem($f, $s, "operation", "selectstart");
				NewFormItem($f, $s, "operation", "--Select an Operation--", "");
				//index 1 is status
				if($data && $data[1] == 'block'){
					NewFormItem($f, $s, "operation", "selectoption", "Opt-In", "optin");
				} else if($data && $data[1] == 'optin'){
					NewFormItem($f, $s, "operation", "selectoption", "Block", "block");
				} else {
					NewFormItem($f, $s, "operation", "selectoption", "Opt-In", "optin");
					NewFormItem($f, $s, "operation", "selectoption", "Block", "block");
				}
				NewFormItem($f, $s, "operation", "selectend");
?>
			</td>
		</tr>
		<tr>
			<td>Notes:</td>
			<td>
				<?
					PutFormData($f, $s, "notes", "", "text", "nomin", "nomax", true);
					NewFormItem($f, $s, "notes", "textarea", 40, 3);
				?>
			</td>
		</tr>
		<tr>
			<td><? NewFormItem($f, "operate", "Make it so...", "submit", null, null, "onclick='submitForm(\"$f\", \"operate\",\"$number\"); return false;'")?></td>
		</tr>
	</table>
<?
	managerpassword($f, $s);
}
EndForm();
include_once("navbottom.inc.php");



// XML-RPC functions
function pearxmlrpc($method, $params) {
	global $SETTINGS;
	$authhost = $SETTINGS['authserver']['host'];

	$msg = new XML_RPC_Message($method, $params);

	$cli = new XML_RPC_Client('/xmlrpc', $authhost);

	$resp = $cli->send($msg);

	if (!$resp) {
    	error_log($method . ' communication error: ' . $cli->errstr);
	} else if ($resp->faultCode()) {
		error_log($method . ' Fault Code: ' . $resp->faultCode() . ' Fault Reason: ' . $resp->faultString());
	} else {
		$val = $resp->value();
    	$data = XML_RPC_decode($val);
		if ($data['result'] == "") {
			// success
			return $data;
		} else if ($data['result'] == "warning") {
			// warning we do not log, but handle like failure
		} else {
			// error
			error_log($method . " " .$data['result']);
		}
	}
	return false;
}

function blocksms($sms, $action, $notes){
	$params = array(new XML_RPC_Value($sms, 'string'), new XML_RPC_Value($action, 'string'), new XML_RPC_Value($notes, 'string'));
	$method = "AuthServer.updateBlockedNumber";
	$result = pearxmlrpc($method, $params);
	if ($result !== false) {
		// success
		return $result['result'];
	}
	return false;
}

?>
<script>
function submitForm (formname,section,value) {
	var theform = document.forms[formname];
	//make a new hidden element to emulate the data that would normally be passed back from a submit button
	var submit = document.createElement('input');
	submit.setAttribute('name','submit[' + formname  + '][' + section + ']');
	submit.value= value == undefined ? 'Submit' : value;
	submit.setAttribute('type','hidden');
	theform.appendChild(submit);
	if(!(theform.onsubmit && theform.onsubmit() == false)){
		theform.submit();
	}
}
</script>
