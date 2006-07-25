<?

class SpecialTask extends DBMappedObject {

	var $type;
	var $assignedto;
	var $data = "";
	var $lastcheckin;

	function SpecialTask ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "specialtask";
		$this->_fieldlist = array("type", "assignedto", "data","lastcheckin");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
	
	function getData($field) {
		parse_str($this->data, $data);
		return $data[$field];
	}
	
	function setData($field, $value) {
		$data = array();
		parse_str($this->data, $data);
		
		$data[$field] = $value;
		
		$pairs = array();
		foreach($data as $key => $value)
			$pairs[] = urlencode($key) . '=' . urlencode($value);
		$this->data = implode('&',$pairs);
	}
}

?>