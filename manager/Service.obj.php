<?
class Service extends DBMappedObject{
	var $serverid;
	var $type;
	var $runmode;
	var $notes;
	var $attributesarray = false;

	function Service($id = NULL){
		$this->_allownulls = false;
		$this->_tablename = "service";
		$this->_fieldlist = array("serverid","type","runmode","notes");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
	
	function getAttribute($name, $default = false, $refresh = false) {
		if ($this->attributesarray === false || $refresh)
			$this->attributesarray = QuickQueryList("select name, value from serviceattribute where serviceid = ?", true, false, array($this->id));
		
		if (isset($this->attributesarray[$name]))
			return $this->attributesarray[$name];
		else
			return $default;
	}
	
	function setAttribute($name, $value) {
		QuickUpdate("insert into serviceattribute (serviceid, name, value) values (?,?,?) on duplicate key update value = ?", false, array($this->id, $name, $value, $value));
		$this->attributesarray[$name] = $value;
	}
	
	static function getRunModes() {
		return array('active'=>'Active','standby'=>'Standby','all'=>'All');
	}
	
	static function getTypes() {
		return array('commsuite'=>'CommSuite','kona'=>'Kona');
	}
}

?>