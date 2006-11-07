<?
include_once("common.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/html.inc.php");
include_once("../inc/utils.inc.php");

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


function genpassword() {
	$digits = 6;
	$passwd = "";
	$chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyzabcdefghijklmnopqrstuvwxyz";
	while ($digits--) {
		$passwd .= $chars[mt_rand(0,strlen($chars)-1)];
	}
	return $passwd;
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$f = "customer";
$s = "main";

$reloadform = 0;

// If user submitted the form
if (CheckFormSubmit($f,$s)){

	MergeSectionFormData($f,$s);

	// Checks to see if user left out any of the required fields
	if( CheckFormSection($f, $s) ) {
		error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
	}else{

		$displayname = GetFormData($f,$s,"name");
		$timezone = GetFormData($f, $s, "timezone");
		$hostname = GetFormData($f, $s, "hostname");
		$inboundnum = GetFormData($f, $s, "inboundnumber");
		$user = GetFormData($f, $s, "user");
		$cust_pass = GetFormData($f, $s, "password");
		$autoname = GetFormData($f,$s,"autoname");
		$autoemail = GetFormData($f,$s,"autoemail");

		if (QuickQuery("SELECT COUNT(*) FROM customer WHERE inboundnumber=" . DBSafe($inboundnum) . "")) {
			error('Entered 800 Number Already being used', 'Please Enter Another');
		} else if (QuickQuery("SELECT COUNT(*) FROM customer WHERE hostname='" . DBSafe($hostname) ."'")) {
			error('URL Path Already exists', 'Please Enter Another');
		} else if (GetFormData($f,$s,"code") != "joplin555") {
			error('Bad Secret Code', 'Try Again');
		} else if ($cust_pass != GetFormData($f,$s,"password2")) {
			error('Password and Confirmation Password do not match', 'Try Again');
		} else if (strlen($inboundnum) > 0 && !ereg("[0-9]{10}",$inboundnum)) {
			error('Bad 800 Number Format', 'Try Again');
		} else if (strlen($autoemail) > 0 && CheckFormItem($f,$s,"autoemail") != 0) {
			error('Bad E-mail Format', 'Try Again');
		} else {

			$query = "insert into customer (name,enabled,timezone,hostname,inboundnumber) VALUES
						('" . DBSafe($displayname) . "',1,
						'" . DBSafe($timezone) . "',
						'" . DBSafe($hostname) . "',
						'" . DBSafe($inboundnum) . "')";
			QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);
			$custid = mysql_insert_id();

			$query = "insert into access (name,customerid) values ('System Administrators', $custid)";
			QuickUpdate($query) or die( "ERROR:" . mysql_error());
			$accessid = mysql_insert_id();

			$query = "INSERT INTO `permission` (accessid,name,value) VALUES "
					. "($accessid, 'loginweb', '1'),"
					. "($accessid, 'manageprofile', '1'),"
					. "($accessid, 'manageaccount', '1'),"
					. "($accessid, 'manageaccount', '1'),"
					. "($accessid, 'managesystem', '1'),"
					. "($accessid, 'loginphone', '1'),"
					. "($accessid, 'startstats', '1'),"
					. "($accessid, 'startshort', '1'),"
					. "($accessid, 'starteasy', '1'),"
					. "($accessid, 'sendprint', '0'),"
					. "($accessid, 'callmax', '10'),"
					. "($accessid, 'sendemail', '1'),"
					. "($accessid, 'sendphone', '1'),"
					. "($accessid, 'sendmulti', '1'),"
					. "($accessid, 'createlist', '1'),"
					. "($accessid, 'createrepeat', '1'),"
					. "($accessid, 'createreport', '1'),"
					. "($accessid, 'datafields', 'f01|f02|f03'),"
					. "($accessid, 'maxjobdays', '7'),"
					. "($accessid, 'viewsystemreports', '1'),"
					. "($accessid, 'managesystemjobs', '1'),"
					. "($accessid, 'managemyaccount', '1'),"
					. "($accessid, 'viewcontacts', '1'),"
					. "($accessid, 'viewsystemactive', '1'),"
					. "($accessid, 'viewsystemrepeating', '1'),"
					. "($accessid, 'viewsystemcompleted', '1'),"
					. "($accessid, 'listuploadids', '1'),"
					. "($accessid, 'listuploadcontacts', '1'),"
					. "($accessid, 'setcallerid', '1'),"
					. "($accessid, 'blocknumbers', '1'),"
					. "($accessid, 'callblockingperms', 'editall'),"
					. "($accessid, 'metadata', '1'),"
					. "($accessid, 'managetasks', '1');"
					;
			QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);

			$query = "INSERT INTO `user` (`accessid`, `login`, `password`, `customerid`, `firstname`, `lastname`, `enabled`, `deleted`) VALUES
						( $accessid , '" . DBSafe($user) . "',
						password('" . DBSafe($cust_pass) . "') ,
						$custid, 'System', 'Administrator', 1 ,0)";
			QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);

			$query = "INSERT INTO `fieldmap` (`customerid`, `fieldnum`, `name`, `options`) VALUES
						($custid, 'f01', 'First Name', 'searchable,text'),
						($custid, 'f02', 'Last Name', 'searchable,text'),
						($custid, 'f03', 'Language', 'searchable,multisearch')";
			QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);

			$query = "INSERT INTO `language` (`customerid`, `name`, `code`) VALUES
						($custid, 'English', ''),
						($custid, 'Spanish', '')";
			QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);


			$query = "INSERT INTO `jobtype` (`customerid`, `name`, `priority`, `systempriority`, `deleted`) VALUES
						($custid, 'Emergency', 10000, 1, 0),
						($custid, 'Attendance', 20000, 2, 0),
						($custid, 'General', 30000, 3, 0)";
			QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);

			$query = "INSERT INTO `setting` (`customerid`, `name`, `value`) VALUES
						($custid, 'autoreport_replyemail', '" . DBSafe($autoemail) ."'),
						($custid, 'autoreport_replyname', '" . DBSafe($autoname) . "')";
			QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);

			redirect("customers.php");
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform ){

	ClearFormData($f);

	PutFormData($f,$s,'name',"","text",1,50,true);
	PutFormData($f,$s,'hostname',"","text",5,255,true);
	PutFormData($f,$s,'inboundnumber',"","text",10,10);
	PutFormData($f,$s,'user', "","text",1,20,true);
	$password = genpassword();
	PutFormData($f,$s,'password',$password,"text",1,255,true);
	PutFormData($f,$s,'password2',$password,"text",1,255,true);
	PutFormData($f,$s,'code', "","password",1,9,true);
	PutFormData($f,$s,'autoname', "","text",1,50);
	PutFormData($f,$s,'autoemail', "","email",1,255);

}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
include_once("nav.inc.php");

?>
<html>
<body>

<?

NewForm($f);
buttons(submit($f, $s));

?>

<table>
<tr><td>Customer display name: </td><td> <? NewFormItem($f, $s, 'name', 'text', 25, 50); ?></td></tr>
<tr><td>URL path name: </td><td><? NewFormItem($f, $s, 'hostname', 'text', 25, 255); ?> (Must be 5 or more characters)</td></tr>
<tr><td>800 inbound number: </td><td><? NewFormItem($f, $s, 'inboundnumber', 'text', 10, 10); ?></td></tr>
<tr><td>Admin username: </td><td><? NewFormItem($f, $s, 'user', 'text', 20); ?> (admin is ok)</td></tr>
<tr><td>Admin password: </td><td><? NewFormItem($f, $s, 'password', 'text', 25,255); ?> (generated automatically, be sure to write this down)</td></tr>
<tr><td>Password verify:</td><td><? NewFormItem($f, $s, 'password2', 'text', 25,255); ?> </td></tr>

<tr><td>Reliance secret code: </td><td><? NewFormItem($f, $s, 'code', 'password', 25); ?></td></tr>
<tr><td>Timezone: </td><td>
<? NewFormItem($f, $s, 'timezone', "selectstart");
   foreach($timezones as $timezone)
       NewFormItem($f, $s, 'timezone', "selectoption", $timezone, $timezone);
   NewFormItem($f, $s, 'timezone', "selectend");
?>
</td></tr>

<tr><td>AutoReport Name: </td><td><? NewFormItem($f,$s,'autoname','text',25,50); ?></td></tr>
<tr><td>AutoReport Email: </td><td><? NewFormItem($f,$s,'autoemail','text',25,255); ?></td></tr>

</table>

<?

buttons();
EndForm();

if(isset($ERRORS) && is_array($ERRORS)) {
	foreach($ERRORS as $key => $value) {
		$ERRORS[$key] = addslashes($value);
	}
	print '<script language="javascript">window.alert(\'' . implode('.\n', $ERRORS) . '.\');</script>';
}
?>
</body>
</html>
<?
include_once("navbottom.inc.php");
?>

