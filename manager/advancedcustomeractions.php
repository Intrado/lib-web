<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../inc/table.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../inc/themes.inc.php");



if (!$MANAGERUSER->authorizedAny(array("ffield2gfield","billablecalls","editcustomer")))
	exit("Not Authorized");

if (!isset($_GET['cid']))
	exit("Missing customer id");

$cid = $_GET['cid'] + 0;

$custurl = QuickQuery("select c.urlcomponent from customer c where c.id = ?", false, array($cid));

$TITLE = "Advanced Customer Actions";
$PAGE = "commsuite:customers";

include_once("nav.inc.php");

startWindow(_L('Advanced Customer Actions for customer: ' . $custurl));
?>

<ul>

<? if ($MANAGERUSER->authorized("ffield2gfield")) { ?>
<li><a href="ffield2gfield.php?cid=<?=$cid?>">F field to G field migration</a></li>
<? } ?>

<? if ($MANAGERUSER->authorized("billablecalls")) { ?>
<li><a href="customerbillablecalls.php?cid=<?=$cid?>">Billable calls</a></li>
<? } ?>

<? if ($MANAGERUSER->authorized("edittemplate")) { ?>
<li><a href="customertemplates.php?cid=<?=$cid?>">Templates</a></li>
<? } ?>

<? if ($MANAGERUSER->authorized("editcustomer")) { ?>
<li><a href="authproviders.php?cid=<?=$cid?>">Authentication Providers</a></li>
<? } ?>

<? if ($MANAGERUSER->authorized("runqueries")) { ?>
<li><a href="querylist.php?cid=<?=$cid?>">Run Queries</a></li>
<? } ?>

</ul>
<?
endWindow();

include_once("navbottom.inc.php");
?>
