<?

class JobAutoReport extends ReportGenerator{

	function generateQuery(){
		global $USER;
		$instance = $this->reportinstance;
		$params = $this->params = $instance->getParameters();
		$this->reporttype = $params['reporttype'];

		$rulesql = getRuleSql($this->params, "rp");

		if(isset($params['jobid'])){
			$this->params['joblist'] = 0 + $params['jobid'];
		} else {
			if(isset($params['datestart']))
				$datestart = date("Y-m-d", strtotime($params['datestart']));
			else
				$datestart = date("Y-m-d", strtotime("today"));
			if(isset($params['dateend']))
				$dateend = date("Y-m-d", strtotime($params['dateend']));
			else
				$dateend = date("Y-m-d", strtotime("now"));
			$joblist = QuickQueryList("select j.id from job j where j.startdate < '$dateend' and (j.finishdate > '$datestart' or j.enddate > '$datestart')", false, $this->_readonlyDB);
			$this->params['joblist'] = implode("','", $joblist);
		}
		$resultquery = "";
		if(isset($params['result']) && $params['result']){
			$resultquery = " and rc.result = '" . $params['result'] . "' ";
		}

		$searchquery = " and rp.jobid in ('". $this->params['joblist'] ."')";
		$searchquery .= $resultquery;
		$orgfieldquery = generateOrganizationFieldQuery("rp.personid");
		$fieldquery = generateFields("rp");

		$this->query =
			"select SQL_CALC_FOUND_ROWS
			rp.pkey,
			rp." . FieldMap::GetFirstNameField() . " as firstname,
			rp." . FieldMap::GetLastNameField() . " as lastname,
			rp.type,
			coalesce(mg.name, sq.name) as messagename,
			coalesce(rc.phone,
						rc.email,
						rc.sms,
						concat(
							coalesce(rc.addr1,''), ' ',
							coalesce(rc.addr2,''), ' ',
							coalesce(rc.city,''), ' ',
							coalesce(rc.state,''), ' ',
							coalesce(rc.zip,''))
					) as destination,
			rc.numattempts,
			from_unixtime(rc.starttime/1000) as lastattempt,
			coalesce(if(rc.result='X' and rc.numattempts<3,'F',rc.result), rp.status) as result,
			rp.status,
			u.login,
			rp.type as jobtype,
			j.name as jobname,
			rc.numattempts as attempts,
			rc.resultdata,
			sw.resultdata,
			rc.response as confirmed,
			rc.sequence as sequence,
			rc.voicereplyid as voicereplyid,
			vr.id as vrid
			$orgfieldquery
			$fieldquery
			, dl.label as label
			from reportperson rp
			inner join job j on (rp.jobid = j.id)
			inner join user u on (u.id = j.userid)
			left join	reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
			left join	messagegroup mg on
							(mg.id = j.messagegroupid)
			left join surveyquestionnaire sq on (sq.id = j.questionnaireid)
			left join surveyweb sw on (sw.personid = rp.personid and sw.jobid = rp.jobid)
			left join destlabel dl on (rc.type = dl.type and rc.sequence = dl.sequence)
			left join voicereply vr on (vr.jobid = rp.jobid and vr.personid = rp.personid and vr.sequence = rc.sequence and vr.userid = " . $USER->id . " and rc.type='phone')
			where 1 "
			. $searchquery
			. $rulesql
			. " order by rp." . FieldMap::GetLastNameField() . ", rp." . FieldMap::GetFirstNameField() .", rp.pkey";


	}


	function setReportFile(){
		$this->reportfile = "jobautoreport.jasper";
	}

	function getReportSpecificParams(){
		$hassms = QuickQuery("select exists (select * from message m where m.type='sms' and m.messagegroupid = j.messagegroupid) from job j where id in ('" . $this->params['joblist'] . "')", $this->_readonlyDB);
		$params = array("jobId" => $this->params['jobid'],
						"jobcount" => "1",
						"hassms" => $hassms);
		return $params;
	}
}

?>