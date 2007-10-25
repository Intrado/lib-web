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
	var $skipheaderlines = 0;

	function Import ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "import";
		$this->_fieldlist = array("uploadkey","userid", "listid", "name", "description", "status", "type","ownertype", "updatemethod", "lastrun","datamodifiedtime","skipheaderlines");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function runNow($importid = null) {
		if (!isset($importid))
			$importid = $this->id;

		QuickUpdate("call start_import($importid)");
		QuickUpdate("update import set status='queued' where id=$importid and status != 'running'");
	}


	function upload ($data) {
		return QuickUpdate("update import set data='" . DBSafe($data) . "', datamodifiedtime=now() where id=" . $this->id);
	}

	function download () {
		return QuickQuery("select data from import where id=" . $this->id);
	}

}

?>