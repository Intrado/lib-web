<?

class DrillDownReport extends ReportGenerator{

	function generateQuery(){
		$instance = $this->reportinstance;
		$params = $this->params = $instance->getParameters();
		$this->reporttype = $params['reporttype'];
		$fieldquery = generateFields("rp");
		$id = $params['personid'];
		$jobid = $params['jobid'];
		$firstnamefield = FieldMap::getFirstNameField();
		$lastnamefield = FieldMap::getLastNameField();
		$this->query = "Select
				rp.pkey as pkey, 
				rp.$firstnamefield as firstname, 
				rp.$lastnamefield as lastname,
				j.name
				$fieldquery
				 from reportperson rp
				inner join job j on (j.id = rp.jobid)
				where rp.personid = '$id'
				and rp.jobid = '$jobid'
				group by rp.personid";
	}
	
	function runHtml($params = null){
		
		$id = $this->params['personid'];
		$jobid = $this->params['jobid'];
		$fieldlist = $this->reportinstance->getFields();	
		$info = QuickQueryRow($this->query);
		
		$firstname = $info[1];
		$lastname = $info[2];
		$pkey = $info[0];
		$field = array();
		for($i = 3; $i<3+count($fieldlist);$i++){
			$field[] = $info[$i];
		}
		$phonequery = "Select
				rc.phone as destination,
				rc.attemptdata as result,
				rc.result as finalresult 
				from reportcontact rc
				where rc.personid = '$id'
				and rc.jobid = '$jobid'
				and rc.type = 'phone'";
		$emailquery = "Select
				rc.email as destination,
				rc.starttime as result,
				rc.result as finalresult 
				from reportcontact rc
				where rc.personid = '$id'
				and rc.jobid = '$jobid'
				and rc.type = 'email'";
				
		$phoneres = Query($phonequery);
		$phonedata = array();
		while($row = DBGetRow($phoneres)){
			$phonedata[] = $row;
		}
		$emailres = Query($emailquery);
		$emaildata = array();
		while($row = DBGetRow($emailres)){
			$emaildata[] = $row;
		}
		$totalattempts = 0;
		$tempattempts = array();
		$phoneattempts = array();
		$order = array();
		$count=0;
		foreach($phonedata as $dest){
			if($dest[0] == null) continue;
			$tmp = explode(",", $dest[1]);
			
			foreach($tmp as $attempt){
				$time = 0;
				$result = 0;
				if($attempt != "" || $attempt != null){
					list($time, $result) = explode(":", $attempt);
					$attempt = array($dest[0], $time, $result);
					$totalattempts++;
				} else if($dest[2] != null){
					$attempt = array($dest[0], "", $dest[2]);
					$totalattempts++;
				} else {
					continue;
				}
				$tempattempts[$count] = $attempt;
				$order[$count] = $time;
				$count++;
			}
		}
		if(asort($order, SORT_NUMERIC)){
			foreach($order as $count => $time){
				$phoneattempts[] = $tempattempts[$count];
			}
		} else { 
			$phoneattempts = $tempattempts;
		}
		
		foreach($emaildata as $index => $dest){
			if($dest[0] == null)
				unset($emaildata[$index]);
		}
		
		startWindow("Information", "padding: 3px;"); 
		?>
			<table width="100%" cellpadding="3" cellspacing="1">
				<tr><th align="right" class="windowRowHeader bottomBorder">ID#:</th><td  class="bottomBorder"><?=$pkey?></td></tr>
				<tr><th align="right" class="windowRowHeader bottomBorder">First Name:</th><td class="bottomBorder"><?=$firstname?></td></tr>
				<tr><th align="right" class="windowRowHeader bottomBorder">Last Name:</th><td class="bottomBorder"><?=$lastname?></td></tr>
				<tr>
<?
				$i = 0;
				foreach($fieldlist as $index => $fieldname){
					?><tr><th align="right" class="windowRowHeader bottomBorder"><?=$fieldname?></th><td class="bottomBorder"><?=$field[$i]?></td></tr><?
					$i++;
				}
?>
				</tr>
			</table>
		<?
		endWindow();
		?>
		<br>
		<?
		 
		startWindow("Phone Attempts");
		
		$titles = array("0" => "Phone#",
						"1" => "Date/Time",
						"2" => "Result");
		$formatters = array("0" => "fmt_destination",
							"1" => "fmt_ms_timestamp",
							"2" => "job_status");
		?>
		<table width="100%" cellpadding="3" cellspacing="1" class="list" >
		<?
			showTable($phoneattempts, $titles, $formatters);
		?>
		</table>
		<?
		endWindow();
		
		?><br><?
		
		startWindow("Email Attempts");
		
		$titles = array("0" => "Email",
						"1" => "Date/Time");
		$formatters = array("1" => "fmt_ms_timestamp");
		?>
		<table width="100%" cellpadding="3" cellspacing="1" class="list" >
		<?
			showTable($emaildata, $titles, $formatters);
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