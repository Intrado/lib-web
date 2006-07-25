<?

/*

notes:
search only on f1-10
f11-19 for general data
f20 does not automatically load

*/
//TODO could use dynamic table name

class PersonData extends DBMappedObject {

	var $personid;
	var $f01;
	var $f02;
	var $f03;
	var $f04;
	var $f05;
	var $f06;
	var $f07;
	var $f08;
	var $f09;
	var $f10;
	var $f11;
	var $f12;
	var $f13;
	var $f14;
	var $f15;
	var $f16;
	var $f17;
	var $f18;
	var $f19;
	var $f20;

	function PersonData ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "persondata";
		$this->_fieldlist = array("personid",
			"f01", "f02", "f03", "f04", "f05", "f06", "f07", "f08", "f09", "f10",
			"f11", "f12", "f13", "f14", "f15", "f16", "f17", "f18", "f19");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function refreshF20 () {
		$this->refresh(array("f20"));
	}

	function updateF20 () {
		$this->update(array("f20"));
	}

}

?>