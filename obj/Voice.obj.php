<?

class Voice extends DBMappedObject {

	var $language;
	var $gender;

	function Voice ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "ttsvoice";
		$this->_fieldlist = array("language", "gender");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	// return array of Voice objects (indexed "language:gender") based on customer language table
	static function getTTSVoices() {
		if (getSystemSetting("_dmmethod", "asp") == "asp") {
			$voices = DBFindMany("Voice","from ttsvoice t join language l where l.name = t.language order by t.id", "t");
		} else {
			// if flex customer, limit to English and Spanish
			$voices = DBFindMany("Voice","from ttsvoice t join language l where l.name in ('English', 'Spanish') and l.name = t.language order by t.id", "t");
		}
		$retval = array();
		foreach ($voices as $voice) {
			$retval[$voice->language.":".$voice->gender] = $voice;
		}
		return $retval;
	}

	// return array of strings, all supported languages for tts, based on customer language table
	static function getTTSLanguages() {
		if (getSystemSetting("_dmmethod", "asp") == "asp") {
			return QuickQueryList("select distinct l.name from ttsvoice t join language l where l.name = t.language order by t.id");
		} else {
			// if flex customer, limit to English and Spanish
			return QuickQueryList("select distinct l.name from ttsvoice t join language l where l.name in ('English', 'Spanish') and l.name = t.language order by t.id");
		}
	}
}

?>