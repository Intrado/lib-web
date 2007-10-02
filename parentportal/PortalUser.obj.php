<?
class PortalUser extends DBMappedObject {

	var $username;
	var $firstname;
	var $lastname;
	var $zipcode;
	var $enabled;
	
	function PortalUser($id = null){
		$this->_allownulls = true;
		$this->_tablename = "portaluser";
		$this->_fieldlist = array("username", "firstname", "lastname", "zipcode", "enabled");
		
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
	
	function getAssociations() {
		//TODO: figure out portaluser to person DB table
		$associationlist = QuickQueryList("select personid from personportalmap where portaluserid = '$this->id'");
		$associations = DBFindMany("Person", "from person where id in ('" . implode("','", $associationlist) . "') and not deleted");
		return $associations;
	}
}


?>