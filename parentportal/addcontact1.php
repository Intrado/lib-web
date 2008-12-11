<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");


$f="addstudent";
$s="main";
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

		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			//submit changes

			// by phone
			if (GetFormData($f, $s, "radioselect") == "byphone") {
				$pkey = GetFormData($f, $s, "pkey");
				$_SESSION['phoneactivationpkeylist'] = array();
				$_SESSION['phoneactivationpkeylist'][] = $pkey;
				redirect("phoneactivation1.php");
			} else {
				// by code
				$code = GetFormData($f, $s, "code");
				$pkey = GetFormData($f, $s, "pkey");
				$result = portalAssociatePerson($code, $pkey);
				if($result['result'] == ""){
					if(!isset($_SESSION['pidlist'][$result['customerid']])){
						$_SESSION['pidlist'][$result['customerid']] = array();
					}

					if(!isset($_SESSION['customerid'])){
						$associationresult = portalGetCustomerAssociations();
						if($associationresult['result'] == ""){
							$customerlist = $associationresult['custmap'];
							$customeridlist = array_keys($customerlist);
						} else {
							$customeridlist = array();
						}
						$_SESSION['customerid'] = $result['customerid'];

						$_SESSION['custname'] = $customerlist[$customeridlist[0]];
						$accessresult = portalAccessCustomer($customeridlist[0]);
						if($accessresult['result'] != ""){
							error("There was an unknown problem connecting to that customer");
						}
						//make sure timezone gets updated to the current customer's tz
						$timezone = getSystemSetting("timezone");
						$_SESSION['timezone'] = $timezone;
					}

					$_SESSION['pidlist'][$result['customerid']][] = $result['personid'];
					$_SESSION['currentpid'] = $result['personid'];
					$_SESSION['currentcid'] = $result['customerid'];
					redirect("addcontact2.php");
				} else {
					error("That is not a valid Contact Identification Number and Activation Code combination");
				}
			} // end radio selection bycode
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);

	PutFormData($f, $s, "radioselect", "bycode");

	PutFormData($f, $s, "pkey", "", "text", 1, 255, true);
	PutFormData($f, $s, "code", "", "text");
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "contacts:contactpreferences";
$TITLE = "Contact Activation";

include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, $s, 'Next'), button("Cancel", NULL, "addcontact3.php"));


startWindow('Activation Method');
?>
<table>
	<tr>
		<td>Contact Identification Number:</td><td><? NewFormItem($f, $s, "pkey", "text", "20", "255") ?></td>
	</tr>
<?	if ($INBOUND_ACTIVATION) { ?>
	<tr>
		<td>
			<? NewFormItem($f, $s, "radioselect", "radio", null, "bycode", ""); ?> I have an Activation Code:
		</td>

		<td><? NewFormItem($f, $s, "code", "text", "10") ?></td>

	</tr>
	<tr>
		<td>
			<? NewFormItem($f, $s, "radioselect", "radio", null, "byphone", ""); ?> I need an Activation Code by phone.
		</td>
	</tr>
<?	} else { ?>
	<tr>
		<td>Activation Code:</td><td><? NewFormItem($f, $s, "code", "text", "10") ?></td>
	</tr>
<?	} ?>
</table>
<?
endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");
?>