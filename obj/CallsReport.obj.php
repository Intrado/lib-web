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
		$datestartquery=""; 
		$dateendquery=""; 
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
		if(isset($params['date_start'])){
			$datestartquery = strtotime($params['date_start']) ? " and unix_timestamp(j.finishdate) > '" . strtotime($params['date_start']) . "'" : "";
		}
		if(isset($params['date_end'])){
			$dateendquery = strtotime($params['date_end']) ? " and unix_timestamp(j.startdate) < '" . strtotime($params['date_end']) . "'" : "";
		}
		
		if(isset($params['priority'])){
			$priorityquery = $params['priority'] ? " and j.jobtypeid = '" . DBSafe($params['priority']) ."'" : "";
		}
		if(isset($params['reldate'])){
			$reldate = $params['reldate'];
			if($reldate != ""){
				switch($reldate){
					case 'today':
						$targetdate = QuickQuery("select curdate()");
						break;
					case 'xdays':
						$lastxdays = $params['lastxdays'];
						if($lastxdays == "")
							$lastxdays = 1;
						$targetdate = QuickQuery("select date_sub(curdate(),interval $lastxdays day)");
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
						break;
					case 'yesterday':
						$targetdate = QuickQuery("select date_sub(curdate(),interval 1 day)");
						break;
				}
				$reldatequery = "and ( (j.startdate >= unix_timestamp('$targetdate') * 1000 and j.startdate < unix_timestamp(date_add('$targetdate',interval 1 day)) * 1000 )
											or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= '$targetdate' and j.startdate <= date_add('$targetdate',interval 1 day) ";
			}
		}
		$jobids = "";
		if($datestartquery != "" || $dateendquery != ""){
			$jobids = QuickQueryList("select j.id from job j where 1 $datestartquery $dateendquery");
		} else if ($reldatequery != ""){
			$jobids = QuickQueryList("select j.id from job j where 1 $reldatequery");
		}
		if($jobids != "")
			$jobidquery = "and j.id in ('" . implode("','", $jobids) . "')";
			
		if(isset($params['result'])){
			$results = $params['result'];
			$resultquery = " and rc.result in (" . $results . ") ";
		}
		if(isset($params['systempriority'])){
			$systemquery = "and jt.systempriority = '" . DBSafe($params['systempriority']) . "'";
		}
			
		$search = $personquery . $phonequery . $emailquery . $priorityquery . $resultquery . $reldatequery . $jobidquery . $systemquery;
		if($orderquery == ""){
			$orderquery .= " order by rp.personid";
		}
		$this->query = 
				"Select SQL_CALC_FOUND_ROWS
					rp.pkey as pkey, 
					rp." . FieldMap::GetFirstNameField() . " as firstname, 
					rp." . FieldMap::GetLastNameField() . " as lastname,
					rp.personid
					from reportperson rp
					left join reportcontact rc on (rp.jobid = rc.jobid and rp.personid = rc.personid and rp.type = rc.type)
					inner join job j on (rp.jobid= j.id)
					inner join jobtype jt on (j.jobtypeid = jt.id)
					where 1
					$search
					$usersql
					$rulesql
					group by personid
					$orderquery";
	}

	function runHtml($params=null){
		$options = $this->params;
		$results = isset($options['result']) ? $options['result'] : "";
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
		$pagestart = $options['pagestart'];
		$fields = $this->reportinstance->getFields();
		$query = $this->query;
		$query .= " limit $pagestart, 10";
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
					, rp.type, j.id
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
		if(isset($options['priority'])){
			$jobtype = new JobType($options['priority']+0);
			$priority = $jobtype->name;
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
			if(isset($options['priority']) && $priority !="") {
?>
				<tr><td>Priority: <?=$priority?></td></tr>
<?
			}
			if(isset($options['result']) && $resulttypes !=""){
?>
				<tr><td>Result Type: <?=$resulttypes?></td></tr>
<?
			}
?>
			</table>
		<? 
		endWindow(); 
		?>
		<br>
		<?
		
		$titles = array("Person ID",
						"First Name",
						"Last Name",
						"Job Name",
						"Date",
						"Notified");
		foreach($fields as $index => $field){
			$titles[] = $field;
		}
							
		startWindow("Search Results", "padding: 3px;");
			
			showPageMenu($total,$pagestart,10);
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
				$alt = 0;
				foreach($persondata as $row){
					echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
					?>
						<td><?=$row[0]?></td>
						<td><?=$row[1]?></td>
						<td><?=$row[2]?></td>
<?
					for($i=3;$i<count($titles);$i++){
						?><td></td><?
					}
?>
					</tr>
<?
					foreach($personcalls[$row[3]] as $callinfo){
						echo $alt % 2 ? '<tr>' : '<tr class="listAlt">';
						?><td></td><td></td><td></td><?
						$first = false;
?>
							<td><?=fmt_jobdrilldown($row[3], $callinfo[count($callinfo)-1], $callinfo[0])?></td>
							<td><?=fmt_date($callinfo,1)?></td>
							<td><?=fmt_calls_result($callinfo,2)?></td>
<?
							for($i=3;$i<count($fields)+3;$i++){
								?><td><?=$callinfo[$i]?></td><?
							}
?>
						</tr>
<?
					}
				}
?>
				</table>
<?
			showPageMenu($total,$pagestart,10);
		
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
		$this->reportfile = "callsreport.jasper";
	}

}
?>