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
			$result = portalAssociatePerson(GetFormData($f, $s, "code"), GetFormData($f, $s, "pkey"));
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
				error("That is not a valid Activation Code and Person ID combination");
			}
		}
	}
} else {
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);
	PutFormData($f, $s, "pkey", "", "text");
	PutFormData($f, $s, "code", "", "text");

}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "contacts:contactpreferences";
$TITLE = "Add A Contact";

include_once("nav.inc.php");
NewForm($f);

startWindow('Add');
?>
<table>
	<tr>
		<td>ID#</td><td><? NewFormItem($f, $s, "pkey", "text", "20", "255") ?></td>
	</tr>
	<tr>
		<td>Activation Code: </td><td><? NewFormItem($f, $s, "code", "text", "10") ?></td>
	</tr>
</table>
<?
endWindow();
buttons(submit($f, $s, 'Add'), button("Cancel", NULL, "addcontact3.php"));
EndForm();
include_once("navbottom.inc.php");
?>