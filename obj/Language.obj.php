<?


class Language extends DBMappedObject {
	var $name;
	var $code;

	function Language ($id = NULL) {
		$this->_tablename = "language";
		$this->_fieldlist = array("name", "code");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
	
	//returns code=>name list of defined languages suitable for use as lookup or menu generation
	static function getLanguageMap() {
		static $languages = false;
		if ($languages === false)
			$languages = QuickQueryList("select code,name from language order by name",true);
		return $languages;
	}
	
	static function getName ($code) {
		return Language::getLanguageMap()[$code];
	}
}

?>
