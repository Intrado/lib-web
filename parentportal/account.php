<?
require_once("common.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Phone.obj.php");

$error_failedupdate = _L("There was an error updating your information");

$f="portaluser";
$s="main";
$reloadform = 0;
$error = 0;
$notifyemailCheckbox = 0;
$notifysmsCheckbox = 0;

instrumentation_add_custom_parameter("action", "error");
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
		$notifyemailCheckbox = TrimFormData($f, $s, "notifyemailCheckbox");
		$notifyemail = TrimFormData($f, $s, "email");
		$notifysmsCheckbox = TrimFormData($f, $s, "notifysmsCheckbox");
		$notifysms = TrimFormData($f, $s, "sms");
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if ($notifysmsCheckbox && $phoneerror = Phone::validate($notifysms)) {
			error($phoneerror);
		} else if ($notifyemailCheckbox && !validEmail($notifyemail)) {
			error(_L("Email is not a valid format"));
		} else {
		
			//submit changes
			
			if (!$notifyemailCheckbox)
				$notifyemail = "";
			if (!$notifysmsCheckbox)
				$notifysms = "";

			$_SESSION['portaluser']['portaluser.preferences']['_locale'] = getFormData($f, $s, "_locale");
			$_SESSION['portaluser']['portaluser.notifyemail'] = $notifyemail;
			$_SESSION['portaluser']['portaluser.notifysms'] = $notifysms;

			$preferences = $_SESSION['portaluser']['portaluser.preferences'];
			portalUpdateUserPreferences($notifyemail, $notifysms, $preferences);
			instrumentation_add_custom_parameter("action", "save");

			if (!$error) {
				redirect("start.php");
			}
		}
	}
} else {
	instrumentation_add_custom_parameter("action", "load");
	$reloadform = 1;
}

if( $reloadform )
{
	ClearFormData($f);
	PutFormData($f, $s, "_locale", $_SESSION['portaluser']['portaluser.preferences']['_locale'], "text", "nomin", "nomax");
	
	$notifyemailCheckbox = ($_SESSION['portaluser']['portaluser.notifyemail'] == "") ? 0 : 1;
	PutFormData($f, $s, "notifyemailCheckbox", $notifyemailCheckbox, "bool", 0, 1);
	PutFormData($f, $s, "email", $_SESSION['portaluser']['portaluser.notifyemail'], "email", "2", "100");
	
	$notifysmsCheckbox = ($_SESSION['portaluser']['portaluser.notifysms'] == "") ? 0 : 1;
	PutFormData($f, $s, "notifysmsCheckbox", $notifysmsCheckbox, "bool", 0, 1);
	PutFormData($f, $s, "sms", Phone::format($_SESSION['portaluser']['portaluser.notifysms']), "phone", "2", "20"); // 20 is the max to accomodate formatting chars
}

$PAGE = "account:account";
$TITLE = _L("Account Information") . ": " . escapehtml($_SESSION['portaluser']['portaluser.firstname']) . " " . escapehtml($_SESSION['portaluser']['portaluser.lastname']);
include_once("nav.inc.php");
NewForm($f);

startWindow(_L('User Information'));
?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr>
			<th valign="top" width="70" class="windowRowHeader bottomBorder" align="right" valign="top" style="padding-top: 6px;"><?=_L("Account Info")?>:</th>
			<td class="bottomBorder">
				<table border="0" cellpadding="3" cellspacing="0">
					<tr>
						<td align="right"><?=_L("Username")?>:</td>
						<td><?= escapehtml($_SESSION['portaluser']['portaluser.username']) ?></td>
					</tr>
					<tr>
						<td align="right"><?=_L("First Name")?>:</td>
						<td><?= escapehtml($_SESSION['portaluser']['portaluser.firstname']) ?></td>
					</tr>
					<tr>
						<td align="right"><?=_L("Last Name")?>:</td>
						<td><?= escapehtml($_SESSION['portaluser']['portaluser.lastname']) ?></td>
					</tr>
				</table>
			</td>
		</tr>
		<tr>
			<th valign="top" width="70" class="windowRowHeader bottomBorder" align="right" valign="top" style="padding-top: 6px;"><?=_L("Preferences")?>:</th>
			<td  class="bottomBorder">
				<table border="0" cellpadding="1" cellspacing="0">
					<tr>
						<td width="30%"><?=_L("Change Interface Language")?>:</td>
						<td>
							<?
								NewFormItem($f, $s, '_locale', 'selectstart', null, null, "id='locale'");
								foreach($LOCALES as $loc => $lang){
									NewFormItem($f, $s, '_locale', 'selectoption', $lang, $loc);
								}
								NewFormItem($f, $s, '_locale', 'selectend');
							?>
						</td>
					</tr>
					
					<tr>
						<td colspan="2"><? NewFormItem($f,$s, 'notifyemailCheckbox', 'checkbox', null, "", "id=\"emailcheck\" onclick=\"document.getElementById('emailbox').disabled=!this.checked\""); ?>&nbsp;<?=_L("Email me when I have a new phone message.")?></td>
					</tr>
					<tr>
						<td align="right"><?=_L("Email")?>:</td>
						<td><? NewFormItem($f,$s, 'email', 'text', 40, 100, "id=\"emailbox\" ". ($notifyemailCheckbox ? "" : "disabled=\"true\"")); ?></td>
					</tr>

					<tr>
						<td colspan="2"><? NewFormItem($f,$s, 'notifysmsCheckbox', 'checkbox', null, "", "id=\"smscheck\" onclick=\"document.getElementById('smsbox').disabled=!this.checked\""); ?>&nbsp;<?=_L("Text me when I have a new phone message.")?></td>
					</tr>
					<tr>
						<td align="right"><?=_L("Mobile Phone for Text Message")?>:</td>
						<td><? NewFormItem($f,$s, 'sms', 'text', 40, 20, "id=\"smsbox\" ". ($notifysmsCheckbox ? "" : "disabled=\"true\"")); ?></td>
					</tr>

				</table>
			<td>
		</tr>
	</table>

<?
buttons(submit($f, $s, _L('Save')), icon_button(_L("Manage Account"),"email_edit", NULL, $SETTINGS['portalauth']['accountUrl']), icon_button(_L("Cancel"),"cross", NULL, "start.php"));

endWindow();
include_once("navbottom.inc.php");
?>