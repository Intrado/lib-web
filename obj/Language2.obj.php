<?

// TODO: Replace with Language.obj.php once migration/db schema is finalized. For now, named Language2.obj.php for use with MessageGroupForm editor.

class Language extends DBMappedObject {
	var $name;
	var $code;
	var $ttsvoiceid;
	var $enabled;

	function Language ($id = NULL) {
		$this->_tablename = "language";
		$this->_fieldlist = array("name", "code", "ttsvoiceid", "enabled");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>