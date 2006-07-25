<?

/*

#
# Table structure for table `reportsubscription`
#


CREATE TABLE `reportsubscription` (
  `ID` int(11) NOT NULL auto_increment,
  `UserID` int(11) NOT NULL default '0',
  `Name` varchar(20) NOT NULL default '',
  `ReportInstanceID` int(11) NOT NULL default '0',
  `ReportScheduleID` int(11) NOT NULL default '0',
  PRIMARY KEY  (`ID`),
  KEY `subscription` (`UserID`,`ReportInstanceID`)
) TYPE=MyISAM

*/

class ReportSubscription extends DBMappedObject {
	var $userid;
	var $name = "";
	var $reportinstanceid;
	var $reportscheduleid;

	//var $reportinstance; // doesnt make sence in this context, subscriptions are children of reportinstance
	var $reportschedule;

	//new constructor
	function ReportSubscription ($id = NULL) {
		$this->_tablename = "reportsubscription";
		$this->_fieldlist = array("userid", "name","reportinstanceid","reportscheduleid");
		$this->_childobjects = array("reportschedule");
		$this->_childclasses = array("ReportSchedule");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function setReportInstance ($reportinstanceobj) {
		//$this->$reportinstance = $reportinstanceobj; // doesnt make sence in this context, subscriptions are children of reportinstance
		$this->$reportinstanceid = $reportinstanceobj->id;
	}
}

?>