<?

class SubscriberPending extends DBMappedObject {

	var $subscriberid;
	var $type;
	var $value;
	var $token;

	function SubscriberPending ($id = NULL) {
		$this->_tablename = "subscriberpending";
		$this->_fieldlist = array("subscriberid","type","value","token");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}
?>