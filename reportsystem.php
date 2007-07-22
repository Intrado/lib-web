<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("obj/Job.obj.php");
require_once("obj/JobType.obj.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/form.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/FieldMap.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

	
	$schoolfield = FieldMap::getSchoolField();
	$schoolquery = "";
	if($schoolfield){
		$schoolquery = "rp." . $schoolfield . ", ";
	}
	
	$userlist = array();
	$userquery = "Select login, id from user";
	$userresult = Query($userquery);
	while($row = DBGetRow($userresult)){
		$userlist[$row[1]] = $row[0];
	}
	
	$query = "SELECT $schoolquery 
					rp.userid, j.jobtypeid, count(*)
				from reportperson rp 
				inner join job j on (rp.jobid = j.id)
				where rp.status in ('fail', 'success')
				group by $schoolquery rp.userid, j.jobtypeid 
				order by $schoolquery rp.userid";
	
	$result = Query($query);
	$data = array();
	while($row = DBGetRow($result)){
		$data[] = $row;
	}
	$schools = array();
	$schooltotals = array();
	foreach($data as $row){
	
		if(!isset($schooltotals[$row[0]]))
			$schooltotals[$row[0]] = array(1 => 0,
											2 => 0,
											3 => 0);
		$schooltotals[$row[0]][$row[2]] += $row[3];
		
		if(!isset($schools[$row[0]]))
			$schools[$row[0]] = array();
			
		if(!isset($schools[$row[0]][$userlist[$row[1]]]))
			$schools[$row[0]][$userlist[$row[1]]] = array(1 => 0,
															2 => 0,
															3 => 0);
			
		$schools[$row[0]][$userlist[$row[1]]][$row[2]] = $row[3];
	}
	foreach($schools as $name => $school){
		$schooltotals[$name]["total"] = array_sum($schooltotals[$name]);
		foreach($school as $uname => $user){
			$sum = array_sum($user);
			$schools[$name][$uname]["total"] = (($sum / $schooltotals[$name]["total"])*100) . "%";
		}
	}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:system";
$TITLE = "Usage Statistics";

include_once("nav.inc.php");

startWindow("Total Messages Delivered", "padding: 3px;");
?>
	<table border="0" cellpadding="2" cellspacing="1" class="list">
		<tr class="listHeader" align="left" valign="bottom">
			<td>&nbsp;</td>
			<td>Emergency</td>
			<td>Attendance</td>
			<td>General</td>
			<td>Total</td>
		</tr>
	<?
		foreach($schools as $index => $schools){
			?><tr><td><div style='font-weight:bold; text-decoration: underline'><?=$index?></div></td><?
			foreach($schooltotals[$index] as $sindex => $total){
				if($sindex == "total"){
					?><td>100%</td><?
				} else { 
					?><td><?=$total?></td><?
				}
			}
			?></tr><?
			
			foreach($schools as $uindex => $users){
				?><tr><td><?=$uindex?></td><?
				foreach($users as $utotal){
					?><td><?=$utotal?></td><?
				}
				?></tr><?
			}
			
		}
	?>
	</table>
<?
endWindow();

include_once("navbottom.inc.php");
?>
