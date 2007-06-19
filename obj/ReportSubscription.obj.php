<?

class ReportSubscription extends DBMappedObject {
	var $userid;
	var $name = "";
	var $reportinstanceid;
	var $dow;
	var $dom;
	var $date;
	var $nextrun;
	var $time;

	//var $reportinstance; // doesnt make sence in this context, subscriptions are children of reportinstance
	var $reportschedule;

	//new constructor
	function ReportSubscription ($id = NULL) {
		$this->_tablename = "reportsubscription";
		$this->_fieldlist = array("userid", "name","reportinstanceid","dow", "dom", "date", "nextrun", "time");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function setReportInstance ($reportinstanceobj) {
		//$this->$reportinstance = $reportinstanceobj; // doesnt make sence in this context, subscriptions are children of reportinstance
		$this->reportinstanceid = $reportinstanceobj->id;
	}
}

?>