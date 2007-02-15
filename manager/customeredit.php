<?
include_once("common.inc.php");
include_once("../obj/Customer.obj.php");
include_once("../inc/form.inc.php");
include_once("../inc/html.inc.php");
include_once("../inc/utils.inc.php");
include_once("../obj/Phone.obj.php");
include_once("AspAdminUser.obj.php");

if (isset($_GET['id'])) {
	$_SESSION['currentid']= $_GET['id']+0;
	redirect();
}

$f = "customer";
$s = "edit";
$currentid = $_SESSION['currentid'];
$accountcreator = new AspAdminUser($_SESSION['aspadminuserid']);

$timezones = array(	"US/Alaska",
					"US/Aleutian",
					"US/Arizona",
					"US/Central",
					"US/East-Indiana",
					"US/Eastern",
					"US/Hawaii",
					"US/Indiana-Starke",
					"US/Michigan",
					"US/Mountain",
					"US/Pacific",
					"US/Samoa"	);
$reloadform = 0;
// Checking to see if customer id is in the database
if( !QuickQuery("SELECT COUNT(*) FROM customer WHERE id = $currentid")) {
	exit("Cannot find record of customer in database");
}

if(CheckFormSubmit($f,$s)) {
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);

		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {

			$displayname = GetFormData($f,$s,"name");
			$timezone = GetFormData($f, $s, "timezone");
			$hostname = GetFormData($f, $s, "hostname");
			$inboundnumber  = GetFormData($f, $s, "inboundnumber");
			$maxphones = GetFormData($f, $s, "maxphones");
			$maxemails = GetFormData($f, $s, "maxemails");
			$callerid = GetFormData($f, $s, "callerid");
			$areacode = GetFormData($f, $s, "areacode");
			$retry = GetFormData($f, $s, "retry");
			$autoname = GetFormData($f, $s, 'autoname');
			$autoemail = GetFormData($f, $s, 'autoemail');
			$renewaldate = GetFormData($f, $s, 'renewaldate');
			$callspurchased = GetFormData($f, $s, 'callspurchased');
			$managerpassword = GetFormData($f, $s, 'managerpassword');
			$surveyurl = GetFormData($f, $s, 'surveyurl');

			$currmaxphone = getCustomerSystemSetting('maxphones', $currentid, 4);
			$currmaxemail = getCustomerSystemSetting('maxemails', $currentid, 2);

			if (QuickQuery("SELECT COUNT(*) FROM customer WHERE inboundnumber=" . DBSafe($inboundnumber) . " AND id != $currentid")) {
				error('Entered 800 Number Already being used', 'Please Enter Another');
			} else if (QuickQuery("SELECT COUNT(*) FROM customer WHERE hostname='" . DBSafe($hostname) ."' AND id != $currentid")) {
				error('URL Path Already exists', 'Please Enter Another');
			} else if (strlen($inboundnumber) > 0 && !ereg("[0-9]{10}",$inboundnumber)) {
				error('Bad Toll Free Number Format, Try Again');
			} else if(!$accountcreator->runCheck($managerpassword)) {
				error('Bad Manager Password');
			} else {
				$query="UPDATE customer SET name='" . DBSafe($displayname) . "',
						hostname='" . DBSafe($hostname) . "',
						inboundnumber='" . DBSafe($inboundnumber) . "',
						timezone='" . DBSafe($timezone) . "' WHERE id = $currentid";
				Query($query) or die("ERROR: " . mysql_query() . " SQL:" . $query);

				setCustomerSystemSetting("maxphones", $maxphones, $currentid);
				setCustomerSystemSetting("maxemails", $maxemails, $currentid);
				setCustomerSystemSetting('retry', $retry, $currentid);
				setCustomerSystemSetting('callerid', Phone::parse($callerid), $currentid);
				setCustomerSystemSetting('defaultareacode', $areacode, $currentid);
				setCustomerSystemSetting('autoreport_replyname', $autoname, $currentid);
				setCustomerSystemSetting('autoreport_replyemail', $autoemail, $currentid);
				setCustomerSystemSetting('surveyurl', $surveyurl);

				if($renewaldate != "" || $renewaldate != NULL){
					if($renewaldate = strtotime($renewaldate)) {
						$renewaldate = date("Y-m-d", $renewaldate);
					}
				}
				setCustomerSystemSetting('_renewaldate', $renewaldate, $currentid);
				setCustomerSystemSetting('_callspurchased', $callspurchased, $currentid);

				redirect();
			}

		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform ) {

	ClearFormData($f);

	$custfields = QuickQueryRow("SELECT name, hostname, inboundnumber, timezone FROM customer WHERE customer.id = $currentid");
	PutFormData($f,$s,'name',$custfields[0],"text",1,50,true);
	PutFormData($f,$s,'hostname',$custfields[1],"text",5,255,true);
	PutFormData($f,$s,'inboundnumber',$custfields[2],"phone",10,10);
	PutFormData($f,$s,'timezone', $custfields[3], "text", 1, 255);

	PutFormData($f,$s,'callerid', Phone::format(getCustomerSystemSetting('callerid', $currentid)),"phone",10,10);
	PutFormData($f,$s,'areacode', getCustomerSystemSetting('defaultareacode', $currentid),"phone", 3, 3);


	$currentmaxphone = getCustomerSystemSetting('maxphones', $currentid, 4);
	PutFormData($f,$s,'maxphones',$currentmaxphone,"number",$currentmaxphone,"nomax",true);
	$currentmaxemail = getCustomerSystemSetting('maxemails', $currentid, 2);
	PutFormData($f,$s,'maxemails',$currentmaxemail,"number",$currentmaxemail,"nomax",true);

	PutFormData($f,$s,'autoname', getCustomerSystemSetting('autoreport_replyname', $currentid),"text",1,255);
	PutFormData($f,$s,'autoemail', getCustomerSystemSetting('autoreport_replyemail', $currentid),"email",1,255);

	PutFormData($f,$s,'renewaldate', getCustomerSystemSetting('_renewaldate', $currentid), "text", 1, 255);
	PutFormData($f,$s,'callspurchased', getCustomerSystemSetting('_callspurchased', $currentid), "number");

	PutFormData($f,$s,"retry", getCustomerSystemSetting('retry', $currentid),"number",5,240);
	PutFormData($f,$s,"surveyurl", getCustomerSystemSetting('surveyurl', $currentid), "text", 0, 100);
}

include_once("nav.inc.php");

NewForm($f);
NewFormItem($f, $s,"", 'submit');

?>

<table>
<tr><td>Customer display name: </td><td> <? NewFormItem($f, $s, 'name', 'text', 25, 50); ?></td></tr>
<tr><td>URL path name: </td><td><? NewFormItem($f, $s, 'hostname', 'text', 25, 255); ?> (Must be 5 or more characters)</td></tr>
<tr><td>800 inbound number: </td><td><? NewFormItem($f, $s, 'inboundnumber', 'text', 10, 10); ?></td></tr>
<tr><td>Timezone: </td><td>
<?
	NewFormItem($f, $s, 'timezone', "selectstart");
	foreach($timezones as $timezone) {
		NewFormItem($f, $s, 'timezone', "selectoption", $timezone, $timezone);
	}
	NewFormItem($f, $s, 'timezone', "selectend");
?>
</td></tr>
<tr><td>Default Caller ID: </td><td> <? NewFormItem($f, $s, 'callerid', 'text', 25, 255) ?></td></tr>
<tr><td>Default Area Code: </td><td> <? NewFormItem($f, $s, 'areacode', 'text', 25, 255) ?></td></tr>
<tr><td>AutoReport Name: </td><td><? NewFormItem($f,$s,'autoname','text',25,50); ?></td></tr>
<tr><td>AutoReport Email: </td><td><? NewFormItem($f,$s,'autoemail','text',25,255); ?></td></tr>
<tr><td>Survey URL: </td><td><? NewFormItem($f, $s, 'surveyurl', 'text', 30, 100); ?></td></tr>
<tr><td>Max Phones: </td><td> <? NewFormItem($f, $s, 'maxphones', 'text', 25, 255) ?></td></tr>
<tr><td>Max E-mails: </td><td> <? NewFormItem($f, $s, 'maxemails', 'text', 25, 255) ?></td></tr>
<tr><td>Renewal Date: </td><td><? NewFormItem($f, $s, 'renewaldate', 'text', 25, 255) ?></td></tr>
<tr><td>Calls Purchased: </td><td><? NewFormItem($f, $s, 'callspurchased', 'text', 25, 255) ?></td></tr>
<tr><td>Retry:

<?
	NewFormItem($f,$s,'retry','selectstart');
		NewFormItem($f,$s,'retry','selectoption',5,5);
		NewFormItem($f,$s,'retry','selectoption',10,10);
		NewFormItem($f,$s,'retry','selectoption',15,15);
		NewFormItem($f,$s,'retry','selectoption',30,30);
		NewFormItem($f,$s,'retry','selectoption',60,60);
		NewFormItem($f,$s,'retry','selectoption',90,90);
		NewFormItem($f,$s,'retry','selectoption',120,120);
	NewFormItem($f,$s,'retry','selectend');
?>
</td></tr>
</table>

<?

NewFormItem($f, $s,"", 'submit');
?><p>Manager Password: </td><td><? NewFormItem($f, $s, 'managerpassword', 'password', 25); ?><p><?
EndForm();

if(isset($ERRORS) && is_array($ERRORS)) {
	foreach($ERRORS as $key => $value) {
		$ERRORS[$key] = addslashes($value);
	}
	print '<script language="javascript">window.alert(\'' . implode('.\n', $ERRORS) . '.\');</script>';
}

include_once("navbottom.inc.php");

/************FUNCTIONS***********/
function setCustomerSystemSetting($name, $value, $currid) {
	$old = getCustomerSystemSetting($name, $currid);
	$name = DBSafe($name);
	$value = DBSafe($value);
	if($old === false) {
		QuickUpdate("insert into setting (customerid, name, value) values ('$currid', '$name', '$value')");
	} else {
		QuickUpdate("update setting set value = '$value' where customerid = '$currid' and name = '$name'");

	}
}

?>
