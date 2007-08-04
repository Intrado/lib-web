<?

function generateFields($tablealias){
	$fieldstring = "";
	$first = FieldMap::GetFirstNameField();
	$last = FieldMap::GetLastNameField();
	for($i=1; $i<=20; $i++){

		if($i<10){
			$num = "f0".$i;
		}else{
			$num = "f".$i;
		}
		if($num == $first || $num == $last){
			continue;
		} else{
			$fieldstring .= "," . $tablealias . "." . $num;
		}
	}
	return $fieldstring;
}

function select_metadata($tablename=null, $start=null, $fields){
	global $USER;
	if(isset($_SESSION['saved_report']) && $_SESSION['saved_report']){
		$saved = "true";
	} else {
		$saved = "false";
	}
?>
	<table border="0" cellpadding="2" cellspacing="1" class="list">
		<tr class="listHeader" align="left" valign="bottom">
<?
			foreach($fields as $field){
				?><td><?=$field->name;?></td><?
			}
?>
		</tr>
		<tr>
<?
			$count = 1;
			foreach($fields as $field){
				$fieldnum = $field->fieldnum;
				if($saved == "false"){
					$usersetting = DBFind("UserSetting", "from usersetting where name = '" . DBSafe($field->fieldnum) . "' and userid = '$USER->id'");
					$_SESSION['fields'][$fieldnum] = false;
					if($usersetting!= null){
						if($usersetting->value == "true"){
							$_SESSION['fields'][$fieldnum] = true;
						}
					}
				}
				?><td><div align="center">
				<?
					if(isset($_SESSION['fields'][$fieldnum]) && $_SESSION['fields'][$fieldnum]){
						$result = "<img src=\"img/checkbox-rule.png\" onclick=\"dofieldbox(this,true,'$fieldnum', $saved);";
						$checked = "checked>";
					} else {
						$result = "<img src=\"img/checkbox-clear.png\" onclick=\"dofieldbox(this,false,'$fieldnum', $saved);";
						$checked = ">";
					}
					if($tablename == null && $start ==null){
						$result .= "\">";
					} else {
						$result .= "toggleHiddenField('$fieldnum'); setColVisability($tablename, $start+$count, new getObj('hiddenfield$fieldnum').obj.checked); \">";
					}
					echo $result;
					echo "<input style='display: none' type='checkbox' id='hiddenfield$fieldnum' " . $checked;
				?>
				</div></td><?
				$count++;
			}
?>
		</tr>
	</table>
<?
}

function selectOrderBy($f, $s, $ordercount, $ordering){

?>
	<table>
		<tr>
<?
		for($i=1; $i <= $ordercount; $i++){
			$order = "order$i";
?>
			<td>
<?
				NewFormItem($f, $s, $order, 'selectstart');
				NewFormItem($f, $s, $order, 'selectoption', " -- Not Selected --", "");
				foreach($ordering as $index => $item){
					NewFormItem($f, $s, $order, 'selectoption', $index, $item);
				}	
				NewFormItem($f, $s, $order, 'selectend');
?>
			</td>
<?
			}
?>
		</tr>
	</table>
<?
}

function createPdfParams($filename){
	global $_DBHOST, $_DBNAME, $_DBUSER, $_DBPASS;
	$host = "jdbc:mysql://" . $_DBHOST . "/" . $_DBNAME;
	$user = $_DBUSER;
	$pass = $_DBPASS;
	$params = array("host" => $host,
					"user" => $user,
					"pass" => $pass,
					"filename" => $filename);
	return $params;
}

function getJobList($startdate, $enddate, $jobtypes = "", $surveyonly = "", $deliverymethod = ""){
	global $USER;
	//expects unix time stamps as input
	//returns any jobs between the date range.
	
	//if this user can see systemwide reports, then lock them to the customerid
	//otherwise lock them to jobs that they own
	if (!$USER->authorize('viewsystemreports')) {
		$userJoin = " and j.userid = '$USER->id' ";
	} else {
		$userJoin = "";
	}
	$deliveryquery = " ";
	$surveydeliveryquery = "";
	if("phone" == $deliverymethod){
		$deliveryquery = " and j.phonemessageid is not null ";
		$surveydeliveryquery = " OR sq.hasphone != '0' ";
	} else if("email" == $deliverymethod) {
		$deliveryquery = " and j.emailmessageid is not null ";	
		$surveydeliveryquery = " OR sq.hasemail != '0'";
	}	
	$surveyfilter = "";
	if($surveyonly == "true"){
		$surveyfilter = " and j.questionnaireid is not null ";
	} else if($surveyonly == "false") {
		$surveyfilter = " and j.questionnaireid is null ";
	}
	
	$startdate = date("Y-m-d", $startdate);
	$enddate = date("Y-m-d", $enddate);
	$jobtypequery = "";
	if($jobtypes != ""){
		$jobtypequery = " and j.jobtypeid in ('" . $jobtypes . "') ";
	}
	$joblist = QuickQueryList("select j.id from job j 
							left join surveyquestionnaire sq on (sq.id = j.questionnaireid)
							where ( (j.startdate >= '$startdate' and j.startdate < date_add('$enddate',interval 1 day) )
							or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= '$startdate' and j.startdate <= date_add('$enddate',interval 1 day)
							and j.status in ('active', 'complete', 'cancelled')
							$userJoin 
							$surveyfilter
							$deliveryquery
							$surveydeliveryquery
							$jobtypequery");
	return $joblist;
}

function dateOptions($f, $s, $tablename = "", $infinite = false){
//function to generate table for date options.
//Note that the form data names are pre-set.
?>
	<table  border="0" cellpadding="3" cellspacing="0" width="100%" id="<?=$tablename?>">
		<tr>
			<td><?
				NewFormItem($f, $s, 'relativedate', 'selectstart', null, null, "id='reldate' onchange='if(this.value!=\"xdays\"){hide(\"xdays\")} else { show(\"xdays\");} if(new getObj(\"reldate\").obj.value!=\"daterange\"){ hide(\"date\");} else { show(\"date\")}'");
				if($infinite)
					NewFormItem($f, $s, 'relativedate', 'selectoption', '-- Select A Date Options --', "");
				NewFormItem($f, $s, 'relativedate', 'selectoption', 'Today', 'today');
				NewFormItem($f, $s, 'relativedate', 'selectoption', 'Yesterday', 'yesterday');
				NewFormItem($f, $s, 'relativedate', 'selectoption', 'Last Week Day', 'lastweekday');
				NewFormItem($f, $s, 'relativedate', 'selectoption', 'Week to date', 'weektodate');
				NewFormItem($f, $s, 'relativedate', 'selectoption', 'Month to date', 'monthtodate');
				NewFormItem($f, $s, 'relativedate', 'selectoption', 'Last X Days', 'xdays');
				NewFormItem($f, $s, 'relativedate', 'selectoption', 'Date Range(inclusive)', 'daterange');
				NewFormItem($f, $s, 'relativedate', 'selectend');
				
				?>
			</td>
			<td><div id="xdays">Days: <? NewFormItem($f, $s, 'xdays', 'text', '3'); ?><div></td>
			<td><div id="date">From: <? NewFormItem($f, $s, "startdate", "text", "20") ?> To: <? NewFormItem($f, $s, "enddate", "text", "20")?></div></td>
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
<?

}

?>