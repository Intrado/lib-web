<?

/**
 * Switches to TTS provider
 *
 * @param string $provider tts provider
 * @param type $customerDB customer DB
 */
function switchTTSProviderTo($provider, $customerDB) {
	$ttsProviderManager = new VoiceProviderManager($customerDB);
	$ttsProviderManager->switchProviderTo($provider);
}

/**
 * Manages voice switches: enable/disable voices
 */
class VoiceProviderManager {

	//Loquendo voic names
	var $loquendoVoiceNames = array('Susan', 'Dave', 'Esperanza', 'Carlos', 'Montserrat', 'Jordi', 'Lisheng', 'Saskia', 'Willem', 'Milla', 'Florence', 'Bernard', 'Katrin', 'Stefan', 'Afroditi', 'Paola', 'Matteo', 'Zosia', 'Krzysztof', 'Amalia', 'Eusebio', 'Olga', 'Annika', 'Sven');
	//NeoSpeech voic names
	var $neoSpeechVoiceNames = array("James", "Julie");
	//customer DB connection
	var $customerDB;
	//key is languagecode:gender
	//NOTE this is are parallel arrays: both have the same keys
	var $loquendoVoices = array();
	var $neoSpeechVoices = array();

	function __construct($customerDB) {
		$this->customerDB = $customerDB;
		$this->loadVoices();
	}

	/**
	 * load all voices and construct provider lists
	 */
	function loadVoices() {
		//get all voices
		$voices = DBFindMany("Voice", "from ttsvoice", false, false, $this->customerDB);
		//key is languagecode:gender
		$allLoquendoVoices = array();
		$allNeoSpeechVoices = array();
		//create a list of voices for both loquendo and neospeech
		foreach ($voices as $voice) {
			$voiceLanguageGenderKey = $voice->languagecode . ":" . $voice->gender;
			if (in_array($voice->name, $this->loquendoVoiceNames)) {
				$allLoquendoVoices[$voiceLanguageGenderKey] = $voice;
			} else if (in_array($voice->name, $this->neoSpeechVoiceNames)) {
				$allNeoSpeechVoices[$voiceLanguageGenderKey] = $voice;
			} else {
				error_log("No TTS provided found for voice name=" . $voice->name);
			}
		}

		//find common voices based on language code and gender
		foreach ($allLoquendoVoices as $key => $voice) {
			if (isset($allNeoSpeechVoices[$key])) {
				$voiceLanguageGenderKey = $voice->languagecode . ":" . $voice->gender;
				$this->loquendoVoices[$voiceLanguageGenderKey] = $voice;
				$this->neoSpeechVoices[$voiceLanguageGenderKey] = $allNeoSpeechVoices[$key];
			}
		}
	}

	/**
	 * Switch voices
	 *
	 * @param Voice $fromVoice voice to disable
	 * @param Voice $toVoice to enable
	 */
	function switchVoices($fromVoice, $toVoice) {
		//disable old provider
		QuickUpdate("update ttsvoice set enabled=0 where id=?", $this->customerDB, array($fromVoice->id));
		//enable new provider
		QuickUpdate("update ttsvoice set enabled=1 where id=?", $this->customerDB, array($toVoice->id));
		//update message parts
		QuickUpdate("update messagepart set voiceid=? where voiceid=?", $this->customerDB, array($toVoice->id, $fromVoice->id));
	}

	/**
	 * Switch customer to TTS provider
	 * @param string $provider TTS provider
	 */
	function switchProviderTo($provider) {
		Query("BEGIN", $this->customerDB, false);
		switch ($provider) {
			case "loquendo":
				foreach ($this->loquendoVoices as $key => $loquendoVoice) {
					$this->switchVoices($this->neoSpeechVoices[$key], $loquendoVoice);
				}
				break;
			case "neospeech":
				foreach ($this->loquendoVoices as $key => $loquendoVoice) {
					$this->switchVoices($loquendoVoice, $this->neoSpeechVoices[$key]);
				}
				break;
			default:
				error_log("Unknown tts provider=" . $provider);
		}
		//now change deafult provider
		setCustomerSystemSetting('_defaultttsprovider', $provider, $this->customerDB);
		Query("COMMIT", $this->customerDB, false);
	}

}

?>
