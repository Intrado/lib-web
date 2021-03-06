<?

class Voice extends DBMappedObject {

	var $language;
	var $languagecode;
	var $gender;
	var $name;
	var $enabled;
	var $provider;

	function Voice ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "ttsvoice";
		$this->_fieldlist = array("language", "languagecode", "gender", "name", "enabled", "provider");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	// return array of Voice objects (indexed "languagecode:gender") based on what's enabled in customer language table
	static function getTTSVoices() {
		static $voices = false;	
		if ($voices === false) {
			$tmp = DBFindMany("Voice","from ttsvoice t join language l on (t.languagecode = l.code) where t.enabled order by t.language", "t");
			$voices = array();
			foreach ($tmp as $voice) {
				$voices[$voice->languagecode.":".$voice->gender] = $voice;
			}
		}
		return $voices;
	}

	// return array of language codes and language names, $codes[$voice->languagecode] = $voice->language, that are supported languages for tts, based on what's enabled in customer language table.
	static function getTTSLanguageMap() {
		$codes = array();
		foreach (Voice::GetTTSVoices() as $voice) {
			$codes[$voice->languagecode] = $voice->language;
		}
		return $codes;
	}

	static function getPreferredVoice($languagecode, $gender) {
		$voices = Voice::GetTTSVoices();
		if (isset($voices["$languagecode:$gender"]))
			return $voices["$languagecode:$gender"]->id;
		else if (isset($voices["$languagecode:female"]))
			return $voices["$languagecode:female"]->id;
		else if (isset($voices["$languagecode:male"]))
			return $voices["$languagecode:male"]->id;
		else
			return $gender == "male" ? $voices["en:male"]->id : $voices["en:female"]->id; // default to english
	}
}

?>
