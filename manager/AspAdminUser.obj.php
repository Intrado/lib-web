<?
class AspAdminUser extends DBMappedObject{

	var $firstname = "";
	var $lastname = "";
	var $email = "";
	var $login = "";

	function AspAdminUser($id = NULL){
		$this->_allownulls = true;
		$this->_tablename = "aspadminuser";
		$this->_fieldlist = array("firstname","lastname", "email", "login");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);

	}

	/**static functions**/

	function doLogin($login, $password) {
		$login = dbsafe($login);
		$password = dbsafe($password);

		$query = "SELECT id FROM aspadminuser WHERE login='$login'
					AND password=password('$password')";
		return QuickQuery($query);
	}

	function runCheck($password) {

		$password = DBSafe($password);
		$login = DBSafe($this->login);
		$query = "SELECT id FROM aspadminuser
					WHERE login = '$login'
					AND password = password('$password')";
		if(QuickQuery($query)){
			return TRUE;
		}
		return FALSE;
	}
}

?>