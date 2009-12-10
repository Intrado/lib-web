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
			error(_L('There was a problem trying to save your changes'), _L('Please verify that all required field information has been entered properly'));
		} else {
			//submit changes
			$result = portalAssociatePerson(GetFormData($f, $s, "code"), GetFormData($f, $s, "pkey"));
			if($result['result'] == ""){
				if(!isset($_SESSION['pidlist'][$result['customerid']])){
					$_SESSION['pidlist'][$result['customerid']] = array();
				}

				if(!isset($_SESSION['customerid'])){
					
					// set the customerid to the one just associated with this person
					$_SESSION['customerid'] = $result['customerid'];
					// get customer db access
					$accessresult = portalAccessCustomer($_SESSION['customerid']);
					if($accessresult['result'] != ""){
						error(_L("There was an unknown problem connecting to that customer"));
					}
					$_SESSION['custname'] = getSystemSetting("displayname");
				
					//make sure timezone gets updated to the current customer's tz
					$timezone = getSystemSetting("timezone");
					$_SESSION['timezone'] = $timezone;
				}

				$_SESSION['pidlist'][$result['customerid']][] = $result['personid'];
				$_SESSION['currentpid'] = $result['personid'];
				$_SESSION['currentcid'] = $result['customerid'];
				redirect("addcontact2.php");
			} else {
				error(_L("That is not a valid Activation Code and Person ID combination"));
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
$TITLE = _L("Add A Contact");

include_once("nav.inc.php");
NewForm($f);

startWindow(_L('Add'));
?>
<table>
	<tr>
		<td><?=_L("ID#")?></td><td><? NewFormItem($f, $s, "pkey", "text", "20", "255") ?></td>
	</tr>
	<tr>
		<td><?=_L("Activation Code")?>: </td><td><? NewFormItem($f, $s, "code", "text", "10") ?></td>
	</tr>
</table>
<?
endWindow();
buttons(submit($f, $s, _L('Add')), button(_L("Cancel"), NULL, "addcontact3.php"));
EndForm();
include_once("navbottom.inc.php");
?>
