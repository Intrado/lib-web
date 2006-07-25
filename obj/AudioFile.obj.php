<?

class AudioFile extends DBMappedObject {

	var $userid;
	var $name = "";
	var $description = "";
	var $contentid;
	var $recorddate;
	var $deleted = 0;

	function AudioFile ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "audiofile";
		$this->_fieldlist = array("userid", "name", "description", "contentid", "recorddate", "deleted");
		DBMappedObject::DBMappedObject($id);
	}

}

?>