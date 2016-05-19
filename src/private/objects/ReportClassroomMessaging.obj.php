<?php

/**
 *
 * DIRECT EXTERNAL DEPENDENCIES
 * obj/ReportGenerator.obj.php
 * obj/Formatters.obj.php
 */

class ReportClassroomMessaging extends ReportGenerator {

	// This is public so that our unit test can access it to stub data, no other reason.
	public $queryResult = null;
	private $options = array();

	/**
	 *  Constructor
	 *
	 * @param array options An associative array of options with name/value pairs
	 * Valid options names are:
	 * 	'reldate' - value from ReldateOptions FormItem
	 *	'organizationid' - array of one or more organization ID's (from RestrictedValues FormItem)
	 */
	public function __construct($options) {
		$this->options = $options;

		// Allow the caller to specifiy the organization label to avoid calling getSystemSetting() in unit tests
		if (! isset($this->options['orglabel'])) {
			$this->options['orglabel'] = getSystemSetting('organizationfieldname', 'Organization');
		}
	}

	/**
	 * Run the classroom messaging report CSV query based on the supplied options
	 *
	 * @return boolean true on successful query operation, else false
	 */
	public function generateQuery($hackPDF = false) {

		// Figure out what the date clause will look like
		$datesql = $startdate = $enddate = '';
		if(isset($this->options['reldate']) && $this->options['reldate'] != ""){
			list($startdate, $enddate) = getStartEndDate($this->options['reldate'], $this->options);
			$startdate = date("Y-m-d", $startdate);
			$enddate = date("Y-m-d", $enddate);
			// TODO - why not a.date <= $enddate for the second condition instead of the interval addition?
			$datesql = "AND (a.date >= '$startdate' and a.date < date_add('$enddate',interval 1 day) )";
		}
		else {
			$datesql = "AND Date(e.occurence) = CURDATE()";
			$enddate = $startdate = date("Y-m-d", time());
		}

		// Figure out what the organization clause will look like
		if (isset($this->options['organizationid']) && count($this->options['organizationid'])) {
			$orglist = join("','", $this->options['organizationid']);
			$orgsql = "AND o.id in ('{$orglist}')";
		}
		else $orgsql = '';

		// Figure out a clause to restrict the view to a specific person (by personid or email)
		$personsql = $emailsql = $emailjoins = '';
		if (isset($this->options['personid']) && strlen($this->options['personid'])) {
			$personsql = "AND p.pkey = '" . DBSafe($this->options['personid']) . "'";
		}
		else if(isset($this->options['email']) && $this->options['email'] != "") {

			$emailjoins = " LEFT JOIN personguardian pg ON ( pg.personid = p.id )";
			$emailjoins .= "\n LEFT JOIN email em ON ( em.personid = p.id OR em.personid = pg.guardianpersonid )";
			$emailsql = "AND em.email = '" . DBSafe($this->options['email']) . "'";
		}

		// Figure out a filter for a specific event user (teacher) to view only alerts/events associated with them
		if (isset($this->options['userid'])) {
			$eventsql = "AND e.userid = '" . intval($this->options['userid']) . "'";
		}
		else $eventsql = '';


		// The query including clauses from above as WHERE clause "filters"
		$q = 
		"
			select
				u.login,
				concat(u.firstname, ' ', u.lastname) as teacher,
				o.orgkey,
				s.skey,
				if(rp.pkey is null, p.pkey, rp.pkey) as studentid,
				concat(if(rp.f01 is null, p.f01, rp.f01), ' ', if(rp.f02 is null, p.f02, rp.f02)) as student,
				tg.messagekey,
				e.notes,
				e.occurence AS occurrence,
				from_unixtime(if(rc.type = 'email', (select timestamp from reportemaildelivery use index (jobperson) where jobid = rc.jobid and personid = rc.personid and sequence = rc.sequence order by timestamp desc limit 1), rc.starttime/1000)) as lastattempt,
				rc.type,
				if(rc.type = 'email', rc.email, rc.phone) as destination,
				if(rc.type = 'email', (select statuscode from reportemaildelivery use index (jobperson) where jobid = rc.jobid and personid = rc.personid and sequence = rc.sequence order by timestamp desc limit 1), rc.result) as result
			from
				alert a
				inner join event e on (e.id = a.eventid)
				inner join organization o on (o.id = e.organizationid)
				inner join section s on (s.id = e.sectionid)
				inner join user u on (u.id = e.userid)
				inner join person p on (p.id = a.personid)
				inner join targetedmessage tg on (tg.id = e.targetedmessageid)
				left join job j on (j.startdate = a.date and j.type = 'alert')
				left join reportperson rp on (rp.jobid = j.id and rp.type in ('email', 'phone') and rp.personid = a.personid)
				left join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid AND rc.result NOT IN('declined'))
				{$emailjoins}
			where
				1
				{$personsql}
				{$emailsql}
				{$eventsql}
				{$orgsql}
				{$datesql};
		";
		//error_log($q);
		$this->queryResult = Query($q);

		return(is_object($this->queryResult));
	}

	/**
	 * Send the results of the summary_query straight to STDOUT as CSV data
	 */
	public function runCSV() {

		$fmt = new Formatters();

		// For each of the query result fields selected that we want to show
		// up in the CSV output, map the field to a title to use in the CSV
		$titles = array(
			'login' => 'Login',
			'teacher' => 'Teacher',
			'orgkey' => $this->options['orglabel'],
			'skey' => 'Section',
			'studentid' => 'Student ID',
			'student' => 'Student',
			'messagekey' => 'Message',
			'notes' => 'Notes',
			'occurrence' => 'Message Time',
			'lastattempt' => 'Last Attempt',
			'destination' => 'Destination',
			'result' => 'Result'
		);
		echo array_to_csv($titles) . "\n";

		$formatters = array(
			'result' => 'fmt_field_phone_or_email_result',
			'messagekey' => 'fmt_field_messagekey'
		);

		// For every row in the result data
		while ($row = $this->queryResult->fetch(PDO::FETCH_ASSOC)) {
			error_log("row " . print_r($row, true));
			echo $fmt->fmt_csv_line($row, array_keys($titles), $formatters) . "\n";
		}
	}
}

