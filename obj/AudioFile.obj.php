<?

class AudioFile extends DBMappedObject {

	var $userid;
	var $name = "";
	var $description = "";
	var $contentid;
	var $recorddate;
	var $deleted = 0;
	var $permanent = 0;

	function AudioFile ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "audiofile";
		$this->_fieldlist = array("userid", "name", "description", "contentid", "recorddate", "deleted", "permanent");
		DBMappedObject::DBMappedObject($id);
	}

}


class AudioFile2 extends DBMappedObject {

	var $userid;
	var $name = "";
	var $description = "";
	var $contentid;
	var $recorddate;
	var $deleted = 0;
	var $permanent = 0;
	var $messagegroupid = 0;

	function AudioFile ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "audiofile";
		$this->_fieldlist = array("userid", "name", "messagegroupid", "description", "contentid", "recorddate", "deleted", "permanent");
		DBMappedObject::DBMappedObject($id);
	}

}

?>
