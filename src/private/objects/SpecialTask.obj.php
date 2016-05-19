<?

class SpecialTask extends DBMappedObject {

	var $userid;
	var $status;
	var $type;
	var $data = "";
	var $lastcheckin;

	function SpecialTask ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "specialtask";
		$this->_fieldlist = array("userid","status","type","data","lastcheckin");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function getData($field) {
		$data = sane_parsestr($this->data);
		return (isset($data[$field]) ? $data[$field] : false);
	}

	function setData($field, $inputvalue) {

		$cleanarray = sane_parsestr($this->data);

		$cleanarray[$field] = $inputvalue;
		$pairs = array();
		foreach($cleanarray as $key => $value) {
			$pair = urlencode($key) . '=' . urlencode($value);
			$pairs[] = $pair;
		}
		$this->data = implode('&',$pairs);
	}

	function delData($field) {
		$cleanarray = sane_parsestr($this->data);

		$pairs = array();
		foreach($cleanarray as $key => $value) {
			if($key == $field) continue;
			$pair = urlencode($key) . '=' . urlencode($value);
			$pairs[] = $pair;
		}
		$this->data = implode('&',$pairs);
	}
}

?>
