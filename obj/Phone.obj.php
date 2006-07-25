<?

class Phone extends DBMappedObject {

	var $personid;
	var $phone;
	var $sequence;

	function Phone ($id = NULL) {
		$this->_tablename = "phone";
		$this->_fieldlist = array("personid", "phone", "sequence");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function format ($phone) {
		if (strlen($phone) == 10)
			return "(" . substr($phone,0,3) . ") " . substr($phone,3,3) . "-" . substr($phone,6,4);
		else if (strlen($phone) == 7)
			return  substr($phone,0,3) . "-" . substr($phone,3,4);
		else
			return $phone;	
	}

	function parse ($phone) {
		return ereg_replace("[^0-9]*","",$phone);
	}
}

?>