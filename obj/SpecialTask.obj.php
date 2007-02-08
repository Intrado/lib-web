<?

class SpecialTask extends DBMappedObject {

	var $customerid;
	var $status;
	var $type;
	var $data = "";
	var $lastcheckin;

	function SpecialTask ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "specialtask";
		$this->_fieldlist = array("customerid","status","type", "data","lastcheckin");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function getData($field) {
		$data = array();
		parse_str($this->data, $data);
		return get_magic_quotes_gpc() ? stripslashes($data[$field]) : $data[$field];
	}

	function setData($field, $inputvalue) {
		$data = array();
		parse_str($this->data, $data);
		
		$cleanarray = array();
		foreach($data as $key => $value) {
			$cleankey = get_magic_quotes_gpc() ? stripslashes($key) : $key;
			$cleanvalue =  get_magic_quotes_gpc() ? stripslashes($value) : $value;
			$cleanarray[$cleankey] = $cleanvalue;
		}
		$cleanarray[$field] = $inputvalue;

		$pairs = array();
		foreach($cleanarray as $key => $value)
			$pairs[] = urlencode($key) . '=' . urlencode($value);
		$this->data = implode('&',$pairs);
	}
}

?>