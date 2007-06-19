<?

//abstract superclass for specific reports 
class ReportGenerator {
	
	var $reportinstance;
	var $format;
	var $query="";
	var $params;
	var $reporttype;
	
	function generate() {
		
		global $USER;

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
		
		switch($this->reporttype){
			case 'surveyreport':
				$jobid = $params['jobid'];
				if($jobid != ""){
					$this->query = "select 
								sr.questionnumber as questionnumber, 
								sr.answer as answer, 
								sr.tally as tally, 
								sq.reportlabel as questionname,
								coalesce(mp.txt, sq.webmessage) as questiontext,
								sr.jobid as jobid
								from surveyresponse sr
								inner join job j on (sr.jobid = j.id)
								left join surveyquestion sq on (sr.questionnumber = sq.questionnumber and sq.questionnaireid = j.questionnaireid) 
								left join messagepart mp on (mp.messageid = sq.phonemessageid)
								where sr.jobid= '$jobid'
								order by questionnumber, answer";
				} else {
					//errorlog("Report generator called with missing param: jobid for survey report");
					return false;
				}
				break;
			case 'jobreport':
				$jobid = $params['jobid'];
				if($jobid != ""){
					$usersql = $USER->userSQL("rp");
					$fields = $instance->getFields();
					$fieldquery = generateFields("rp");
					$this->query = 
						"select SQL_CALC_FOUND_ROWS
						rp.pkey,
						rp." . FieldMap::GetFirstNameField() . ",
						rp." . FieldMap::GetLastNameField() . ",
						rp.type,
						coalesce(m.name, sq.name),
						coalesce(rc.phone,
									rc.email,
									concat(
										coalesce(rc.addr1,''), ' ',
										coalesce(rc.addr2,''), ' ',
										coalesce(rc.city,''), ' ',
										coalesce(rc.state,''), ' ',
										coalesce(rc.zip,''))
								) as destination,
						rc.numattempts,
						from_unixtime(rc.starttime/1000),
						coalesce(rc.result,
								rp.status) as result,
						rp.status,
						u.login,
						j.name,
						rc.resultdata,
						sw.resultdata
						$fieldquery
						from reportperson rp
						inner join job j on (rp.jobid = j.id)
						inner join user u on (u.id = j.userid)
						left join	reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
						left join	message m on
										(m.id = rp.messageid)
						left join surveyquestionnaire sq on (sq.id = j.questionnaireid)
						left join surveyweb sw on (sw.personid = rp.personid and sw.jobid = rp.jobid)
					
						where rp.jobid='$jobid'
						$orderquery
						$usersql
						";
				} else {
					//errorlog("Report generator called with missing param: jobid for survey report");
					return false;
				}
				break;
			case 'attendance':
			case 'emergency':
			case 'callsreport':
			
				$usersql = $USER->userSQL("rp");
				$personquery="";
				$phonequery="";
				$emailquery="";
				$datestartquery=""; 
				$dateendquery=""; 
				$priorityquery=""; 
				$unnotifiedquery="";
				
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
				
				if(isset($params['unnotified'])){
					$unnotifiedquery = $params['unnotified'] ? " and rp.status = 'fail'" : "";
				}
				
				$search = $personquery . $phonequery . $emailquery . $datestartquery . $dateendquery . $priorityquery . $unnotifiedquery;
				if($orderquery == ""){
					$orderquery .= " order by rp.personid, j.id, rc.sequence ";
				} else {
					$orderquery .= ",j.id, rc.sequence ";
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
							where 1
							$search
							$usersql
							group by personid
							$orderquery";
				break;
			case 'contacts':
				
				$usersql = $USER->userSQL("p");
				$phonequery="";
				$emailquery="";
				if(isset($params['phone'])){
					$phonequery = $params['phone'] ? " and ph.phone like '%" . DBSafe($params['phone']) . "%'" : "";
				} 
				if(isset($params['email'])){
					$emailquery = $params['email'] ? " and e.email like '%" . DBSafe($params['email']) . "%'" : "";
				}
				if(isset($params['personid'])){
					$emailquery = $params['personid'] ? " and p.pkey like '%" . DBSafe($params['personid']) . "%'" : "";
				}
				if($orderquery == "")
					$orderquery = "order by p.id";		
				$this->query = "select 
							p.id
							from person p
							left join phone ph on (ph.personid = p.id)
							left join email e on (e.personid = p.id)
							where 1
							$phonequery
							$emailquery
							$usersql
							group by p.id
							$orderquery
							";
				break;
			case 'drilldown':
				
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
				break;	
		}
		switch($this->format){
			case "html":
				$this->doHTML();
				break;
			case "pdf":
				$this->doPDF();
				break;
			case "csv":
				$this->doCSV();
				break;
		}
	}

	function doHTML(){
		$type = $this->reporttype;
		switch($type){
			case 'attendance':
			case 'emergency':
			case 'callsreport':
				$this->doHTMLCallsReport();
				break;
			case 'contacts':
				$this->doHTMLContactReport();
				break;
			case 'drilldown':
				$this->doHTMLDrilldownReport();
				break;
			case 'surveyreport':
				$this->doHTMLJobHeader(false);
				$this->doHTMLSurveyReport();
				break;
			case 'jobreport':
				if(isset($this->params['detailed']) && $this->params['detailed'])
					$this->doHTMLJobReport();
				else
					$this->doHTMLJobHeader();
				break;
			default:
				break;
		}
	}
	
	function doPDF ($forceupdate=null) {
		
		//find my report
		$ar = new Report();
		$ar->findByName(get_class($this));
		
		$ari = new ReportInstance ();
		$ari->setReport($ar);
		$params = $this->options;//copy the same options
		$params['format'] = "html";//but set format to html
		$ari->setParameters($params);
		$ari->findInstance();
		$files = $ari->generate($forceupdate);
		if (!$files)
			return false; //fail!
		
		$in = SM_ENTERPRISE_REPORT_CACHE . "/" . $files[0];
		$out = $this->getFullPath();
		convertHtmlToPdf($in,$out);
		$files = array($this->getFileName());
		
		return $files;
	}
	
	function doCSV(){
		$type = $this->reporttype;
		switch($type){
			case 'callsreport':
				break;
			case 'contacts':
				break;
			case 'jobreport':
				$this->doCSVJobReport();
				break;
			default:
				break;
		}
	}
		
	function setBaseFileName ($base) {
		$this->basefilename = $base;
	}

	
	function getFileName () {
		return $this->basefilename . $this->getExtension();
	}
	
	function getFullPath () {
		return SM_ENTERPRISE_REPORT_CACHE 
				. "/" . $this->basefilename 
				. $this->getExtension();
	}

	//setOptions
	//array of options
	function setOptions ($options) {
		$this->options = $options;
		
		
		//get format
		if (isset($this->options['format'])) {
			$this->format = $this->options['format'];
		} 
		
		//get output
		if (isset($this->options['output'])) {
			$this->output = $this->options['output'];
		} else {
			$this->output = "file";
		}
	}
	
	//converts a string of options
	//ie from a reportinstance parameter string
	//or a GET query string
	function setOptionsString ($paramstring) {
		$newoptions = array();
		//parse this into an array
		parse_str($paramstring, $newoptions);
		$this->setOptions($newoptions);
	}
	
	//setOptionsCLI
	//get options from command line params
	//parses basic params ie "-school=1 -school=2 -someflag -format=csv"
	function setOptionsCLI ($argvars, $argcount) {
		$options = array();
		for ($x = 1; $x < $argcount ; $x++) {
			if (strpos($argvars[$x], "-") === 0 ) {
				$arg = substr($argvars[$x], 1);	//get everything after the -
				if (strpos($arg, "=")) {
					list($name, $value) = explode("=", $arg);
				} else {
					$name = $arg;
					$value = 1;
				}
				
				//see if we have something set for this already
				if (isset($options[$name])) {
					//if it is an array
					if (is_array($options[$name])) {
						//then just add to it
						$options[$name][] = $value;
					} else {
						//otherwise convert it to one
						//and add its old and new values to the array
						$options[$name] = array($options[$name], $value);
					}
				} else {
					//just set the value as a single
					$options[$name] = $value;
				}
			}
		}
		
		$this->setOptions($options);
	}
	
	function doHTMLCallsReport(){
		$pagestart = $this->params['pagestart'];
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
					from_unixtime(rc.starttime/1000) as date 
					$fieldquery
					, j.id
					from reportperson rp
					left join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
					inner join job j on (rp.jobid= j.id)
					where rp.personid = '$info[3]'
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
		
		startWindow("Search Information", "padding: 3px;"); 
		?>
			<table>
				<tr><td>Person ID: <?=isset($this->params['personid']) ? $this->params['personid'] : "" ?></td></tr>
				<tr><td>Phone: <?=isset($this->params['phone']) ? $this->params['phone'] : "" ?></td></tr>
				<tr><td>Email: <?=isset($this->params['email']) ? $this->params['email'] : ""?></td></tr>
				<tr><td>Date From: <?=isset($this->params['date_start']) ? $this->params['date_start'] : "" ?></td></tr>
				<tr><td>Date To: <?=isset($this->params['date_end']) ? $this->params['date_end'] : "" ?></td></tr>
				<tr><td>Priority: <?=$priority?></td></tr>
				<tr><td>People Found: <?=$total?></td></tr>
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
						"Date");
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
					$alt++;
					echo $alt % 2 ? '<tr>' : '<tr class="listAlt">';
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
<?
							for($i=2;$i<count($fields)+2;$i++){
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
				setColVisability(searchresultstable, 8+<?=$count?>, new getObj("hiddenfield".concat('<?=$index?>')).obj.checked);
				<?
				$count++;
			}
		?>
		</script>
		<?
	
	}
	function doHTMLContactReport(){
	
		$fieldlist = $this->reportinstance->getFields();
		$activefields = $this->reportinstance->getActiveFields();
		$fieldcount = count($fieldlist);
		$idlist = QuickQueryList($this->query);
		$personlist = array();
		$phonelist = array();
		$emaillist = array();
		$fieldquery = generateFields("p");
		foreach($idlist as $id){
			$personquery = "select
							p.pkey as pkey, 
							p." . FieldMap::GetFirstNameField() . " as firstname, 
							p." . FieldMap::GetLastNameField() . " as lastname 
							$fieldquery
							 from person p
							where p.id = '$id'";
							
			$personrow = QuickQueryRow($personquery);
			$personlist[$id] = $personrow;
			$phonelist[$id] = DBFindMany("Phone", "from phone where personid = '$id'");
			$emaillist[$id] = DBFindMany("Email", "from email where personid = '$id'");
		}
		
		startWindow("Search Results", "padding: 3px;");
		
		?>
			<table width="100%" cellpadding="3" cellspacing="1" class="list" id="searchresults">
			<tr class="listHeader">
				<td>ID#</td>
				<td>First Name</td>
				<td>Last Name</td>
				<td>Sequence</td>
				<td>Destination</td>
			<?
			for($i=0;$i<20; $i++){
				if($i<10){
					$num = "f0" . $i;
				} else {
					$num = "f" . $i;
				}
				if(in_array($num, array_keys($fieldlist))){
					?><td><?=$fieldlist[$num]?></td><?
				}
			}
			
		?>
			</tr>
		<?
			$alt = 0;
			foreach($idlist as $id){
				echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
				
				$person = $personlist[$id];
				?>
					<td><?=$person[0]?></td>
					<td><?=$person[1]?></td>
					<td><?=$person[2]?></td>
					
					<?
						$first=true;
						foreach($phonelist[$id] as $phone){
							if(!$first) {
								echo $alt % 2 ? '<tr>' : '<tr class="listAlt">';
								?><td></td><td></td><td></td><?
							}
							?>
							<td><?=$phone->sequence +1?></td><td><?=fmt_phone_contact($phone->phone)?></td>
							<?
							if($first){
								$first = false;
								$count=0;
								for($i=0;$i<20; $i++){
									if($i<10){
										$num = "f0" . $i;
									} else {
										$num = "f" . $i;
									}
									if(in_array($num, array_keys($fieldlist))){
										?><td><?=$person[3+$count]?></td><?
										$count++;
									}
								}
							} else {
								for($i=1; $i<$fieldcount+1; $i++){
									?><td>&nbsp;</td><?
								}
							}
							?>
							</tr><?
						}
						foreach($emaillist[$id] as $email){
							if(!$first) {
								echo $alt % 2 ? '<tr>' : '<tr class="listAlt">';
								?><td></td><td></td><td></td><?
							}
							?><td><?=$email->sequence +1?></td><td><?=$email->email?></td>
							<? 
							if($first){
								$first = false;
								$count=0;
								for($i=0;$i<20; $i++){
									if($i<10){
										$num = "f0" . $i;
									} else {
										$num = "f" . $i;
									}
									if(in_array($num, array_keys($fieldlist))){
										?><td><?=$person[3+$count]?></td><?
										$count++;
									}
								}
							} else {
								for($i=1; $i<$fieldcount+1; $i++){
									?><td>&nbsp;</td><?
								}
							}
							?>
							</tr><?
						}
					?>
				<?
			}
		?>
			</table>
			<script langauge="javascript">
			var searchresultstable = new getObj("searchresults").obj;
			
		<?
			$count=1;
			foreach($fieldlist as $index => $field){
				?>
				setColVisability(searchresultstable, 2+<?=$count?>, new getObj("hiddenfield".concat('<?=$index?>')).obj.checked);
				<?
				$count++;
			}
		?>
			</script>
		<?
		endWindow();
	}
	
	function doHTMLDrilldownReport(){
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
			<table>
			<tr><td> ID#: <?=$pkey?> </td></tr>
			<tr><td> First Name: <?=$firstname?> </td></tr>
			<tr><td> Last Name: <?=$lastname?> </td></tr>
			<tr><td> Job Name: <?=$jobname?> </td></tr>
			<tr><td> Total Attempts: <?=$totalattempts?></td></tr>
			</table>
		<? 
		endWindow(); 
		?>
		<br>
		<?
		startWindow("Fields");
		?>
			
			<table width="100%" cellpadding="3" cellspacing="1" class="list" id="fields">
			<tr class="listHeader">
		<?
				foreach($fieldlist as $index => $fieldname){
					?><td><?=$fieldname?></td><?
				}
		?>
			</tr>
			<tr>
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
	
	function doHTMLJobHeader($display = true){
		//////////////////////////////////////
		// Processing
		//////////////////////////////////////
		$jobid = $this->params['jobid'];
		if ($jobid) {
			$job = new Job($jobid);
			
			//TODO check if there are new workitems, then display a pie chart with % queued
			if (QuickQuery("select personid from reportperson where status='new' and jobid='$jobid' limit 1")) {
				$isprocessing = true;
			} else {
				$isprocessing = false;
		
				$validstamp = time();
				$jobstats = array ("validstamp" => $validstamp);
				$jobtypes = explode(",",$job->type);
		
				//--------------- SURVEY ---------------
				if(in_array("survey",$jobtypes) !== false) {
		
					$questionnaire = new SurveyQuestionnaire($job->questionnaireid);
					if ($questionnaire->hasphone)
						$jobtypes[] = "phone";
					if ($questionnaire->hasweb)
						$jobtypes[] = "email";
		
				}
				
				//--------------- PHONE ---------------
				if(in_array("phone",$jobtypes) !== false) {
					//people, dupes, contacted, notcontacted, %complete (actually from phone)
		
					$query = "select count(*) as cnt, rp.status
								from reportperson rp
								where rp.jobid='$jobid' and rp.type='phone' group by rp.status";
					//then need to stitch the results back together by summing them.
		
					$totalpeople = 0;
					$duplicates = 0;
					$contacted = 0;
					$notcontacted = 0;
					$result = Query($query);
					while ($row = DBGetRow($result)) {
						$totalpeople += $row[0];
		
						if ($row[1] == "success")
							$contacted += $row[0];
						else if ($row[1] == "duplicate")
							$duplicates += $row[0];
						else
							$notcontacted += $row[0];
					}
		
					//phones by cp
					$maxcallattempts = QuickQuery("select value from jobsetting where name='maxcallattempts' and jobid = '$jobid'");
					$query = "select count(*) as cnt, rc.result, sum(rp.status not in ('success','fail') and rc.numattempts < $maxcallattempts) as remaining
								from reportperson rp 
								left join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
					where rp.jobid = '$jobid'
					and rp.status != 'duplicate' and rp.type='phone'
					group by rc.result";
					//may need to clean up, null means not called yet
					//do math for the % completed
					$cpstats = array (
						"C" => 0,
						"A" => 0,
						"M" => 0,
						"N" => 0,
						"B" => 0,
						"X" => 0,
						"F" => 0,
						"nullcp" => 0
					);
					$remainingcalls = 0;
					$totalcalls = 0;
					$result = Query($query);
					while ($row = DBGetRow($result)) {
						$totalcalls += $row[0];
						$index = $row[1] !== NULL ? $row[1] : "nullcp";
						$cpstats[$index] += $row[0];
						if ($row[1] != "A" && $row[1] != "M") {
							$remainingcalls += $row[2];
						}
					}
		
					$jobstats["phone"] = $cpstats; //start with the cp codes
					//add people stats
					$jobstats["phone"]["totalpeople"] = $totalpeople;
					$jobstats["phone"]["duplicates"] = $duplicates;
					$jobstats["phone"]["contacted"] = $contacted;
					$jobstats["phone"]["notcontacted"] = $notcontacted;
		
					$jobstats["phone"]["remainingcalls"] = $remainingcalls;
					$jobstats["phone"]["totalcalls"] = $totalcalls;
					$jobstats["phone"]["percentcomplete"] = $totalcalls ? ($totalcalls - $remainingcalls)/$totalcalls : 0;
		
				}
				//-------------------------------------
		
				//--------------- EMAIL ---------------
				if(in_array("email",$jobtypes) !== false) {
					//email people, emails, % sent
					$query = "select count(*)
								from reportperson rp
								where rp.jobid='$jobid' and rp.type='email'";
		
					$emailpeople = QuickQuery($query);
		
					$query = "select count(*) totalemails, sum(rc.numattempts>0) as sent
								from reportperson rp 
								left join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
								where rp.jobid='$jobid' and rp.type='email'";
					list($totalemails, $sentemails) = QuickQueryRow($query);
		
					$jobstats["email"] = array();
					$jobstats["email"]["emailpeople"] = $emailpeople;
					$jobstats["email"]["totalemails"] = $totalemails;
					$jobstats["email"]["sentemails"] = $sentemails;
					$jobstats["email"]["percentsent"] = $sentemails/$totalemails;
		
		
				}
				//-------------------------------------
		
				//--------------- PRINT ---------------
				if(in_array("print",$jobtypes) !== false) {
					//print people %sent
					$query = "select count(*) as totoal, sum(rp.status='success') as printed
								from reportperson rp
								where rp.jobid='$jobid' and rp.type='print'";
					list($totalprint, $printed) = QuickQueryRow($query);
		
					$jobstats["print"] = array();
					$jobstats["print"]["totalprint"] = $totalprint;
					$jobstats["print"]["printed"] = $printed;
				}
				//-------------------------------------
		
				//save all these stats to the session with a jobid and timestamp so we can use them in the pie charts
				$_SESSION['jobstats'][$jobid] = $jobstats;
				$urloptions = "jobid=$jobid&valid=$validstamp";
			}
		}
		
		//////////////////////////////////////
		// DISPLAY
		//////////////////////////////////////
		if($display){
			if ($jobid && $isprocessing) {
				startWindow("Report Summary - Processing Job", NULL, false);
			?>
				<div style="padding: 10px;">Please wait while your job is processed...</div>
				<img src="graph_processing.png.php?jobid=<?= $jobid ?>" >
				<meta http-equiv="refresh" content="10;url=reportsummary.php?jobid=<?= $jobid ?>&t=<?= rand() ?>">
			
			<?
				endWindow();
			} else if ($jobid) {
				//--------------- Summary ---------------
				startWindow("Report Summary", NULL, false);
			?>
			
				<table border="0" cellpadding="3" cellspacing="0" width="100%">
			<? if (isset($jobstats["phone"])) { ?>
					<tr>
						<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Phone:</th>
						<td class="bottomBorder"><img src="graph_summary_contacted.png.php?<?= $urloptions ?>"><img src="graph_summary_completed.png.php?<?= $urloptions ?>"></td>
					</tr>
			<? } ?>
			
			<? if (isset($jobstats["email"])) { ?>
					<tr>
						<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Email:</th>
						<td class="bottomBorder"><img src="graph_summary_email.png.php?<?= $urloptions ?>"></td>
					</tr>
			<? } ?>
			
			
				</table>
			
			<?
				endWindow();
				
			echo "<br>";	
				//--------------- Detail ---------------
				startWindow("Report Detail", NULL, false);
			?>
				<table border="0" cellpadding="3" cellspacing="0" width="100%">
			<? if (isset($jobstats["phone"])) { ?>
			
				<!--
					<tr>
						<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Phone<br>(by People)</th>
						<td class="bottomBorder">
							<div class="floatingreportdata"><u>People</u><br><?= number_format($jobstats["phone"]["totalpeople"]) ?></div>
							<div class="floatingreportdata"><u>Duplicates</u><br><?= number_format($jobstats["phone"]["duplicates"]) ?></div>
							<div class="floatingreportdata"><u>Contacted</u><br><?= number_format($jobstats["phone"]["contacted"]) ?></div>
							<div class="floatingreportdata"><u>Not Contacted</u><br><?= number_format($jobstats["phone"]["notcontacted"]) ?></div>
							<div class="floatingreportdata"><u>Complete</u><br><?= sprintf("%0.2f%%",100 * $jobstats["phone"]["percentcomplete"]) ?></div>
						</td>
						<td class="bottomBorder" align="left"><img src="graph_summary_contacted.png.php?<?= $urloptions ?>"></td>
					</tr>
				-->
					<tr>
						<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Phone:</th>
						<td class="bottomBorder" >
							<div class="floatingreportdata"><u>Phone #s</u><br><?= number_format($jobstats["phone"]["totalcalls"]) ?></div>
			
							<div class="floatingreportdata"><u>Answered</u><br><?= number_format($jobstats["phone"]["A"]) ?></div>
							<div class="floatingreportdata"><u>Machine</u><br><?= number_format($jobstats["phone"]["M"]) ?></div>
							<div class="floatingreportdata"><u>Calling</u><br><?= number_format($jobstats["phone"]["C"]) ?></div>
							<div class="floatingreportdata"><u>No Answer</u><br><?= number_format($jobstats["phone"]["N"]) ?></div>
							<div class="floatingreportdata"><u>Busy</u><br><?= number_format($jobstats["phone"]["B"]) ?></div>
							<div class="floatingreportdata"><u>Disconnect</u><br><?= number_format($jobstats["phone"]["X"]) ?></div>
							<div class="floatingreportdata"><u>Fail</u><br><?= number_format($jobstats["phone"]["F"]) ?></div>
							<div class="floatingreportdata"><u>Not Attempted</u><br><?= number_format($jobstats["phone"]["nullcp"]) ?></div>
			
						</td>
						<td class="bottomBorder" align="left"><img src="graph_detail_callprogress.png.php?<?= $urloptions ?>"></td>
						<td class="bottomBorder" align="left"><img src="report_graph_hourly.png.php?jobid=<?=$jobid?>"></td>
					</tr>
			
			<? } ?>
			
			<? if (isset($jobstats["email"])) { ?>
					<tr>
						<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Email:</th>
						<td class="bottomBorder" >
							<div class="floatingreportdata"><u>People</u><br><?= number_format($jobstats["email"]["emailpeople"]) ?></div>
			
							<div class="floatingreportdata"><u>Email Addresses</u><br><?= number_format($jobstats["email"]["totalemails"]) ?></div>
							<div class="floatingreportdata"><u>% Sent</u><br><?= sprintf("%0.2f%%",100 * $jobstats["email"]["percentsent"]) ?></div>
						</td>
						<td class="bottomBorder" align="left"><img src="graph_summary_email.png.php?<?= $urloptions ?>"></td>
					</tr>
			<? } ?>
					<tr>
						<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">Contact Log:</th>
						<td >
							<? if(!isset($this->params['detailed']) || !$this->params['detailed']){ ?>
								&nbsp;<a href="reportjobdetails.php?jobid=<?= $jobid?>">View</a>&nbsp;|&nbsp;
							<? } ?>
							<a href="reportjobdetails.php?jobid=<?= $jobid?>&csv=true">Download CSV File</a>
						</td>
					</tr>
			
				</table>
			
			<?
				endWindow();
			}
		}
	}
	
	function doHTMLSurveyReport(){
		//////////////////////////////////////
		// Processing
		//////////////////////////////////////
		
		$jobid = $this->params['jobid'];
		$jobstats = $_SESSION['jobstats'][$jobid];
		$validstamp = $jobstats['validstamp']; 
		$urloptions = "jobid=$jobid&valid=$validstamp";
		//% people participated (subset of contacted)
		$query = "select count(*) as cnt
					from reportperson rp 
					left join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
					where rp.jobid='$jobid'
					and rp.status='success' and rc.result='A' and rc.participated=1";
		$phoneparticipants = QuickQuery($query);
	
		//TODO: fix jobworkitem related code
		$query = "select count(*) from surveyweb sw
				inner join reportperson rp on (rp.personid = sw.personid and rp.jobid = sw.jobid)
				where sw.status='web' and sw.jobid=$jobid";
	
		$emailparticipants = QuickQuery($query);
	
		$query = $this->query;
		
		$res = Query($query);
		echo mysql_error();
	
		$questions = array();
		$questiontext = array();
		while ($row = DBGetRow($res)) {
			$questions[$row[0]]['answers'][$row[1]] = $row[2];
			$questions[$row[0]]['label'] = $row[3] != NULL ? $row[3] : ("Question " . ($row[0] + 1));
			$questiontext[$row[0]] = $row[4] != NULL ? $row[4] : "";
		}
	
		$jobstats['survey'] = array();
		$jobstats['survey']['phoneparticipants'] = $phoneparticipants;
		$jobstats['survey']['emailparticipants'] = $emailparticipants;
		$jobstats['survey']['questions'] = $questions;
		$_SESSION['jobstats'][$jobid] = $jobstats;
		

		//////////////////////////////////////
		// DISPLAY
		//////////////////////////////////////
		
		echo "<br>";

		startWindow("Survey Results", NULL, false);
		?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr>
					<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Participation</th>
					<td class="bottomBorder">
		<? if ($jobstats['survey']['phoneparticipants'] || $jobstats['survey']['emailparticipants']) { ?>
						<img src="graph_survey_participation.png.php?<?= $urloptions ?>">
		<? } else { ?>
						No one has yet participated in this survey.
		<? } ?>
					</td>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">Responses</th>
					<td class="bottomBorder">
		<? if (count($jobstats['survey']['questions'])) { ?>
		
					<table width="100%" cellpadding="3" cellspacing="1" class="list">
		<?
					$titles = array("No.", "Question");
					for ($x = 1; $x <= 9; $x++)
						$titles[$x+2] = " #$x";
					$titles[] = "Total";
					$titles[] = "Graph";
					
					$data = array();
					foreach ($jobstats['survey']['questions'] as $index => $question) {
						$line = array_fill(1,11,"");
						foreach ($question['answers'] as $answer => $tally) {
							$line[$answer+2] = $tally;
						}
						$line[12] = array_sum($line);
						$line[0] = $index+1;
						$line[1] = $question['label'];
						$line[2] = $questiontext[$index];
						$line[14] = $validstamp;
						$data[] = $line;
					}
					
					$formatters = array("13" => "fmt_survey_graph",
										"1" => "fmt_question");
		
					showtable($data,$titles,$formatters);
		
		
		?>
					</table>
					</div>
		
		
		<? } ?>
					</td>
				</tr>
			</table>
		<?
		endWindow();
		sleep(1);
	}
	
	function doHTMLJobReport(){
		
		$fields = $this->reportinstance->getFields();
		$pagestart = $this->params['pagestart'];
		print '<br>';
		$query = $this->query;
		$query .= " limit $pagestart, 500";
		//load page to memory
		$data = array();
		$result = Query($query);
		while ($row = DBGetRow($result)) {
			$data[] = $row;
		}
		
		$query = "select found_rows()";
		$total = QuickQuery($query);
	
		startWindow("Report Details", 'padding: 3px;', false);
	
		showPageMenu($total,$pagestart,500);
		echo '<table width="100%" cellpadding="3" cellspacing="1" class="list" id="reportdetails">';
		$titles = array(0 => "ID",
						1 => "First Name",
						2 => "Last Name",
						3 => "Message",
						5 => "Destination",
						6 => "Attempts",
						7 => "Last Attempt",
						8 => "Last Result");
		$count=14;
		foreach($fields as $index => $field){
			$titles[$count] = $field;
			$count++;
		}
			
		$formatters = array(3 => "fmt_message",
							4 => "fmt_limit_25",
							5 => "fmt_destination",
							6 => "fmt_attempts",
							7 => "fmt_date",
							8 => "fmt_result");
		showTable($data,$titles,$formatters);
		echo "</table>";
		showPageMenu($total,$pagestart,500);
	
		endWindow();
		?>
		<script langauge="javascript">
			var reportdetailstable = new getObj("reportdetails").obj;
		<?
			$count=1;
			foreach($fields as $index => $field){
				?>
				setColVisability(reportdetailstable, 7+<?=$count?>, new getObj("hiddenfield".concat('<?=$index?>')).obj.checked);
				<?
				$count++;
			}
		?>
			</script>
		<?
	}
	
	function doCSVJobReport(){
	
		$query = $this->query;
		$fieldlist = $this->reportinstance->getFields();
		$activefields = $this->reportinstance->getActiveFields();
		$options = $this->params;
		
		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-disposition: attachment; filename=report.csv");
		header("Content-type: application/vnd.ms-excel");
	
		session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
	
	
		$issurvey = false;
		if (isset($_SESSION['reportjobid']) && $_SESSION['reportjobid']) {
			$job = new Job($_SESSION['reportjobid']);
			if ($job->questionnaireid) {
				$issurvey = true;
				$numquestions = QuickQuery("select count(*) from surveyquestion where questionnaireid=$job->questionnaireid");
			}
		}
	
		//generate the CSV header
		$header = '"Job Name","User","Type","Message","ID","First Name","Last Name","Destination","Attempts","Last Attempt","Last Result"';
		
		
		if (isset($options['issurvey']) && $options['issurvey']) {
			for ($x = 1; $x <= $numquestions; $x++) {
				$header .= ",Question $x";
			}
		}
		foreach($activefields as $active){
			$header .= ',"' . $fieldlist[$active] . '"';
		}
		echo $header;
		echo "\r\n";
	
		$result = Query($query);
	
		while ($row = DBGetRow($result)) {
			$row[5] = html_entity_decode(fmt_destination($row,5));
			$row[6] = (isset($row[6]) ? $row[6] : "");
			
	
			if (isset($row[7])) {
				$time = strtotime($row[7]);
				if ($time !== -1 && $time !== false)
					$row[7] = date("m/d/Y H:i",$time);
			} else {
				$row[7] = "";
			}
			$row[8] = fmt_result($row,8);
	
	
			$reportarray = array($row[11],$row[10],ucfirst($row[3]),$row[4],$row[0],$row[1],$row[2],$row[5],$row[6],$row[7],$row[8]);
	
			if ($issurvey) {
				//fill in survey result data, be sure to fill in an array element for all questions, even if blank
				$startindex = count($reportarray);
	
				$questiondata = array();
				if ($row[3] == "phone")
					parse_str($row[12],$questiondata);
				else if ($row[3] == "email")
					parse_str($row[13],$questiondata);
	
				//add data to the report for each question
				for ($x = 0; $x < $numquestions; $x++) {
					$reportarray[$startindex + $x] = isset($questiondata["q$x"]) ? $questiondata["q$x"] : "";
				}
			}
			$count=0;
			foreach($fieldlist as $index => $field){
				if(in_array($index, $activefields)){
					$reportarray[] = $row[14+$count];
				}
				$count++;
			}
			echo '"' . implode('","', $reportarray) . '"' . "\r\n";
			
		}
	}

}

?>