<?php

class ReportClassroomMessaging {

	/**
	 * Get the classroom messaging report CSV data based on the supplied options
	 *
	 * @param array options - An associative array of options with name/value pairs
	 * Valid options names are:
	 * 	'reldate' - value from ReldateOptions FormItem
	 *	'organizationid' - array of one or more organization ID's (from RestrictedValues FormItem)
	 *
	 * @return object The query result object
	 */
	public function get_csvdata($options) {

		// Figure out what the date clause will look like
		$datesql = $startdate = $enddate = '';
		if(isset($options['reldate']) && $options['reldate'] != ""){
			list($startdate, $enddate) = getStartEndDate($options['reldate'], $options);
			$startdate = date("Y-m-d", $startdate);
			$enddate = date("Y-m-d", $enddate);
			// TODO - why not a.date <= $enddate for the second condition instead of the interval addition?
			$datesql = "AND (a.date >= '$startdate' and a.date < date_add('$enddate',interval 1 day) )";
		} else {
			$datesql = "AND Date(e.occurence) = CURDATE()";
			$enddate = $startdate = date("Y-m-d", time());
		}

		// Figure out what the organization clause will look like
		if (isset($options['organizationid']) && count($options['organizationid'])) {
			$orglist = join("','", $options['organizationid']);
			$orgsql = "AND o.id in ('{$orglist}')";
		}
		else $orgsql = '';

		// Figure out a clause to restrict the view to a specific person (by personid or email)
		if (isset($options['personid']) && strlen($options['personid'])) {
			$personsql = "AND p.pkey = '" . DBSafe($options['personid']) . "'";
		}
		else if(isset($options['email']) && $options['email'] != "") {
			$emailtable = " LEFT JOIN email e ON ( e.personid = p.id )";
			$emailtableGuardianauto = " LEFT JOIN email e ON ( e.personid = pg.guardianpersonid )";
			$emailsql = "AND e.email = '" . DBSafe($options['email']) . "'";
		}
		else $personsql = $emailsql = $emailtable = $emailtableGuardianauto = '';


		// The query including clauses from above as WHERE clause "filters"
		$result = Query("
			select
				a.id,
				rc.jobid,
				u.login,
				concat(u.firstname, ' ', u.lastname) as teacher,
				o.orgkey,
				s.skey,
				if(rp.pkey is null, p.pkey, rp.pkey) as studentid,
				concat(if(rp.f01 is null, p.f01, rp.f01), ' ', if(rp.f02 is null, p.f02, rp.f02)) as student,
				tg.messagekey,
				e.notes,
				e.occurence,
				from_unixtime(if(rc.type = 'email', (select timestamp from reportemaildelivery where jobid = rc.jobid and personid = rc.personid and sequence = rc.sequence order by timestamp limit 1), rc.starttime/1000)) as lastattempt,
				rc.type,
				if(rc.type = 'email', rc.email, rc.phone) as destination,
				if(rc.type = 'email', (select statuscode from reportemaildelivery where jobid = rc.jobid and personid = rc.personid and sequence = rc.sequence order by timestamp limit 1), rc.result) as result,
				rp.status
			from alert a
				inner join event e on (e.id = a.eventid)
				inner join organization o on (o.id = e.organizationid)
				inner join section s on (s.id = e.sectionid)
				inner join user u on (u.id = e.userid)
				inner join person p on (p.id = a.personid)
				inner join targetedmessage tg on (tg.id = e.targetedmessageid)
				left join job j on (j.startdate = a.date and j.type = 'alert')
				left join reportperson rp on (rp.jobid = j.id and rp.type in ('email', 'phone') and rp.personid = a.personid)
				left join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
				{$emailtable}
				{$emailtableGuardianauto}
			where
				1
				{$personsql}
				{$emailsql}
				{$orgsql}
				{$datesql};
		");

		return($result);
	}

	/**
	 * Send the results of the summary_query straight to STDOUT as CSV data
	 */
	public function summary_csv_to_stdout($result) {

		// set header
		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-disposition: attachment; filename=classroom_messaging_report.csv");
		header("Content-type: application/vnd.ms-excel");

		// echo out the data
		echo '"alert id", "job id", "login", "teacher", "school", "section", "student id", "student", "messagekey", "notes", "occurence", "lastattempt", "type", "destination", "result", "status"' . "\n";

		// For every row in the result data
		while ($row = $result->fetch(PDO::FETCH_ASSOC)) {

			// Translate some of the raw values into something human readable
			switch ($row['type']) {
				case 'email':
					// TODO - translate rp.status for email status significance
					break;

				case 'phone':
					// TODO - translate rc.result for phone result significance
					break;
			}
			// TODO - translate messagekey


			// Then spit the row out to STDOUT as CSV data
			echo array_to_csv($row) . "\n";
		}
	}
}

