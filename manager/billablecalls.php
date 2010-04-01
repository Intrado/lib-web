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
	
function fmt_typedisplay ($row,$index) {
	$em = $row[$index];
	$att = $row[$index + 1];
	$gen = $row[$index + 2];
	
	return '<span style="color: red;">'.number_format($em).'</span>&nbsp;/&nbsp;<span style="color: orange;">'.number_format($att).'</span>&nbsp;/&nbsp;<span style="color: blue;">'.number_format($gen).'</span>';
}

function fmt_custurl($row, $index){
	global $MANAGERUSER, $CUSTOMERINFO;
	
	if ($row[0] == "Total")
		return '';

	if ($MANAGERUSER->authorized("logincustomer"))
		return "<a href='customerlink.php?id=" . $row[0] ."' target=\"_blank\">" . escapehtml($CUSTOMERINFO[$row[0]]['urlcomponent']) . "</a>";
	else
		return escapehtml(escapehtml($CUSTOMERINFO[$row[0]]['urlcomponent']));
}

function fmt_hasdm ($row,$index) {
	return $row[$index] > 0 ? 'Yes' : '';
}

function fmt_dollars ($row,$index) {
	if(isset($row[5]))
		return "$".number_format(ceil($row[5]*0.0122*100)/100,2);
	return "&nbsp;";
}


include_once("nav.inc.php");
?>

<h2>Billable calls report for all customers</h2>

<form method=post>
<label>Start date:<input type=text name=startdate value="<?=date("Y-m-d",time() - 7*24*60*60)?>"></label>
<label>End date:<input type=text name=enddate value="<?=date("Y-m-d")?>"></label>
<button type=submit>Go</button>
(WARNING: long date ranges can take a while)
</form>
<hr></hr>

<?



if (!isset($_POST['startdate'])) {
	echo "<h2>enter search values</h2>";
} else {
	
	
	loadManagerConnectionData();
	
	$data = array();
	$count = 0;
	foreach ($CUSTOMERINFO as $cid => $cust) {
		
		$custdb = getPooledCustomerConnection($cid,true);
		
		//do stuff here
		$query = "select sum(attempted), count(*) from customercallstats where finishdate between ? and ? + interval 1 day";
		$billable = QuickQueryMultiRow($query,false,$custdb,array($_POST['startdate'],$_POST['enddate']));
		
		foreach ($billable as $row) {
			$data[$cid] = array(
				$cid,   //customerid
				$row[0], //attemtped
				$row[1], //jobs
			);
		}
		
		$query = "select sum(rc.dispatchtype = 'system'), sum(rc.dispatchtype = 'customer'), ceil(sum(if(rc.dispatchtype = 'system',(ceil(duration/6000)*6),0))/60.0) as minutes, 
				sum(rc.dispatchtype = 'system' and jt.systempriority =1) as emergency, sum(rc.dispatchtype = 'system' and jt.systempriority =2) as attendance, sum(rc.dispatchtype = 'system' and jt.systempriority =3) as general,
				(select count(*) from custdm) as hasflex
				from job j
				inner join reportperson rp on (j.id = rp.jobid and rp.type='phone')
				inner join reportcontact rc on (rc.jobid = j.id and rc.type='phone' and rc.personid = rp.personid)
				inner join jobtype jt on (jt.id = j.jobtypeid)
				where j.finishdate between ? and ? + interval 1 day
				and rc.result in ('A','M')";
		$costs = QuickQueryMultiRow($query,false,$custdb,array($_POST['startdate'],$_POST['enddate']));
				
		foreach ($costs as $row) {
			if (!isset($data[$cid]))
				$data[$cid] = array($cid,-1,-1);
			$data[$cid][3] = $row[0]; //system calls
			$data[$cid][4] = $row[1]; //flex calls
			$data[$cid][5] = $row[2]; //duration
			$data[$cid][6] = $row[3]; //emergency
			$data[$cid][7] = $row[4]; //attendance
			$data[$cid][8] = $row[5]; //general
			$data[$cid][9] = $row[6]; //hasdm
			
		}
		echo ".";
		if (++$count % 20 == 0)
			echo "<wbr></wbr>";
		ob_flush();
		flush();
	}
	
	$totalrow = array("Total",0,0,0,0,0,0,0,0,0);
	foreach ($data as $row) {
		foreach ($row as $index => $val) {
			if ($index == 0)
				continue; //skip cid
			if ($val > 0)
				$totalrow[$index] += $val;
		}
	}
	array_unshift($data,$totalrow);	

	$titles = array(
		0 => "#ID",
		"url" => "#Name",
		"9" => "#DM?",
		1 => "#Billable Calls",
		3 => "#Cost calls",
		4 => "#SC calls", //SmartCall calls
		6 => "Cost by Type",
		2 => "#Num Jobs",
		5 => "#minutes",
		"cost" => "#Blended cost (rnd up)"
	);
	$formatters = array(
		"url" => "fmt_custurl",
		1 => "fmt_number",
		2 => "fmt_number",
		3 => "fmt_number",
		4 => "fmt_number",
		5 => "fmt_number",
		6 => "fmt_typedisplay",
		9 => "fmt_hasdm",
		"cost" => "fmt_dollars"
	);

	echo "<h3>Calls from $_POST[startdate] to $_POST[enddate]</h3>";
	echo '<table id="bibllable" class="list sortable">';
	
	showTable($data,$titles,$formatters);
	
	echo '</table>';

}


include_once("navbottom.inc.php");
