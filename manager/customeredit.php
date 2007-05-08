<?
include_once("common.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/html.inc.php");
include_once("../inc/utils.inc.php");
include_once("../obj/Phone.obj.php");
include_once("AspAdminUser.obj.php");

if (isset($_GET['id'])) {
	$_SESSION['currentid']= $_GET['id']+0;
	redirect();
}
$accountcreator = new AspAdminUser($_SESSION['aspadminuserid']);
if(isset($_SESSION['currentid'])) {
	$currentid = $_SESSION['currentid'];
	$custquery = Query("select dbhost, dbusername, dbpassword, hostname from customer where id = '$currentid'");
	$custinfo = mysql_fetch_row($custquery);
	$custdb = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_$currentid");
	if(!$custdb) {
		exit("Connection failed for customer: $custinfo[0], db: c_$currentid");
	}
}

$f = "customer";
$s = "edit";

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

$refresh = 0;
$languages = QuickQueryList("select id, name from language order by name", true, $custdb);

if(CheckFormSubmit($f,"Save") || CheckFormSubmit($f, "Return")) {
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
			$maxusers = GetFormData($f, $s, 'maxusers');

			if (($inboundnumber != "") && QuickQuery("SELECT COUNT(*) FROM customer WHERE inboundnumber ='" . DBSafe($inboundnumber) . "' and id != '" . $currentid . "'")) {
				error('Entered 800 Number Already being used', 'Please Enter Another');
			} else if (QuickQuery("SELECT COUNT(*) FROM customer WHERE hostname='" . DBSafe($hostname) ."' AND id != $currentid")) {
				error('URL Path Already exists', 'Please Enter Another');
			} else if (strlen($inboundnumber) > 0 && !ereg("[0-9]{10}",$inboundnumber)) {
				error('Bad Toll Free Number Format, Try Again');
			} else if ((strlen($custinfo[3]) >= 5) && (strlen($hostname) < 5)){
				error('Customer URL\'s cannot be shorter than 5 unless their account was already made');			
			} else if(!$accountcreator->runCheck($managerpassword)) {
				error('Bad Manager Password');
			} else {

				QuickUpdate("update customer set hostname = '" . DBSafe($hostname) ."' where id = '$currentid'");
				QuickUpdate("update customer set inboundnumber = '" . DBSafe($inboundnumber) ."' where id = '$currentid'");
				setCustomerSystemSetting("displayname", $displayname, $custdb);
				setCustomerSystemSetting("inboundnumber", $inboundnumber, $custdb);
				setCustomerSystemSetting("timezone", $timezone, $custdb);
				setCustomerSystemSetting("maxphones", $maxphones, $custdb);
				setCustomerSystemSetting("maxemails", $maxemails, $custdb);
				setCustomerSystemSetting('retry', $retry, $custdb);
				setCustomerSystemSetting('callerid', Phone::parse($callerid), $custdb);
				setCustomerSystemSetting('defaultareacode', $areacode, $custdb);
				setCustomerSystemSetting('autoreport_replyname', $autoname, $custdb);
				setCustomerSystemSetting('autoreport_replyemail', $autoemail, $custdb);
				setCustomerSystemSetting('surveyurl', $surveyurl, $custdb);
				

				if($renewaldate != "" || $renewaldate != NULL){
					if($renewaldate = strtotime($renewaldate)) {
						$renewaldate = date("Y-m-d", $renewaldate);
					}
				}
				setCustomerSystemSetting('_renewaldate', $renewaldate, $custdb);
				setCustomerSystemSetting('_callspurchased', $callspurchased, $custdb);
				setCustomerSystemSetting('_maxusers', $maxusers, $custdb);
				$oldlanguages = GetFormData($f, $s, "oldlanguages");
				foreach($oldlanguages as $oldlanguage){
					$lang = "Language" . $oldlanguage;
					if(GetFormData($f, $s, $lang) === "") {
						QuickUpdate("delete from language where id = $oldlanguage", $custdb);
					} else {
						QuickUpdate("update language set name='" . GetFormData($f, $s, $lang) . "' where id = '" . $oldlanguage . "'", $custdb);
					}
				}
				if(GetFormData($f,$s, "newlang")!=""){
					QuickUpdate("insert into language(name) values ('" . GetFormData($f, $s, "newlang") . "')", $custdb);
				}
				if(CheckFormSubmit($f, "Return")){
					redirect("customers.php");
				} else {
					$reloadform=1;
					$refresh = 1;
				}
			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform ) {

	ClearFormData($f);
	if($refresh){
		$languages = QuickQueryList("select id, name from language order by name", true, $custdb);
	}
	PutFormData($f,$s,'name',getCustomerSystemSetting('displayname', "", true, $custdb),"text",1,50,true);
	PutFormData($f,$s,'hostname',$custinfo[3],"text",1,255,true);
	PutFormData($f,$s,'inboundnumber',getCustomerSystemSetting('inboundnumber', 4, true, $custdb),"phone",10,10);
	PutFormData($f,$s,'timezone', getCustomerSystemSetting('timezone', 4, true, $custdb), "text", 1, 255);

	PutFormData($f,$s,'callerid', Phone::format(getCustomerSystemSetting('callerid', false, true, $custdb)),"phone",10,10);
	PutFormData($f,$s,'areacode', getCustomerSystemSetting('defaultareacode', false, true, $custdb),"phone", 3, 3);

	$currentmaxphone = getCustomerSystemSetting('maxphones', 4, true, $custdb);
	PutFormData($f,$s,'maxphones',$currentmaxphone,"number",3,"nomax",true);
	$currentmaxemail = getCustomerSystemSetting('maxemails', 2, true, $custdb);
	PutFormData($f,$s,'maxemails',$currentmaxemail,"number",2,"nomax",true);

	PutFormData($f,$s,'autoname', getCustomerSystemSetting('autoreport_replyname', false, true, $custdb),"text",1,255);
	PutFormData($f,$s,'autoemail', getCustomerSystemSetting('autoreport_replyemail', false, true, $custdb),"email",1,255);

	PutFormData($f,$s,'renewaldate', getCustomerSystemSetting('_renewaldate', false, true, $custdb), "text", 1, 255);
	PutFormData($f,$s,'callspurchased', getCustomerSystemSetting('_callspurchased', false, true, $custdb), "number");

	PutFormData($f,$s,"retry", getCustomerSystemSetting('retry', false, true, $custdb),"number",5,240);
	PutFormData($f,$s,"surveyurl", getCustomerSystemSetting('surveyurl', false, true, $custdb), "text", 0, 100);
	PutFormData($f,$s,"maxusers", getCustomerSystemSetting('_maxusers', false, true, $custdb), "text", 0, 100);
	
	$oldlanguages = array();
	foreach($languages as $index => $language){
		$oldlanguages[] = $index;
		$lang = "Language" . $index;
		PutFormData($f, $s, $lang, $language, "text");
	}
	PutFormData($f, $s, "oldlanguages", $oldlanguages);
	PutFormData($f, $s, "newlang", "", "text");
	PutformData($f, $s, "managerpassword", "", "text");
}

include_once("nav.inc.php");

NewForm($f);

?>
<br>
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
<tr><td>Users Purchased: </td><td><? NewFormItem($f, $s, 'maxusers', 'text', 25, 255) ?></td></tr>

<?
	
	foreach($languages as $index => $language){
		$lang = "Language" . $index;
		?><tr><td><?=$lang?></td><td><? NewFormItem($f, $s, $lang, 'text', 25, 50) ?></td></tr><?
	}
?>
<tr><td>New Language: </td><td><? NewFormItem($f, $s, 'newlang', 'text', 25, 50) ?></td></tr>

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
<td></tr>
<tr>
	<td><? NewFormItem($f, "Save","Save", 'submit');?> </td>
	<td><? NewFormItem($f, "Return","Save and Return", 'submit');?></td>
</tr>

</table>

<p>Manager Password: <? NewFormItem($f, $s, 'managerpassword', 'password', 25); ?></p><?
EndForm();

include_once("navbottom.inc.php");

/************FUNCTIONS***********/
function setCustomerSystemSetting($name, $value, $custdb) {
	$old = getCustomerSystemSetting($name, false, true, $custdb);
	$name = DBSafe($name);
	$value = DBSafe($value);
	if($old === false) {
		QuickUpdate("insert into setting (name, value) values ('$name', '$value')", $custdb);
	} else {
		QuickUpdate("update setting set value = '$value' where name = '$name'", $custdb);
	}
}
	

?>
