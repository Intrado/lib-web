<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");


if(isset($_SESSION['customeridlist']) && !(count($_SESSION['customeridlist']) > 1)){
	if(count($_SESSION['customeridlist']) == 1)
		$_SESSION['customerid'] = $_SESSION['customeridlist'];
	redirect("start.php");
}

if(isset($_GET['customerid']) && $_GET['customerid']){
	$_SESSION['customerid'] = $_GET['customerid'];
	redirect("start.php");
}

if(isset($_GET['logoutcustomer'])){
	unset($_SESSION['customerid']);
	redirect();
}

?>
<br>You have students in multiple districts/schools
<br>Please choose one of the districts/schools you are associated with:
<br>
<?
foreach($_SESSION['customeridlist'] as $customerid){
	?><a href="choosecustomer.php?customerid=<?=$customerid?>"/><?=$customername[$customerid]?></a><br><?
}
?>