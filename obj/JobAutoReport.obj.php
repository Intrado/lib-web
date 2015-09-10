<?

class JobAutoReport extends ReportGenerator{

	function generateQuery($hackPDF = false){
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
			"select
			rp.pkey,
			rp." . FieldMap::GetFirstNameField() . " as firstname,
			rp." . FieldMap::GetLastNameField() . " as lastname,
			rp.type,
			coalesce(mg.name, sq.name) as messagename,
			case rp.type when 'device' then concat(left(rd.deviceUuid,8), '...') when 'phone' then rc.phone when 'email' then rc.email when 'sms' then rc.sms else concat_ws(' ', rc.addr1, rc.addr2, rc.city, rc.state, rc.zip) end as destination,
			case rp.type when 'device' then rd.numAttempts else rc.numattempts end as numattempts,
			case rp.type when 'device' then from_unixtime(rd.startTimeMs/1000) else from_unixtime(rc.starttime/1000) end as lastattempt,
			coalesce(if(rc.result='X' and rc.numattempts<3,'F',rc.result), rd.result, rp.status) as result,
			rp.status,
			u.login,
			rp.type as jobtype,
			j.name as jobname,
			case rp.type when 'device' then rd.numAttempts else rc.numattempts end as attempts,
			rc.resultdata,
			sw.resultdata,
			rc.response as confirmed,
			case rp.type when 'device' then rd.sequence else rc.sequence end as sequence,
			rc.voicereplyid as voicereplyid,
			vr.id as vrid
			$orgfieldquery
			$fieldquery
			, (select dl.label from destlabel dl
				where dl.type = rp.type and dl.sequence = (
					rc.sequence % (select js.value from jobsetting js
						where js.jobid = rp.jobid and name = concat('max', rp.type, if((rp.type = 'email' || rp.type = 'phone'), 's', '') )
					)
				)
			) as label,
			case rp.type when 'device' then rd.recipientpersonid else rc.recipientpersonid end as recipientpersonid,
			case rp.type when 'device' then concat(' ', rdp.f01, ' ', rdp.f02) else concat(' ', rcp.f01, ' ', rcp.f02) end as recipientpersonname
			from reportperson rp
			inner join job j on (rp.jobid = j.id)
			inner join user u on (u.id = j.userid)
			left outer join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid and rc.result not in ('declined'))
			left outer join reportdevice rd on (rd.jobid = rp.jobid and rd.personid = rp.personid and rp.type = 'device' and rd.result not in ('declined'))
			left outer join messagegroup mg on (mg.id = j.messagegroupid)
			left outer join surveyquestionnaire sq on (sq.id = j.questionnaireid)
			left outer join surveyweb sw on (sw.personid = rp.personid and sw.jobid = rp.jobid)
			left outer join voicereply vr on (vr.jobid = rp.jobid and vr.personid = rp.personid and vr.sequence = rc.sequence and vr.userid = " . $USER->id . " and rc.type='phone')
			left outer join person rcp on (rc.recipientpersonid = rcp.id)
			left outer join person rdp on (rd.recipientpersonid = rdp.id)
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
