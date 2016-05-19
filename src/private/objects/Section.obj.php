<?
class Section extends DBMappedObject {
	var $skey;
	var $organizationid;
	var $c01;
	var $c02;
	var $c03;
	var $c04;
	var $c05;
	var $c06;
	var $c07;
	var $c08;
	var $c09;
	var $c10;
	
	function Section ($id = NULL) {
		$this->_allownulls = false;
		$this->_tablename = "section";
		$this->_fieldlist = array(
			"skey",
			"organizationid",
			"c01",
			"c02",
			"c03",
			"c04",
			"c05",
			"c06",
			"c07",
			"c08",
			"c09",
			"c10"
		);

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}
?>
