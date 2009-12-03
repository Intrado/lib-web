<?
require_once("common.inc.php");
require_once("../inc/form.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../inc/themes.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/formatters.inc.php");

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

if (!$MANAGERUSER->authorized("billablecalls"))
	exit("Not Authorized");

if (!isset($_GET['cid']))
	exit("Missing customer id");

$cid = $_GET['cid'] + 0;


$custdb = getSingleCustomerConnection($cid,true);

function fmt_dollars ($row,$index) {
	if(isset($row[5]))
		return "$".number_format(ceil($row[5]*0.0122*100)/100,2);
	return "&nbsp;";
}


include_once("nav.inc.php");
?>

<h2>Billable calls report for <?=$cid?></h2>

<form method=post>
<label>Start date:<input type=text name=startdate value="<?=date("Y-m-d",time() - 30*24*60*60)?>"></label>
<label>End date:<input type=text name=enddate value="<?=date("Y-m-d")?>"></label>
<button type=submit>Go</button>
</form>
<hr></hr>

<?



if (!isset($_POST['startdate'])) {
	echo "<h2>enter search values</h2>";
} else {
	
	$query = "select sum(attempted), date(finishdate) as day, count(*) from customercallstats where finishdate between ? and ? + interval 1 day group by day";
	$billable = QuickQueryMultiRow($query,false,$custdb,array($_POST['startdate'],$_POST['enddate']));
	
	$query = "select date(finishdate) as day,sum(rc.dispatchtype = 'system'), sum(rc.dispatchtype = 'customer'), ceil(sum(if(rc.dispatchtype = 'system',(ceil(duration/6000)*6),0))/60.0) as minutes from job j
			inner join reportperson rp on (j.id = rp.jobid and rp.type='phone')
			inner join reportcontact rc on (rc.jobid = j.id and rc.type='phone' and rc.personid = rp.personid)
			where j.finishdate between ? and ? + interval 1 day
			and rc.result in ('A','M')
			group by day";
	$costs = QuickQueryMultiRow($query,false,$custdb,array($_POST['startdate'],$_POST['enddate']));
	
	$data = array();
	foreach ($billable as $row) {
		$data[$row[1]] = array(
			$row[1], //day
			$row[0], //attemtped
			$row[2], //jobs
		);
	}
	
	foreach ($costs as $row) {
		if (!isset($data[$row[0]]))
			$data[$row[0]] = array($row[0],-1,-1);
		$data[$row[0]][3] = $row[1]; //system calls
		$data[$row[0]][4] = $row[2]; //flex calls
		$data[$row[0]][5] = $row[3]; //duration
	}
	
	$totalrow = array("Total",0,0,0,0,0,0,0);
	foreach ($data as $row) {
		foreach ($row as $index => $val) {
			if ($index == 0)
				continue; //skip day
			if ($val > 0)
				$totalrow[$index] += $val;
		}
	}
	array_unshift($data,$totalrow);
	

	$titles = array(
		0 => "#Date",
		1 => "#Billable Calls",
		3 => "#Cost calls",
		4 => "#Flex calls",
		2 => "#Num Jobs",
		5 => "#Cost Minutes",
		"cost" => "#Blended cost (rnd up)"
	);
	$formatters = array(
		1 => "fmt_number",
		2 => "fmt_number",
		3 => "fmt_number",
		4 => "fmt_number",
		5 => "fmt_number",
		"cost" => "fmt_dollars"
	);

	echo "<h3>Calls from $_POST[startdate] to $_POST[enddate]</h3>";
	echo '<table id="bibllable" class="list sortable">';
	
	showTable($data,$titles,$formatters);
	
	echo '</table>';

}


include_once("navbottom.inc.php");
