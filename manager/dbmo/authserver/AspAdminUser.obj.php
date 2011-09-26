<?
class User extends DBMappedObject {

	var $login = "";
	//Do not store password
	var $firstname = "";
	var $lastname = "";
	var $email = "";
	var $preferences = "";
	var $permissions = "";
	var $queries;
	var $deleted = 0;
	
	//new constructor
	function AspAdminUser ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "aspadminuser";
		$this->_fieldlist = array("login","firstname", "lastname",
								"email", "preferences","permissions","queries","deleted");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>
