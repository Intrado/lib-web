<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/form.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/reportutils.inc.php");
require_once("inc/list.inc.php");

require_once("obj/Form.obj.php");
require_once("obj/Person.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Email.obj.php");
require_once("obj/Sms.obj.php");
require_once("obj/UserSetting.obj.php");
require_once("obj/RenderedList.obj.php");
require_once("obj/RenderedListCM.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (!getSystemSetting("_hasportal", false) || !$USER->authorize('portalaccess') || !$USER->authorize('generatebulktokens')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Rendered List Processing
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['generate'])) {
	// basic rendered list initialization
	$renderedlist = new RenderedListCM();
	$extrawheresql = "";
	if (isset($_SESSION['hideactivecodes']) && $_SESSION['hideactivecodes']) {
		$extrawheresql .= " and not exists (select * from portalpersontoken ppt where ppt.personid = p.id and ppt.expirationdate >= curdate()) ";
	}
	if (isset($_SESSION['hideassociated']) && $_SESSION['hideassociated']) {
		$extrawheresql .= " and not exists (select * from portalperson pp2 where pp2.personid = p.id) ";
	}
	$renderedlist->setExtraWhereSql($extrawheresql);

	$disablerenderedlistajax = true;
	$buttons = array();
	include_once("contactsearchformdata.inc.php");

	$renderedlist->pagelimit = 1000;
	$pageoffset = 0;
	$renderedlist->setPageOffset($pageoffset);
	$personids = QuickQueryList($renderedlist->getPersonSql(true));
	$failedCount = 0;
	while (count($personids) > 0) {
		// Try to generate this batch of tokens up to three times
		$tries = 3;
		while ($tries-- > 0 && generatePersonTokens($personids) === false) {
			error_log("An error occurred trying to generate a batch of activation tokens. Will try $tries more times.");
			sleep(.2);
		}

		// a batch failed, capture the failed number of token generations
		if ($tries < 0)
			$failedCount += count($personids);

		if (isset($_SESSION['hideactivecodes']) && $_SESSION['hideactivecodes'])
			$pageoffset = 0; // resultset changes as new personportaltoken get generated, always fetch first page of updated results
		else
		$pageoffset += $renderedlist->pagelimit;

		$renderedlist->setPageOffset($pageoffset);
		$personids = QuickQueryList($renderedlist->getPersonSql(true));
	}
	if ($failedCount > 0)
		notice(_L("An unexpected error occurred. Generation of %s codes has failed.", $failedCount));
	else
		notice(_L("All activation codes have been generated."));

	redirect("activationcodemanager.php");
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "system:contacts";
$TITLE = _L("Activation Code Manager");

include_once("nav.inc.php");

startWindow(_L("Bulk Code Generation"));
?>
	<div>
		<?= _L('Your request is being processed. Please wait, and you will be redirected once it completes.')?>&nbsp;<img src="img/ajax-loader.gif" />
	</div>
<?
endWindow();

include_once("navbottom.inc.php");
?>
<script type="text/javascript">
	(function() {
		window.location='?generate';
	})();
</script>