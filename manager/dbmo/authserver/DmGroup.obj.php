<?
class DmGroup extends DBMappedObject{

	var $carrier = "";
	var $state = "";

	function DmGroup($id = NULL){
		$this->_allownulls = false;
		$this->_tablename = "dmgroup";
		$this->_fieldlist = array("carrier","state","name","rateModelClassName","rateModelParams","dispatchType","routeType","notes");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>