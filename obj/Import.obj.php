<?

class Import extends DBMappedObject {

	var $uploadkey;
	var $userid;
	var	$listid;
	var $name;
	var $description;
	var $notes;
	var $status;
	var $type;
	var $datatype = "person";
	var $ownertype;
	var $updatemethod;
	var $lastrun;
	var $datamodifiedtime;
	var $datalength = 0;
	var $skipheaderlines = 0;
	var $nsticketid;
	var $managernotes;
	

	function Import ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "import";
		$this->_fieldlist = array("uploadkey","userid", "listid", "name", "description", "notes", "status", "type","datatype","ownertype", "updatemethod", "lastrun","datamodifiedtime","datalength","skipheaderlines","nsticketid","managernotes");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function runNow($importid = null) {
		if (!isset($importid))
			$importid = $this->id;

		QuickUpdate("call start_import($importid)");
		QuickUpdate("update import set status='queued' where id=? and status != 'running'", false, array($importid));
	}


	function upload ($data) {
		$this->datalength = strlen($data);
		return QuickUpdate("update import set data=?,datalength=?,datamodifiedtime=now() where id=?", false, array($data,$this->datalength,$this->id));
	}

	function download () {
		return QuickQuery("select data from import where id=?", false, array($this->id));
	}

}

?>