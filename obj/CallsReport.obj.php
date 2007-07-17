<?

class CallsReport extends ReportGenerator{

	function generateQuery(){
		$USER = new User($this->userid);
		$params = $this->params = $this->reportinstance->getParameters();
		$this->reporttype = $params['reporttype'];
		$orders = array("order1", "order2", "order3");
		$orderquery = "";
		foreach($orders as $order){
			if(!isset($params[$order]))
				continue;
			$orderby = $params[$order];
			if($orderby == "") continue;
			if($orderquery == ""){
				$orderquery = " order by ";
			} else {
				$orderquery .= ", ";
			}
			$orderquery .= $orderby;
		}
		$rulesql = "";
		if(isset($params['rules']) && $params['rules']){
			$rules = explode("||", $params['rules']);
			foreach($rules as $rule){
				if($rule) {
					$rule = explode(";", $rule);
					$newrule = new Rule();
					$newrule->logical = $rule[0];
					$newrule->op = $rule[1];
					$newrule->fieldnum = $rule[2];
					$newrule->val = $rule[3];
					$rulesql .= " " . $newrule->toSql("rp");
				}
			}
		}
		$usersql = $USER->userSQL("rp");
		$personquery="";
		$phonequery="";
		$emailquery="";
		$priorityquery=""; 
		$reldatequery = "";
		$resultquery="";
		$jobidquery = "";
		$systemquery = "";
		
		if(isset($params['personid'])){
			$personquery = ($params['personid'] != "" || $params['personid'] != null) ? " and rp.pkey like '%" . DBSafe($params['personid']) . "%'" : "";
		}
		if(isset($params['phone'])){
			$phonequery = $params['phone'] ? " and rc.phone like '%" . DBSafe($params['phone']) . "%'" : "";
		}
		if(isset($params['email'])){
			$emailquery = $params['email'] ? " and rc.email like '%" . DBSafe($params['email']) . "%'" : "";
		}

		
		if(isset($params['priority'])){
			$priorityquery = $params['priority'] ? " and j.jobtypeid in ('" . $params['priority'] . "')" : "";
		}
		if(isset($params['reldate'])){
			$reldate = $params['reldate'];
			if($reldate != ""){
				switch($reldate){
					case 'today':
						$targetdate = QuickQuery("select curdate()");
						$reldatequery = "and ( (j.startdate >= '$targetdate' and j.startdate < date_add('$targetdate',interval 1 day) )
											or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= '$targetdate' and j.startdate <= date_add('$targetdate',interval 1 day) ";
						
						break;
					
					case 'week':
						//1 = Sunday, 2 = Monday, ..., 7 = Saturday
						$dow = QuickQuery("select dayofweek(curdate())");
	
						//normally go back 1 day
						$daydiff = 1;
						//if it is sunday, go back 2 days
						if ($dow == 1)
							$daydiff = 2;
						//if it is monday, go back 3 days
						if ($dow == 2)
							$daydiff = 3;
	
						$targetdate = QuickQuery("select date_sub(curdate(),interval $daydiff day)");
						$reldatequery = "and ( (j.startdate >= '$targetdate' and j.startdate < date_add('$targetdate',interval 1 day) )
											or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= '$targetdate' and j.startdate <= date_add('$targetdate',interval 1 day) ";
						
						break;
					case 'yesterday':
						$targetdate = QuickQuery("select date_sub(curdate(),interval 1 day)");
						$reldatequery = "and ( (j.startdate >= '$targetdate' and j.startdate < date_add('$targetdate',interval 1 day) )
											or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= '$targetdate' and j.startdate <= date_add('$targetdate',interval 1 day) ";
						
						break;
					case 'xdays':
						$lastxdays = $params['lastxdays'];
						if($lastxdays == "")
							$lastxdays = 1;
						$today = QuickQuery("select curdate()");
						$targetdate = QuickQuery("select date_sub(curdate(),interval $lastxdays day)");
						$reldatequery = "and ( (j.startdate >= '$targetdate' and j.startdate < date_add('$today',interval 1 day) )
											or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= '$targetdate' and j.startdate <= date_add('$today',interval 1 day) ";
						
						break;
					case 'daterange':

						$datestart = strtotime($params['startdate']);
						$dateend = strtotime($params['enddate']);
						$reldatequery = "and ( (j.startdate >= from_unixtime('$datestart') and j.startdate < date_add(from_unixtime('$dateend'),interval 1 day) )
											or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= from_unixtime('$datestart') and j.startdate <= date_add(from_unixtime('$dateend'),interval 1 day) ";
						break;
					case 'weektodate':
						$today = QuickQuery("select curdate()");
						$targetdate = QuickQuery("select date_sub(curdate(), interval 1 week)");
						$reldatequery = "and ( (j.startdate >= '$targetdate' and j.startdate < date_add('$today',interval 1 day) )
											or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= '$targetdate' and j.startdate <= date_add('$today',interval 1 day) ";
						break;
					case 'monthtodate':
						$today = QuickQuery("select curdate()");
						$targetdate = QuickQuery("select date_sub(curdate(), interval 1 month)");
						$reldatequery = "and ( (j.startdate >= '$targetdate' and j.startdate < date_add('$today',interval 1 day) )
											or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= '$targetdate' and j.startdate <= date_add('$today',interval 1 day) ";
						break;
				}
			}
		}
		/*
		$jobids = "";
		if($datestartquery != "" || $dateendquery != ""){
			$jobids = QuickQueryList("select j.id from job j where 1 $datestartquery $dateendquery");
		} else if ($reldatequery != ""){
			$jobids = QuickQueryList("select j.id from job j where 1 $reldatequery");
		}
		
		if($jobids != ""){
			$jobids = implode("','", $jobids);
			$jobidquery = "and j.id in ('" . implode("','", $jobids) . "')";
		*/
		if(isset($params['result'])){
			$results = $params['result'];
			if($results != ""){
				$resultquery = " and rc.result in ('" . $results . "') ";
			}
		}
		if(isset($params['systempriority'])){
			$systemquery = "and jt.systempriority = '" . DBSafe($params['systempriority']) . "'";
		}
			
		$search = $personquery . $phonequery . $emailquery . $priorityquery . $resultquery . $reldatequery . $systemquery;
		if($orderquery == ""){
			$orderquery .= " order by rp.pkey, date";
		}
		
		$fieldquery = generateFields("rp");
		
		$this->query = 
				"Select SQL_CALC_FOUND_ROWS
					rp.pkey as pkey, 
					rp." . FieldMap::GetFirstNameField() . " as firstname, 
					rp." . FieldMap::GetLastNameField() . " as lastname,
					rp.personid,
					j.name as jobname,
					max(from_unixtime(rc.starttime/1000)) as date,
					rp.status as status,
					j.id,
					sum(rp.type = 'phone') as phonecount,
					sum(rp.type = 'email') as emailcount
					$fieldquery
					from reportperson rp
					left join reportcontact rc on (rp.jobid = rc.jobid and rp.personid = rc.personid and rp.type = rc.type)
					inner join job j on (rp.jobid= j.id)
					inner join jobtype jt on (j.jobtypeid = jt.id)
					where 1
					$search
					$usersql
					$rulesql
					group by rp.personid, j.id
					$orderquery
					";
	}

	function runHtml($params=null){
		$options = $this->params;
		$results = isset($options['result']) ? $options['result'] : "";
		$resulttypes = "";
		if($results){
			$resulttypes = array();
			$restypes = explode(",", $results);
			foreach($restypes as $res){
				$resulttypes[] = job_status(preg_replace("{'}", "",$res));
			}
			$resulttypes = implode(", ", $resulttypes);
		}
		$pagestart = $options['pagestart'];
		$fields = $this->reportinstance->getFields();
		$query = $this->query;
		$query .= " limit $pagestart, 50";
		$result = Query($query);
		$persondata = array();
		while($row = DBGetRow($result)){
			$persondata[] = $row;
		}
		$total = QuickQuery("select found_rows()");


		$priority = "";
		if(isset($options['priority'])){
			$priorities = explode("','", $options['priority']);
			$first = true;
			$displaypriority = "";
			foreach($priorities as $priority){
				$jobtype= new JobType($priority);
				$displaypriority .= ($first ? "" : ", ") . $jobtype->name;
				$first = false;
			}
		}
		$searchrules = array();
		if(isset($options['rules']) && $options['rules']){
			$rules = explode("||", $options['rules']);
			foreach($rules as $rule){
				if($rule) {
					$rule = explode(";", $rule);
					$newrule = new Rule();
					$newrule->logical = $rule[0];
					$newrule->op = $rule[1];
					$newrule->fieldnum = $rule[2];
					$newrule->val = $rule[3];
					$fieldname = QuickQuery("select name from fieldmap where fieldnum = '$newrule->fieldnum'");
					$searchrules[] = $fieldname . " : " . preg_replace("{\|}", ", ", $newrule->val);
				}
			}
		}
	
		startWindow("Search Information", "padding: 3px;"); 
		?>
			<table>
<? 
			if(isset($options['personid']) && $options['personid'] !="") { 
?>
				<tr><td>Person ID: <?=$options['personid']?></td></tr>
<? 
			}
			if(isset($options['phone']) && $options['phone'] !="") {
?>
				<tr><td>Phone: <?=$options['phone']?></td></tr>
<?
			}
			if(isset($options['email']) && $options['email'] !="") {
?>
				<tr><td>Email: <?=$options['email']?></td></tr>
<?
			}
			if(isset($options['date_start']) && $options['date_start'] !=""){
?>
				<tr><td>Date From: <?=$options['date_start']?></td></tr>
<?
			}	
			if(isset($options['date_end']) && $options['date_end'] !=""){
?>
				<tr><td>Date To: <?=$options['date_end']?></td></tr>
<?
			}
			if(isset($options['reldate']) && $options['reldate'] != ""){
				$datedisplay = "Relative Date: ";
				if($options['reldate'] == "xdays"){
					$date = fmt_rel_date($options['reldate'], $options['lastxdays']);
				} else if($options['reldate'] == "daterange"){
					$datedisplay = "Date Range: ";
					$date = fmt_rel_date($options['reldate'], $options['startdate'], $options['enddate']);
				} else {
					$date = fmt_rel_date($options['reldate']);
				}
?>
				<tr><td><?=$datedisplay?><?=$date?></td></tr>
<?
			}
			if(isset($options['priority']) && $displaypriority !="") {
?>
				<tr><td>Priority: <?=$displaypriority?></td></tr>
<?
			}
			if(isset($options['result']) && $resulttypes !=""){
?>
				<tr><td>Result Type: <?=$resulttypes?></td></tr>
<?
			}
			foreach($searchrules as $rule){
				?><tr><td><?=$rule?></td></tr><?
			}
?>
			</table>
		<? 
		endWindow(); 
		?>
		<br>
		<?
		
		$titles = array("ID#",
						"First Name",
						"Last Name",
						"Job Name",
						"Last Attempt",
						"Notified");
		foreach($fields as $index => $field){
			$titles[] = $field;
		}
							
		startWindow("Search Results", "padding: 3px;");
			
			showPageMenu($total,$pagestart,50);
			?>
				<table width="100%" cellpadding="3" cellspacing="1" class="list" id="searchresults">
					<tr class="listHeader">
<?
						foreach($titles as $title){
							?><th align="left" class="nosort"><?=$title?></td><?
						}
?>
					</tr>
<?
				$alt = 1;
				$currid = "";
				foreach($persondata as $row){
					if($row[3] != $currid){
						$alt++;
						$currid = $row[3];
					}
					echo $alt % 2 ? '<tr>' : '<tr class="listAlt">';
					?>
						<td><?=fmt_drilldown($row[3], $row[7])?><?=$row[0]?></td>
						<td><?=$row[1]?></td>
						<td><?=$row[2]?></td>

						<td><?=fmt_type($row[4], $row[8], $row[9])?></td>
						<td><?=fmt_date($row,5)?></td>
						<td><?=fmt_calls_result($row,6)?></td>
<?
					
						for($i=10;$i<count($fields)+10;$i++){
							?><td><?=$row[$i]?></td><?
						}
?>
						</tr>
<?
				}
?>
				</table>
<?
			showPageMenu($total,$pagestart,50);
		
		endWindow();
		?>
		<script langauge="javascript">
			var searchresultstable = new getObj("searchresults").obj;	
		<?
			$count=1;
			foreach($fields as $index => $field){
				?>
				setColVisability(searchresultstable, 5+<?=$count?>, new getObj("hiddenfield".concat('<?=$index?>')).obj.checked);
				<?
				$count++;
			}
		?>
		</script>
		<?
	
	}

	function runCSV(){
		$results = isset($this->params['result']) ? $this->params['result'] : "";
		$resultquery = "";
		$resulttypes = "";
		if($results){
			$resulttypes = array();
			$resultquery = "and rc.result in ( $results )";
			$restypes = explode(",", $results);
			foreach($restypes as $res){
				$resulttypes[] = job_status(preg_replace("{'}", "",$res));
			}
			$resulttypes = implode(", ", $resulttypes);
		}
		$fields = $this->reportinstance->getFields();
		$activefields = $this->reportinstance->getActiveFields();
		$query = $this->query;
		$result = Query($query);
		$persondata = array();
		while($row = DBGetRow($result)){
			$persondata[$row[3]] = $row;
		}
		$total = QuickQuery("select found_rows()");
		
		$fieldquery = generateFields("rp");
		$personcalls = array();
		foreach($persondata as $info){
			$query = "select j.name as jobname,
					from_unixtime(rc.starttime/1000) as date, 
					rp.status as status
					$fieldquery
					,j.id
					from reportperson rp
					left join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
					inner join job j on (rp.jobid= j.id)
					where rp.personid = '$info[3]'
					$resultquery
					group by j.id";
			$result = Query($query);
			$temparray = array();
			while($row = DBGetRow($result)){
				$temparray[] = $row;
			}
			$personcalls[$info[3]] = $temparray;
		}
		$priority = "";
		if(isset($this->params['priority'])){
			$jobtype = new JobType($this->params['priority']+0);
			$priority = $jobtype->name;
		}
		// Begin output to csv
		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-disposition: attachment; filename=report.csv");
		header("Content-type: application/vnd.ms-excel");
	
		session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
	
		$titles = "Person ID, First Name,Last Name,Job Name,Date";
		foreach($fields as $index => $field){
			if(in_array($index, $activefields)){
				$titles .= "," . $field;
			}
		}
		echo $titles;
		echo "\r\n";
		foreach($persondata as $row){
			$line = array();
			$line[] = $row[0];
			$line[] = $row[1];
			$line[] = $row[2];
			foreach($personcalls[$row[3]] as $callinfo){
				$line2 = array();
				$line2[] = $callinfo[0];
				$line2[] = date("m/d/Y H:i", strtotime($callinfo[1]));
				$line2[] = $callinfo[2];
				$i = 4;
				foreach($fields as $index => $field){
					if(in_array($index, $activefields)){
						$line2[] = $callinfo[$i];
					}
					$i++;
				}
				echo '"' . implode('","', $line) . '","' . implode('","', $line2) . '"' . "\r\n";
			}
		}
	}
	
	function setReportFile(){
		$this->reportfile = "CallsReport.jasper";
	}
	
	function getReportSpecificParams($params){
		return $params;
	}
	
	/**static functions**/

	static function getOrdering(){
		global $USER;
		$fields = getFieldMaps();
		$firstname = DBFind("FieldMap", "from fieldmap where options like '%firstname%'");
		$lastname = DBFind("FieldMap", "from fieldmap where options like '%lastname%'");
	
		$ordering = array();
		$ordering["ID#"] = "rp.pkey";
		$ordering[$firstname->name]="rp." . $firstname->fieldnum;
		$ordering[$lastname->name]="rp." . $lastname->fieldnum;
		$ordering["Job Name"]="jobname";
		$ordering["Last Attempt"]="date";
		
		$ordering["Notified"]="status";
		foreach($fields as $field){
			$ordering[$field->name]= "rp." . $field->fieldnum;
		}
		return $ordering;
	}

}
?>