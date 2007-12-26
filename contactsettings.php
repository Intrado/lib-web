<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportSubscription.obj.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");
require_once("inc/form.inc.php");

if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE= "admin:contactsettings";
$TITLE= "Contact Settings";

include("nav.inc.php");

startWindow("Options", 'padding: 3px;');
?>
	<table border="1" width="100%" cellpadding="3" cellspacing="1" class="list" >
		<tr class="listHeader">
			<th align="left" class="nosort">Job Types</th>
			<th align="left" class="nosort">Destination Labels</th>
<?
			if(getSystemSetting('_hasportal', false) && $USER->authorize('portalaccess')){
?>
			<th align="left" class="nosort">Contact Manager Administration</th>
<?
			}
?>
		</tr>
		<tr align="left" valign="bottom">
			<td>
				<table>
					<tr><td><a href='jobtypemanagement.php'>Edit Job Types</a></td></tr>
					<tr><td><a href='jobtypeaddition.php'>Create New Job Type</a></td></tr>
<? if(getSystemSetting('_hassms', false)){ ?>
					<tr><td>&nbsp;</td></tr>
<? } ?>
				</table>
			</td>
			<td>
				<table>
					<tr><td><a href='destinationlabel.php?type=phone'>Edit Phone Labels</a></td></tr>
					<tr><td><a href='destinationlabel.php?type=email'>Edit Email Labels</a></td></tr>
<? if(getSystemSetting('_hassms', false)){ ?>
					<tr><td><a href='destinationlabel.php?type=sms'>Edit SMS Labels</a></td></tr>
<? } ?>
				</table>
			</td>
<?
			if(getSystemSetting('_hasportal', false) && $USER->authorize('portalaccess')){
?>
			<td>
				<table>
					<tr><td><a href='activationcodemanager.php?clear=1'>Manage Activation Codes</a></td></tr>
					<tr><td>&nbsp;</td></tr>
					<tr><td>&nbsp;</td></tr>
				</table>
			</td>
<?
			}
?>
		</tr>
	</table>
<?
endWindow();

include("navbottom.inc.php");
?>