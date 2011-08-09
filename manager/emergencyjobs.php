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

if (!$MANAGERUSER->authorized("emergencyjobs"))
	exit("Not Authorized");

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

function fmt_play_link($row, $index){
	$url = "";
	if (isset($row[0]) && $row[0] != "" && isset($row[7]) && $row[7] != "")
		$url = "<a onclick='popup(\"customerplaymessage.php?customerid=" . $row[0] . "&jobid=" . $row[7] . "\", 400, 500); return false;' href=\"#\" title='Play Message'><img src='mimg/s-play.png' border=0></a>";
	return $url;
}


include_once("nav.inc.php");
?>

<h2>Jobs for all customers</h2>

<form method=post>
<label>Priority:<select multiple name="types[]">
<option selected value=1 >Emergency</option>
<option value=2 >Attendance</option>
<option value=3 >General</option>
</select></label>
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
		//get list of jobtypes for systempriority
		$jtsql = "";
		if (isset($_POST['types']) && is_array($_POST['types'])) {
			$query = "select id from jobtype where systempriority in (" . DBParamListString(count($_POST['types'])) . ")";
			$jtids = QuickQueryList($query,false,$custdb,$_POST['types']);
			$jtsql = "and j.jobtypeid in (".implode(",",$jtids).")";
		}
		
		$query = "select j.name, j.description, mg.name, u.login, count(*) as calls,
				(select count(*) from custdm) as hasflex, j.id
				from job j
				inner join reportperson rp on (j.id = rp.jobid and rp.type='phone')
				inner join reportcontact rc on (rc.jobid = j.id and rc.type='phone' and rc.personid = rp.personid and rc.result in ('A','M'))
				inner join user u on (u.id = j.userid)
				inner join messagegroup mg on (mg.id = j.messagegroupid)
				where j.finishdate between ? and ? + interval 1 day
				$jtsql
				group by j.id";

		$costs = QuickQueryMultiRow($query,false,$custdb,array($_POST['startdate'],$_POST['enddate']));
		
		if (count($costs) > 0) {
			$customernotes = QuickQuery("select notes from customer where id = ?",false, array($cid));
			foreach ($costs as $row) {
				$d = array();
				$d[0] = $cid;
				$d[1] = $row[0]; //name
				$d[2] = $row[1]; //desc
				$d[3] = $row[2]; //message
				$d[4] = $row[3]; //user
				$d[5] = $row[4]; //emergency calls
				$d[6] = $row[5]; //has SmartCall
				$d[7] = $row[6]; //job id
				$d[8] = $customernotes;
				$data[] = $d;
			}
		}
		
		echo ".";
		if (++$count % 20 == 0)
			echo "<wbr></wbr>";
		ob_flush();
		flush();
	}
	
	$totalrow = array("Total","","","","",0,"","","");
	foreach ($data as $row) {
		$totalrow[5] += $row[5];
	}
	array_unshift($data,$totalrow);	

	$titles = array(
		0 => "#ID",
		"url" => "#Name",
		6 => "#DM?",
		7 => "#Job ID",
		1 => "#Job Name",
		2 => "#Description",
		3 => "#Message Name",
		4 => "#User",
		5 => "#Calls",
		8 => "Customer Notes",
		"play" => "Play"
	);
	$formatters = array(
		"url" => "fmt_custurl",
		5 => "fmt_number",
		6 => "fmt_hasdm",
		"play" => "fmt_play_link"
	);
	
	$types = array();
	if (isset($_POST['types']) && is_array($_POST['types'])) {
		foreach ($_POST['types'] as $type) {
			switch ($type) {
			case "1": $types[] = "Emergency"; break;
			case "2": $types[] = "Attendance"; break;
			case "3": $types[] = "General"; break;
			}
		}
	} else {
		$types = array("All");
	}
	
	echo "<h3>" . implode(",",$types) . " Calls from $_POST[startdate] to $_POST[enddate]</h3>";
	echo '<table id="bibllable" class="list sortable">';
	
	showTable($data,$titles,$formatters);
	
	echo '</table>';

}


include_once("navbottom.inc.php");
