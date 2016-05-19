<?
// TODO make generic to work with list etc...
class ValDuplicateNameCheck extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {	
		global $USER;
		$type = $args["type"];

		if ($type == 'messagegroup') {
			$existsid = QuickQuery("select id from messagegroup where name=? and userid=? and deleted=0",false,array($value,$USER->id));
			if($existsid && $existsid != $_SESSION['messagegroupid']) {
				return "$this->label: ". _L('There is already a message with this name. Please choose another.');
			}
		} else if(in_array($type, array("phone","email","sms"))) {
			$existsid = QuickQuery("select id from message where name=? and type=? and userid=? and deleted=0",false,array($value,$args["type"],$USER->id));
			if($existsid && $existsid != $_SESSION['messageid']) {
				return "$this->label: ". _L('There is already a message with this name. Please choose another.');
			}
		} else if($type == "job") {
			$existsid = QuickQuery("select id from job where not deleted and userid=? and name=? and status in ('new','scheduled','processing','procactive','active')",false,array($USER->id, $value));
			if($existsid && $existsid != $_SESSION['jobid']) {
				return "$this->label: ". _L('There is already an active %s with this name. Please choose another.', getJobTitle());
			}
		} else if($type == "jobtemplate") {
			$existsid = QuickQuery("select id from job where not deleted and userid=? and name=? and status = 'template'",false,array($USER->id, $value));
			if($existsid && $existsid != $_SESSION['jobid']) {
				return "$this->label: ". _L('There is already a %s template with this name. Please choose another.', getJobTitle());
			}
		} else if($type == "survey") {
			$existsid = QuickQuery("select id from job where not deleted and userid=? and name=? and status in ('new','scheduled','processing','procactive','active')",false,array($USER->id, $value));
			if($existsid && $existsid != $_SESSION['surveyid']) {
				return "$this->label: ". _L('There is already an active Survey with this name. Please choose another.');
			}
		} else if($type == "targetedmessagecategory") {
			$existsid = QuickQuery("select id from targetedmessagecategory where name=? and deleted = 0",false,array($value));
			if($existsid && $existsid != $_SESSION["targetedmessagecategoryid"]) {
				return "$this->label: ". _L('There is already a category with this name. Please choose another.');
			}
		}
		return true;
	}
}

?>