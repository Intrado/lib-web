<?

class JobAutoReport extends ReportGenerator{
	
	function generateQuery(){
		global $USER;
		$instance = $this->reportinstance;
		$params = $this->params = $instance->getParameters();
		$this->reporttype = $params['reporttype'];
		
		$orderquery = getOrderSql($this->params);
		$rulesql = getRuleSql($this->params, "rp");
		
		if(isset($params['jobid'])){
			$jobid = DBSafe($params['jobid']);
		} else {
			if(isset($params['datestart']))
				$datestart = date("Y-m-d", strtotime($params['datestart']));
			else
				$datestart = date("Y-m-d", strtotime("today"));
			if(isset($params['dateend']))
				$dateend = date("Y-m-d", strtotime($params['dateend']));
			else
				$dateend = date("Y-m-d", strtotime("now"));
			$joblist = QuickQueryList("select j.id from job j where j.startdate < '$dateend' and (j.finishdate > '$datestart' or j.enddate > '$datestart')");
		}
		$resultquery = "";
		if(isset($params['result']) && $params['result']){
			$resultquery = " and rc.result = '" . $params['result'] . "' ";
		}
		
		$searchquery = isset($jobid) ? " and rp.jobid='$jobid'" : " and rp.jobid in ('" . implode("','", $joblist) ."')";
		$searchquery .= $resultquery;
		$fieldquery = generateFields("rp");

		$this->query = 
			"select SQL_CALC_FOUND_ROWS
			rp.pkey,
			rp." . FieldMap::GetFirstNameField() . " as firstname,
			rp." . FieldMap::GetLastNameField() . " as lastname,
			rp.type,
			coalesce(m.name, sq.name) as messagename,
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
			from_unixtime(rc.starttime/1000) as lastattempt,
			coalesce(rc.result,
					rp.status) as result,
			rp.status,
			u.login,
			rp.type as jobtype,
			j.name as jobname,
			rc.numattempts as attempts,
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
		
			where 1 
			$searchquery
			$rulesql
			$orderquery
			";

			
	}

	
	function setReportFile(){
		$this->reportfile = "jobautoreport.jasper";
	}
	
	function getReportSpecificParams(){
		$sms = QuickQuery("select count(smsmessageid) from job where id in ('" . $this->params['joblist'] . "')") ? "1" : "0";
		$params = array("jobId" => $this->params['jobid'],
						"jobcount" => "1"
						"hassms" => $sms);
		return $params;
	}
}

?>