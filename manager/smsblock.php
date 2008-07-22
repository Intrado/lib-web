<?
include_once("common.inc.php");
include_once("../obj/Customer.obj.php");
include_once("../inc/table.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/formatters.inc.php");
include_once("../inc/html.inc.php");
require_once("XML/RPC.php");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$f = "smsblock";
$s = "main";
$reloadform = 0;
$number = "";
$error = 0;

$data = array();
$titles = array();
$formatters = array();

if(CheckFormSubmit($f, $s) || CheckFormSubmit($f, "unblock") || CheckFormSubmit($f, "block") || CheckFormSubmit($f, "optin"))
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
			$error = 1;
		} else {

			if(CheckFormSubmit($f, $s)){
				$number = ereg_replace("[^0-9]*","",GetFormData($f, $s, "number"));
				if(GetFormData($f, $s, "operation") == "block"){
					if(strlen(ereg_replace("[^0-9]*","",$number)) != 10){
						error("Invalid phone number");
						$error=1;
					} else {
						blocksms($number, "block");
					}
				}
			} else if(CheckFormSubmit($f, "unblock")){
				$number = CheckFormSubmit($f, "unblock");
				blocksms($number, 'unblock');
			} else if(CheckFormSubmit($f, "block")){
				$number = CheckFormSubmit($f, "block");
				blocksms($number, 'block');
			} else if(CheckFormSubmit($f, "optin")){
				$number = CheckFormSubmit($f, "optin");
				blocksms($number, 'unblock');
			}
			// Search all shard databases for number that was operated on

			$res = Query("select id, dbhost, dbusername, dbpassword from shard limit 1");
			$shardinfo = array();
			while($row = DBGetRow($res)){
				$shardinfo[$row[0]] = array($row[1], $row[2], $row[3]);
			}
			$data = array();
			foreach($shardinfo as $shardid => $shard) {

				$sharddb = mysql_connect($shard[0],$shard[1], $shard[2], true)
					or die("Could not connect to shard database: " . mysql_error());
				mysql_select_db("aspshard");
				$res = Query("select sms, status, lastupdate from smsblock where sms like '" . DBSafe($number) . "%'", $sharddb);

				while($row = DBGetRow($res)){
					$data[] = $row;
				}
			}
		}
	}
}

//Always reload the form
if(!$error){
	ClearFormData($f);
	PutFormData($f, $s, "operation", "search");
	PutFormData($f, $s, "number", isset($number) ? $number : "", "phone");
	PutFormData($f, $s, "Submit", "");
	PutFormData($f, $s, "Unblock", "", "text");
	PutFormData($f, $s, "Block", "", "text");
	PutFormData($f, $s, "Optin", "", "text");
	PutFormData($f, $s, "managerpassword", "text");
}



$titles = array(0 => "SMS",
				1 => "Status",
				2 => "Date",
				"actions" => "Actions");

$formatters = array(0 => "fmt_phone_number",
					1 => "fmt_block_status",
					2 => "fmt_date",
					"actions" => "fmt_sms_block_options");

// Customer phone formatter function because we can't use phone.obj.php
function fmt_phone_number($row, $index){
	if($row[$index])
		return "(" . substr($row[$index],0,3) . ") " . substr($row[$index],3,3) . "-" . substr($row[$index],6,4);
	else
		return "";
}


//index 1 is status
//index 0 is sms number
function fmt_sms_block_options($row,$index){
	global $f, $s;
	$urlarray = array();;
	if($row[1] == 'block'){
		$urlarray[] = "<a href='#' onclick=\"if(confirm('Are you sure you want to unblock this number?')) submitForm('" . $f . "','unblock','" . $row[0]. "'); else return false;\" >Unblock</a>";
	}
	if($row[1] == 'pendingoptin'){
		$urlarray[] = "<a href='#' onclick=\"if(confirm('Are you sure you want to block this number?')) submitForm('" . $f . "','block','" . $row[0]. "'); else return false;\" >Block</a>";
	}
	if($row[1] != 'pendingoptin'){
		$urlarray[] = "<a href='#' onclick=\"if(confirm('Are you sure you want to opt-in this number?')) submitForm('" . $f . "','optin','" . $row[0]. "'); else return false;\" >Opt-In</a>";
	}

	return implode("&nbsp|&nbsp", $urlarray);
}

function fmt_block_status($row, $index){
	if($row[$index] == 'pendingoptin'){
		return "Pending Opt-In";
	} else {
		return ucfirst($row[$index]);
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

include_once("nav.inc.php");
NewForm($f,"onSubmit='if(new getObj(\"managerpassword\").obj.value == \"\"){ window.alert(\"Enter Your Manager Password\"); return false;}'");
?>
<div>SMS Block</div>

<table>
	<tr>
		<td>Search:</td>
		<td><? NewFormItem($f, $s, "operation", "radio", null, "search"); ?></td>
	</tr>
	<tr>
		<td>Block Number:</td>
		<td><? NewFormItem($f, $s, "operation", "radio", null, "block"); ?></td>
	</tr>
	<tr>
		<td>Number:</td>
		<td><? NewFormItem($f, $s, "number", "text", 14) ?></td>
	</tr>
	<tr>
		<td><? NewFormItem($f, $s, "Submit", "submit")?></td>
	</tr>
</table>
<?
managerPassword($f, $s);
?>
<br>
<br>
<table class="list">
<?
		showTable($data, $titles, $formatters);
?>
</table>
<?

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

function blocksms($sms, $action){
	$params = array(new XML_RPC_Value($sms, 'string'), new XML_RPC_Value($action, 'string'));
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
