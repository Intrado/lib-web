<?

class Import extends DBMappedObject {

	var $uploadkey;
	var $customerid;
	var $userid;
	var	$listid;
	var $name;
	var $description;
	var $status;
	var $type;
	var $path;
	var $scheduleid;
	var $ownertype;
	var $updatemethod;
	var $lastrun;

	function Import ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "import";
		$this->_fieldlist = array("uploadkey", "customerid", "userid", "listid", "name", "description", "status", "type", "path", "scheduleid", "ownertype", "updatemethod", "lastrun");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function runNow($importid = null) {
		if (!isset($importid))
			$importid = $this->id;

		if (isset($_SERVER['WINDIR'])) {
			$cmd = "start php import.php -import=$importid";
			pclose(popen($cmd,"r"));
		} else {
			$cmd = "php import.php -import=$importid > /dev/null &";
			exec($cmd);
		}
	}

}

?>