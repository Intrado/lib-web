<?

class DrillDownReport extends ReportGenerator{

	function generateQuery(){
		$instance = $this->reportinstance;
		$params = $this->params = $instance->getParameters();
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
		$fieldquery = generateFields("rp");
		$id = $params['personid'];
		$jobid = $params['jobid'];
		$firstnamefield = FieldMap::getFirstNameField();
		$lastnamefield = FieldMap::getLastNameField();
		$this->query = "Select
				j.name as jobname,
				rp.pkey as pkey, 
				rp.$firstnamefield as firstname, 
				rp.$lastnamefield as lastname, 
				coalesce(rc.phone, rc.email) as destination,
				rp.type as jobtype, 
				rc.attemptdata as result,
				rc.numattempts as numattempts,
				rc.sequence as sequence,
				coalesce(rc.result, rp.status) as finalresult 
				$fieldquery
				 from reportperson rp
				left join reportcontact rc on (rp.jobid = rc.jobid and rp.personid = rc.personid and rp.type = rc.type)
				inner join job j on (j.id = rp.jobid)
				where rp.personid = '$id'
				and j.id = '$jobid'";
	}
	
	function runHtml($params = null){
		$fieldlist = $this->reportinstance->getFields();	
		$result = Query($this->query);
		$info = array();
		while($row = DBGetRow($result)){
			$info[] = $row;
		}
		
		$firstname = $info[0][2];
		$lastname = $info[0][3];
		$jobname = $info[0][0];
		$pkey = $info[0][1];
		$field = array();
		for($i = 10; $i<10+count($fieldlist);$i++){
			$field[] = $info[0][$i];
		}
		
		$totalattempts = 0;
		$attempts = array();
		foreach($info as $index => $dest){
			$tmp = explode(",", $dest[6]);
			$count=0;
			foreach($tmp as $attempt){
				if($attempt == "" || $attempt == null){
					$attempt = array(($dest[5] == "email" && $dest[4]=="") ? "No Email Address" : $dest[4], $dest[8], "", $dest[9]);
				} else {
					list($time, $result) = explode(":", $attempt);
					if($count>0){
						$attempt = array("","",$time, $result);
					} else {
						$attempt = array($dest[4], $dest[8], $time, $result);
						$count++;
					}
				}
				$totalattempts++;
				$attempts[] = $attempt;
				
			}
		}
	
		startWindow("Information", "padding: 3px;"); 
		?>
			<table width="100%" cellpadding="3" cellspacing="1" class="list" id="fields">
				<tr class="listheader">
					<td>ID#:</td>
					<td>First Name:</td>
					<td>Last Name:</td>
					<td>Job Name:</td>
<?
					foreach($fieldlist as $index => $fieldname){
						?><td><?=$fieldname?></td><?
					}
?>
				</tr>
				<tr>
					<td><?=$pkey?></td>
					<td><?=$firstname?></td>
					<td><?=$lastname?></td>
					<td><?=$jobname?></td>
<?
					for($i=0; $i<count($fieldlist); $i++){
						?><td><?=$field[$i]?></td><?
					}
?>
				</tr>
			</table>
		<?
		endWindow();
		?>
		<br>
		<?
		 
		startWindow("Call Attempts");
		
		$titles = array("0" => "Destination",
						"1" => "Sequence",
						"2" => "Date/Time",
						"3" => "Result");
		$formatters = array("0" => "fmt_destination",
							"1" => "fmt_sequence",
							"2" => "fmt_ms_timestamp",
							"3" => "job_status");
		?>
		<table width="100%" cellpadding="3" cellspacing="1" class="list" >
		<?
			showTable($attempts, $titles, $formatters);
		?>
		</table>
		<?
		endWindow();
	}
	
	function setReportFile(){
		$this->reportfile = "drilldownreport.jasper";
	}
}

?>