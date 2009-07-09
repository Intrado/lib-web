<?
// TODO make generic to work with list, jobs etc...
class ValDuplicateNameCheck extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {	
		global $USER;
		$existsid = QuickQuery("select id from message where name=? and type=? and userid=? and deleted=0",false,array($value,$args["type"],$USER->id));		
		if($existsid && $existsid != $_SESSION['messageid']) {
			return "A message named $value already exists";
		}
		return true;
	}
}

?>