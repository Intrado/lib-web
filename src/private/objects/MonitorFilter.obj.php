<?

class MonitorFilter extends DBMappedObject {

	var $monitorid;
	var $type;
	var $val;

	function MonitorFilter ($id = NULL) {
		$this->_tablename = "monitorfilter";
		$this->_fieldlist = array("monitorid","type","val");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>