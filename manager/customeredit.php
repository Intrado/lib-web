<?
include_once("common.inc.php");
include_once("../obj/Customer.obj.php");
include_once("../inc/form.inc.php");
include_once("../inc/html.inc.php");
include_once("../inc/utils.inc.php");

$f = "customer";
$s = "edit";
$currentid = $_GET['id'] + 0;

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

			if(strlen($maxphones) == 0) {
				$maxphones = "4";
			}
			if(strlen($maxemails) == 0) {
				$maxemails = "2";
			}

			if (QuickQuery("SELECT COUNT(*) FROM customer WHERE inboundnumber=" . DBSafe($inboundnumber) . " AND id != $currentid")) {
				error('Entered 800 Number Already being used', 'Please Enter Another');
			} else if (QuickQuery("SELECT COUNT(*) FROM customer WHERE hostname='" . DBSafe($hostname) ."' AND id != $currentid")) {
				error('URL Path Already exists', 'Please Enter Another');
			} else if (strlen($inboundnumber) > 0 && !ereg("[0-9]{10}",$inboundnumber)) {
				error('Bad 800 Number Format', 'Try Again');
			} else if ($maxphones < 4) {
				error('Max-Phones must be 4 or greater', 'Try Again');
			} else if ($maxemails < 2) {
				error('Max-Emails must be 2 or greater', 'Try Again');
			} else {

				$query="UPDATE customer SET name='" . DBSafe($displayname) . "',
						hostname='" . DBSafe($hostname) . "',
						inboundnumber='" . DBSafe($inboundnumber) . "',
						timezone='" . DBSafe($timezone) . "' WHERE id = $currentid";
				Query($query) or die("ERROR: " . mysql_query() . " SQL:" . $query);

				if(QuickQuery("SELECT COUNT(*) FROM setting WHERE customerid = $currentid AND name = 'maxphones'")) {
					$query="UPDATE `setting` SET value='" . DBSafe($maxphones) . "'
							WHERE customerid = $currentid AND name = 'maxphones'";
					Query($query) or die("ERROR: " . mysql_query() . " SQL:" . $query);

					$query="UPDATE `setting` SET value='" . DBSafe($maxemails) . "'
							WHERE customerid = $currentid AND name = 'maxemails'";
					Query($query) or die("ERROR: " . mysql_query() . " SQL:" . $query);
				}else {
					$query = "INSERT INTO `setting` (`customerid`, `name`, `value`) VALUES
								($currentid, 'maxphones', '" . DBSafe($maxphones) ."'),
								($currentid, 'maxemails', '" . DBSafe($maxemails) . "')";
					QuickUpdate($query) or die( "ERROR:" . mysql_error() . " SQL:" . $query);
				}

				redirect("customers.php");
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
	PutFormData($f,$s,'inboundnumber',$custfields[2],"text",10,10);
	PutFormData($f,$s,'timezone', $custfields[3], "text", 1, 255);

}
?>

<html>
<body>

<?
include_once("nav.inc.php");

NewForm($f);

?>

<table>
<tr><td>Customer display name: </td><td> <? NewFormItem($f, $s, 'name', 'text', 25, 50); ?></td></tr>
<tr><td>URL path name: </td><td><? NewFormItem($f, $s, 'hostname', 'text', 25, 255); ?> (Must be 5 or more characters)</td></tr>
<tr><td>800 inbound number: </td><td><? NewFormItem($f, $s, 'inboundnumber', 'text', 10, 10); ?></td></tr>
<tr><td>Timezone: </td><td>
<? NewFormItem($f, $s, 'timezone', "selectstart");
   foreach($timezones as $timezone)
       NewFormItem($f, $s, 'timezone', "selectoption", $timezone, $timezone);
   NewFormItem($f, $s, 'timezone', "selectend");
?>
</td></tr>
<tr><td>Max Phones: </td><td> <? NewFormItem($f, $s, 'maxphones', 'text', 25, 255) ?> Must be 4 or greater</td></tr>
<tr><td>Max E-mails: </td><td> <? NewFormItem($f, $s, 'maxemails', 'text', 25, 255) ?> Must be 2 or greater</td></tr>
</table>

<?
NewFormItem($f, $s, 'Submit', 'submit');

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