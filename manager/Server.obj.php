<?
class Server extends DBMappedObject{

	var $name = "";
	var $notes = "";
	var $production = "";
	var $settingsarray = false;

	function Server($id = NULL){
		$this->_allownulls = false;
		$this->_tablename = "server";
		$this->_fieldlist = array("name","notes","production");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
	
	function getSetting($name, $default = false, $refresh = false) {
		if ($this->settingsarray === false || $refresh)
			$this->settingsarray = QuickQueryList("select name, value from serversetting where serverid = ?", true, false, array($this->id));
		
		if (isset($this->settingsarray[$name]))
			return $this->settingsarray[$name];
		else
			return $default;
	}
	
	function setSetting($name, $value) {
		QuickUpdate("insert into serversetting (serverid, name, value) values (?,?,?) on duplicate key update value = ?", false, array($this->id, $name, $value, $value));
		$this->settingsarray[$name] = $value;
	}
}

?>