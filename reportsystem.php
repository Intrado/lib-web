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
if (!$USER->authorize('viewusagestats')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$groupby = FieldMap::getSchoolField(); //defaults to school f-field
if(!$groupby)
	$groupby = ""; //but if school is not used, default to blank
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
		$groupbyquery = "rp." . $groupby;
	}
	
	$userlist = array();
	$userresult = Query("Select login, id from user");
	while($row = DBGetRow($userresult)){
		$userlist[$row[1]] = $row[0];
	}
	
	$jobtypelist = array();
	$jobtyperesult = Query("select name, id from jobtype");
	while($row = DBGetRow($jobtyperesult)){
		$jobtypelist[$row[1]] = $row[0];
	}
	
	$groupbylist = array();
	$groupbylist = QuickQueryList("select $groupby from reportperson group by $groupby");
	
	$query = "SELECT $groupbyquery as field
					, rp.userid,
					j.jobtypeid,
					count(*)
				from reportperson rp 
				inner join job j on (rp.jobid = j.id)
				where rp.status in ('fail', 'success')
				$joblistquery
				group by $groupbyquery, j.jobtypeid, rp.userid
				order by $groupbyquery, rp.userid";
	
	$result = Query($query);
	$data = array();
	$userlistarray = array();
	foreach($userlist as $userid => $name){
		$jobtypearray = array();
		foreach($jobtypelist as $jobtypeid => $jobtypename){
			$jobtypearray[$jobtypeid] = 0;
		}
		$userlistarray[$userid] = $jobtypearray;
	}
	$groupbyarray = array();
	foreach($groupbylist as $item){
		$groupbyarray[$item] = $userlistarray;
	}
	
	while($row = DBGetRow($result)){
		$groupbyarray[$row[0]][$row[1]][$row[2]] = $row[3];
	}
	$schooltotals = array();
	$systemtotal = 0;
	foreach($groupbyarray as $school => $users){
		$schooltotals[$school] = array();
		foreach($users as $userid => $jobtypes){
			foreach($jobtypes as $jobtypeid => $count){
				if(!isset($schooltotals[$school][$jobtypeid]))
					$schooltotals[$school][$jobtypeid] = 0;
				$schooltotals[$school][$jobtypeid]+=$count;
				$systemtotal +=$count;
			}
		}
		foreach($users as $userid => $jobtypes){
			$schoolsum = array_sum($schooltotals[$school]);
			if($schoolsum == 0)
				$groupbyarray[$school][$userid]["total"] = 0;
			else
				$groupbyarray[$school][$userid]["total"] = (array_sum($groupbyarray[$school][$userid])/$schoolsum) * 100;
		}
		
	}



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:system";
$TITLE = "Usage Statistics";
NewForm($f);
include_once("nav.inc.php");
buttons(submit($f, $s, "Refresh"));
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
			<td colspan="<?=count($jobtypelist)+1?>">System Total</td>
			<td><?=$systemtotal?></td>
		</tr>
		<tr class="listHeader" align="left" valign="bottom">
			<td>&nbsp;</td>
<?
			foreach($jobtypelist as $id => $name){
?>
			<td><?=$name?></td>
<?
			}
?>
			<td>%</td>
		</tr>
<?
		$alt=0;
		foreach($groupbyarray as $index => $groupbyfield){
			echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
?>
			<td><u><?=FieldMap::getName($groupby)?>: <?=$index?><u></td>
<?
			foreach($schooltotals[$index] as $jobtype => $total){
?>
				<td><?=$total?></td>
<?
			}
?>
				<td>100%</td>
			</tr>
<?
			foreach($groupbyfield as $uindex => $user){
				if($user["total"] == 0) continue;
				echo $alt % 2 ? '<tr>' : '<tr class="listAlt">';
?>
					<td>&nbsp;&nbsp;&nbsp;User: <?=$userlist[$uindex]?></td>
<?
				foreach($jobtypelist as $jobtypeid => $jobtypename){
?>
					<td><?=$user[$jobtypeid]?></td>
<?
				}
?>
					<td><?=number_format($user["total"],2)?>%</td>
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
