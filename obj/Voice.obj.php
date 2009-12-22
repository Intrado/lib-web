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
		$voices = DBFindMany("Voice","from ttsvoice t join language l where l.name = t.language order by t.id", "t");
		$retval = array();
		foreach ($voices as $voice) {
			$retval[$voice->language.":".$voice->gender] = $voice;
		}
		return $retval;
	}

	// return array of language names (indexed by language code if $paired) that are supported languages for tts, based on customer language table
	static function getTTSLanguages($paired = false) {
		if ($paired)
			return QuickQueryList("select distinct l.code, l.name from ttsvoice t join language l where l.name = t.language order by t.id", true);
		else
			return QuickQueryList("select distinct l.name from ttsvoice t join language l where l.name = t.language order by t.id");
	}
	
	static function getPreferredVoice($languagecode, $gender) {
		$voiceid = QuickQuery("select t.id from language l join ttsvoice t on l.ttsvoiceid = t.id where l.code=? and t.gender=?",false,array($languagecode,$gender));

		if($voiceid === false ) {
			if($gender == "Female") {
				$voiceid = QuickQuery("select t.id from language l join ttsvoice t on l.ttsvoiceid = t.id where l.code=? and t.gender='Male'",false,array($languagecode));
			} else if($gender == "Male") {
				$voiceid = QuickQuery("select t.id from language l join ttsvoice t on l.ttsvoiceid = t.id where l.code=? and t.gender='Female'",false,array($languagecode));
			}
		}
		if($voiceid	=== false)
			$voiceid = 1; // default to english
	}
}

?>
