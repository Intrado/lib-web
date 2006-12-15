<?

class ParentUser extends DBMappedObject{

	var $firstname = "";
	var $lastname = "";
	var $login = "";
	var $customerid = "";

	function ParentUser($id = NULL){
		$this->_allownulls = true;
		$this->_tablename = "parentuser";
		$this->_fieldlist = array("firstname","lastname", "login", "customerid");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);

	}

	/**static functions**/

	function doLogin($login, $password, $url = null) {
		$login = dbsafe($login);
		$password = dbsafe($password);
		$customerid = QuickQuery("Select id from customer where customer.hostname = '$url'");

		$query = "SELECT id FROM parentuser WHERE login='$login'
					AND password=password('$password')
					AND customerid = '$customerid'";
		return QuickQuery($query);
	}

	function setPassword ($password) {
		$query = "update parentuser set password=password('$password') "
				."where id=$this->id";
		QuickUpdate($query);
	}

	function findChildren(){
		$parentid = $this->id;
		$query = "Select person.id from person, personparent
					where personparent.parentuserid = '$parentid'
					AND personparent.personid = person.id
					GROUP BY person.id";
		$childlist = Query($query);
		$studentlist = array();
		while($row = DBGetRow($childlist)) {
			$studentlist[] = $row[0];
		}
		return $studentlist;
	}
}

?>
