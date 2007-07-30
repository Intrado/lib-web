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
require_once("inc/reportutils.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("inc/date.inc.php");
require_once("inc/reportgeneratorutils.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$groupby = FieldMap::getSchoolField(); //defaults to school f-field
$fields = FieldMap::getOptionalAuthorizedFieldMaps();
$type = "phone";
$reldate = "monthtodate";
$f = "system";
$s = "report";
$reload = 0;

if(CheckFormSubmit($f,$s))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		print '<div class="warning">Form was edited in another window, reloading data.</div>';
	}
	else
	{
		MergeSectionFormData($f, $s);
		//do check
		$startdate = GetFormData($f, $s, "startdate");
		$enddate = GetFormData($f, $s, "enddate");
		
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if((GetFormData($f, $s, "relativedate") == "daterange") && !strtotime($startdate)){
			error('Beginning Date is not in a valid format.  February 1, 2007 would be 02/01/07');
		} else if((GetFormData($f, $s, "relativedate") == "daterange") && !strtotime($enddate)){
			error('Ending Date is not in a valid format.  February 1, 2007 would be 02/01/07');
		} else {
			$groupby = GetFormData($f, $s, "groupby");
			$reldate = GetFormData($f, $s, "relativedate");
			$type = GetFormData($f, $s, "type");
		}
	}
} else {
	$reload=1;
}

if($reload){
	ClearFormData($f);
	PutFormData($f, $s, "phone", "1", "bool", 0, 1);
	PutFormData($f, $s, "email", "1", "bool", 0, 1);
	PutFormData($f, $s, "groupby", FieldMap::getSchoolField());
	
	PutFormData($f, $s, "relativedate", $reldate);
	PutFormData($f, $s, 'xdays', isset($lastxdays) ? $lastxdays : "0", "number");
	PutFormData($f, $s, "startdate", isset($startdate) ? $startdate : "", "text");
	PutFormData($f, $s, "enddate", isset($enddate) ? $enddate : "", "text");
	PutFormData($f, $s, "type", isset($type) ? $type : "");
}




////////////////////////////////////////////////////////////////////////////////
// Data Calculation
////////////////////////////////////////////////////////////////////////////////
	$joblistquery = "";
	
	if($reldate == "xdays"){
		list($startdate, $enddate) = getStartEndDate($reldate, array("lastxdays" =>  GetFormData($f, $s, "xdays")));
	} else if(GetFormData($f, $s, "relativedate") != "daterange"){
		list($startdate, $enddate) = getStartEndDate($reldate);
	} else {
		$startdate = strtotime($startdate);
		$enddate = strtotime($enddate);
	}

	$joblist = getJobList($startdate, $enddate, "", "", $type);
	$joblistquery = " and j.id in ('" . implode("','", $joblist) . "') ";

	$groupbyquery = "";
	if($groupby){
		$groupbyquery = "rp." . $groupby . ", ";
	}
	
	$userlist = array();
	$userquery = "Select login, id from user";
	$userresult = Query($userquery);
	while($row = DBGetRow($userresult)){
		$userlist[$row[1]] = $row[0];
	}
	
	$query = "SELECT $groupbyquery
					rp.userid, 
					sum(jt.systempriority = '1'),
					sum(jt.systempriority = '2'),
					sum(jt.systempriority = '3')
				from reportperson rp 
				inner join job j on (rp.jobid = j.id)
				inner join jobtype jt on (jt.id = j.jobtypeid)
				where rp.status in ('fail', 'success')
				$joblistquery
				group by $groupbyquery rp.userid
				order by $groupbyquery rp.userid";
	
	$result = Query($query);
	$data = array();
	while($row = DBGetRow($result)){
		$data[] = $row;
	}
	$schools = array();
	$schooltotals = array("emergency" => array(),
						"attendance" => array(),
						"general" => array(),
						"totalpercent" => array());
	$total = 0;
	$userlist=array();
	foreach($data as $row){
		if(!isset($userlist[$row[1]])){
			$user = new User($row[1]);
			$userlist[$row[1]] = $user->login;
		}
		$row[1] = $userlist[$row[1]];
		$schools[$row[0]][] = $row;
		$schooltotals["emergency"][$row[0]] = (isset($schooltotals["emergency"][$row[0]]) ? $schooltotals["emergency"][$row[0]] : 0) + $row[2];
		$schooltotals["attendance"][$row[0]] = (isset($schooltotals["attendance"][$row[0]]) ? $schooltotals["attendance"][$row[0]] : 0) + $row[3];
		$schooltotals["general"][$row[0]] = (isset($schooltotals["general"][$row[0]]) ? $schooltotals["general"][$row[0]] : 0) + $row[4];
		$schooltotals["totalpercent"][$row[0]] = "100%";
	}
	foreach($schools as $index => $users){
		$schooltotal = $schooltotals["emergency"][$index] + $schooltotals["attendance"][$index] + $schooltotals["general"][$index];
		foreach($users as $uindex => $row){
			$sum = $row[2] + $row[3] + $row[4];
			$row[] = ($sum/$schooltotal) * 100;
			$users[$uindex] = $row;
			$schools[$index] = $users;
		}
	}



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:system";
$TITLE = "Usage Statistics";
NewForm($f);
include_once("nav.inc.php");
buttons(submit($f, $s, "refresh", "refresh"));
startWindow("Display Options", "padding: 3px;");
?>
	<table border="0" cellpadding="2" cellspacing="1" width="100%">
		<tr>
			<th align="right" class="windowRowHeader bottomBorder">Delivery Method</th>
			<td class="bottomBorder">
				<?
					NewFormItem($f, $s, "type", "selectstart");
					NewFormItem($f, $s, "type", "selectoption", "Phone", "phone");
					NewFormItem($f, $s, "type", "selectoption", "Email", "email");
					NewFormItem($f, $s, "type", "selectend");
				?>
			</td>
		</tr>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder">Date</th>
			<td class="bottomBorder">
				<table  border="0" cellpadding="3" cellspacing="0">
					<tr>
						<td><?
							NewFormItem($f, $s, 'relativedate', 'selectstart', null, null, "id='reldate' onchange='if(this.value!=\"xdays\"){hide(\"xdays\")} else { show(\"xdays\");} if(new getObj(\"reldate\").obj.value!=\"daterange\"){ hide(\"date\");} else { show(\"date\")}'");
							NewFormItem($f, $s, 'relativedate', 'selectoption', 'Today', 'today');
							NewFormItem($f, $s, 'relativedate', 'selectoption', 'Yesterday', 'yesterday');
							NewFormItem($f, $s, 'relativedate', 'selectoption', 'Last Week Day', 'weekday');
							NewFormItem($f, $s, 'relativedate', 'selectoption', 'Week to date', 'weektodate');
							NewFormItem($f, $s, 'relativedate', 'selectoption', 'Month to date', 'monthtodate');
							NewFormItem($f, $s, 'relativedate', 'selectoption', 'Last X Days', 'xdays');
							NewFormItem($f, $s, 'relativedate', 'selectoption', 'Date Range(inclusive)', 'daterange');
							NewFormItem($f, $s, 'relativedate', 'selectend');
							
							?>
						</td>
						<td><? NewFormItem($f, $s, 'xdays', 'text', '3', null, "id='xdays'"); ?></td>
						<td><div id="date"><? NewFormItem($f, $s, "startdate", "text", "20") ?> To: <? NewFormItem($f, $s, "enddate", "text", "20")?></div></td>
					</tr>
					<script>
						if(new getObj("reldate").obj.value!="xdays"){
							hide("xdays");
						}
						if(new getObj("reldate").obj.value!="daterange"){
							hide("date");
						
						}
					</script>
				</table>
			</td>
		</tr>

		<tr valign="top">
			<th align="right" class="windowRowHeader">Group By:</th>
			<td>
				<? 
					NewFormItem($f, $s, "groupby", "selectstart");
					foreach($fields as $field){
						NewFormItem($f, $s, "groupby", "selectoption", $field->name, $field->fieldnum);
					}
					NewFormItem($f, $s, "groupby", "selectend");
				?>
			</td>
		</tr>
	</table>
<?
endWindow();

?><br><?
startWindow("Total Messages Delivered", "padding: 3px;");
?>
	<table border="0" cellpadding="2" cellspacing="1" class="list">
		<tr class="listHeader" >
			<td colspan="4">System Total</td>
			<td><?=$total?></td>
		</tr>
		<tr class="listHeader" align="left" valign="bottom">
			<td>&nbsp;</td>
			<td>Emergency</td>
			<td>Attendance</td>
			<td>General</td>
			<td>%</td>
		</tr>
<?
		$alt=0;
		foreach($schools as $index => $schools){
		
			echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
?>

				<td><div style='font-weight:bold;'><?=FieldMap::getName($groupby)?>:&nbsp;<?=$index?></div></td>
				<td><?=$schooltotals["emergency"][$index]?></td>
				<td><?=$schooltotals["attendance"][$index]?></td>
				<td><?=$schooltotals["general"][$index]?></td>
				<td><?=$schooltotals["totalpercent"][$index]?></td>
			</tr>
<?
			foreach($schools as $users){
				echo $alt % 2 ? '<tr>' : '<tr class="listAlt">';
?>
					<td>&nbsp;&nbsp;&nbsp;User:&nbsp;<?=$users[1]?></td>
					<td><?=$users[2]?></td>
					<td><?=$users[3]?></td>
					<td><?=$users[4]?></td>
					<td><?=number_format($users[5], 2)?>%</td>
				</tr>
<?
			}
		}

?>
	</table>
<?
endWindow();
buttons();
endForm();
include_once("navbottom.inc.php");
?>
