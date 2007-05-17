<?

class Import extends DBMappedObject {

	var $uploadkey;
	var $userid;
	var	$listid;
	var $name;
	var $description;
	var $status;
	var $type;
	var $ownertype;
	var $updatemethod;
	var $lastrun;
	var $datamodifiedtime;

	function Import ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "import";
		$this->_fieldlist = array("uploadkey","userid", "listid", "name", "description", "status", "type","ownertype", "updatemethod", "lastrun","datamodifiedtime");
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


	function upload ($data) {
		return QuickUpdate("update import set data='" . DBSafe($data) . "', datamodifiedtime=now() where id=" . $this->id);
	}

	function download () {
		return QuickQuery("select data from import where id=" . $this->id);
	}

}

?>