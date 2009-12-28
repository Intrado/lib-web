<?
// TODO make generic to work with list etc...
class ValDuplicateNameCheck extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {	
		global $USER;
		$type = $args["type"];

		if(in_array($type, array("phone","email","sms"))) {
			$existsid = QuickQuery("select id from message where name=? and type=? and userid=? and deleted=0",false,array($value,$args["type"],$USER->id));
			if($existsid && $existsid != $_SESSION['messageid']) {
				return "$this->label: ". _L('There is already a message with this name. Please choose another.');
			}
		} else if($type == "job") {
			$existsid = QuickQuery("select id from job where not deleted and userid=? and name=? and status in ('new','scheduled','processing','procactive','active')",false,array($USER->id, $value));
			if($existsid && $existsid != $_SESSION['jobid']) {
				return "$this->label: ". _L('There is already an active notification with this name. Please choose another.');
			}
		}
		return true;
	}
}

?>