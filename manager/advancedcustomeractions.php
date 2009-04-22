<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../inc/themes.inc.php");

if (!$MANAGERUSER->authorizedAny(array("ffield2gfield","billablecalls")))
	exit("Not Authorized");

if (!isset($_GET['cid']))
	exit("Missing customer id");

$cid = $_GET['cid'] + 0;


include_once("nav.inc.php");
?>

<ul>

<? if ($MANAGERUSER->authorized("ffield2gfield")) { ?>
<li><a href="ffield2gfield.php?cid=<?=$cid?>">F field to G field migration</a></li>
<? } ?>

<? if ($MANAGERUSER->authorized("billablecalls")) { ?>
<li><a href="customerbillablecalls.php?cid=<?=$cid?>">Billable calls</a></li>
<? } ?>


</ul>
<?
include_once("navbottom.inc.php");
?>