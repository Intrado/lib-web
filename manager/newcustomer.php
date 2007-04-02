<?
include_once("common.inc.php");
include_once("../inc/form.inc.php");
include_once("../inc/html.inc.php");
include_once("../inc/utils.inc.php");
include_once("AspAdminUser.obj.php");

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

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$f = "customer";
$s = "main";

$reloadform = 0;
$user = "schoolmessenger";
$accountcreator = new AspAdminUser($_SESSION['aspadminuserid']);

// If user submitted the form
if (CheckFormSubmit($f,$s)){
	if(CheckFormInvalid($f)) {
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	} else {
		MergeSectionFormData($f,$s);

		// Checks to see if user left out any of the required fields
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		}else{

			$displayname = GetFormData($f,$s,"name");
			$timezone = GetFormData($f, $s, "timezone");
			$hostname = GetFormData($f, $s, "hostname");
			$inboundnum = GetFormData($f, $s, "inboundnumber");
			$cust_pass = GetFormData($f, $s, "password");
			$managerpassword = GetFormData($f, $s, "managerpassword");

			if (QuickQuery("SELECT COUNT(*) FROM customer WHERE inboundnumber=" . DBSafe($inboundnum) . "")) {
				error('Entered 800 Number Already being used', 'Please Enter Another');
			} else if (QuickQuery("SELECT COUNT(*) FROM customer WHERE hostname='" . DBSafe($hostname) ."'")) {
				error('URL Path Already exists', 'Please Enter Another');
			} else if(!$accountcreator->runCheck($managerpassword)) {
				error('Bad Manager Password');
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

				$query = "INSERT INTO `user` (`accessid`, `login`, `password`, `customerid`,
							`firstname`, `lastname`, `enabled`, `deleted`) VALUES
							( '$accessid' , '$user',
							password('" . DBSafe($cust_pass) . "') ,
							'$custid', 'System', 'Administrator', 1 ,0)";
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


				$query = "INSERT INTO `jobtype` (`customerid`, `name`, `priority`, `systempriority`, timeslices, `deleted`) VALUES
							($custid, 'Emergency', 10000, 1, 50, 0),
							($custid, 'Attendance', 20000, 2, 0, 0),
							($custid, 'General', 30000, 3, 100, 0)";
				QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);

				$surveyurl = "http://asp.schoolmessenger.com/" . $hostname . "/survey/";
				$query = "INSERT INTO `setting` (`customerid`, `name`, `value`) VALUES
							($custid, 'autoreport_replyemail', '" . DBSafe($autoemail) ."'),
							($custid, 'autoreport_replyname', '" . DBSafe($autoname) . "'),
							($custid, 'maxphones', '3'),
							($custid, 'maxemails', '2'),
							($custid, 'retry', '15'),
							($custid, 'surveyurl', '" . DBSafe($surveyurl) . "')";
				QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);

				redirect("customers.php");
			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform ){

	ClearFormData($f);

	PutFormData($f,$s,'name',"","text",1,50);
	PutFormData($f,$s,'hostname',"","text",5,255);
	PutFormData($f,$s,'inboundnumber',"","text",10,10);
	PutFormData($f,$s,'password',"","text",1,255);
	PutFormData($f,$s,'password2',"","text",1,255);
	PutFormData($f,$s,'managerpassword',"", "text");
	PutFormData($f,$s,'timezone', "");
}

include_once("nav.inc.php");


NewForm($f);
NewFormItem($f, $s,"", 'submit');

?>

<table>
<tr><td>Customer display name: </td><td> <? NewFormItem($f, $s, 'name', 'text', 25, 50); ?></td></tr>
<tr><td>URL path name: </td><td><? NewFormItem($f, $s, 'hostname', 'text', 25, 255); ?> (Must be 5 or more characters)</td></tr>
<tr><td>Toll Free Inbound Number: </td><td><? NewFormItem($f, $s, 'inboundnumber', 'text', 10, 10); ?> Make Sure Not Taken</td></tr>
<tr><td>Admin username: </td><td>schoolmessenger</td></tr>
<tr><td>Admin password: </td><td><? NewFormItem($f, $s, 'password', 'text', 25,255); ?></td></tr>
<tr><td>Password verify:</td><td><? NewFormItem($f, $s, 'password2', 'text', 25,255); ?> </td></tr>


<tr><td>Timezone: </td><td>
<?
	NewFormItem($f, $s, 'timezone', "selectstart");
	foreach($timezones as $timezone) {
	   NewFormItem($f, $s, 'timezone', "selectoption", $timezone, $timezone);
	}
	NewFormItem($f, $s, 'timezone', "selectend");
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
?>

