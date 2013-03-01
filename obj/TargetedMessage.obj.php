<?
class TargetedMessage extends DBMappedObject {
	var $messagekey;
	var $targetedmessagecategoryid;
	var $overridemessagegroupid;
	var $enabled = 1;
	var $deleted = 0;

	function TargetedMessage ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "targetedmessage";
		$this->_fieldlist = array(
			"messagekey",
			"targetedmessagecategoryid",
			"overridemessagegroupid",
			"enabled",
			"deleted"
		);
		
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
	
	
	// Targeted message may be invalid if message was customized and only the the email was defined
	// and the phone was enabled
	function isValid() {			
		if ($this->overridemessagegroupid && getSystemSetting('_hasphonetargetedmessage', false)) {
			$overridemessagegroup = new MessageGroup($this->overridemessagegroupid);			
			
			return $this->isNewCustom()?$overridemessagegroup->hasDefaultMessage("phone", "voice"):true;
		}
		return true;
	}
	
	// Find out if this is a new custom targeted message, (one that does not have a default value)
	function isNewCustom() {
		return substr($this->messagekey, 0,7) == "custom-";
	}
	
}
?>
