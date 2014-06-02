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

	//customer DB connection
	var $customerDB;
	//List of voices for each provider: key is provider and languagecode:gender
	var $providerVoices = array();

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
		foreach ($voices as $voice) {
			$voiceLanguageGenderKey = $voice->languagecode . ":" . $voice->gender;
			if (!isset($this->providerVoices[$voice->provider])) {
				$this->providerVoices[$voice->provider] = array();
			}
			$this->providerVoices[$voice->provider][$voiceLanguageGenderKey] = $voice;
		}
	}

	/**
	 * Return common voices 
	 * @param string $provider tts provider
	 * @return array common voices to this provider
	 */
	function getCommonVoices($provider) {
		$commonVoices = array();
		$providerVoices = $this->providerVoices[$provider];
		foreach ($this->providerVoices as $ttsProvider => $voices) {
			if ($provider != $ttsProvider) {
				foreach ($voices as $key => $voice) {
					if (isset($providerVoices[$key])) { //has same entry in common voices?
						if (!isset($commonVoices[$key])) {
							$commonVoices[$key] = array();
						}
						$commonVoices[$key][] = $voice;
					}
				}
			}
		}
		return $commonVoices;
	}

	/**
	 * enable or disable voice
	 *
	 * @param Voice $voice to toggle
	 */
	function toggleVoice($voice, $enable) {
		//disable old provider
		QuickUpdate("update ttsvoice set enabled=? where id=?", $this->customerDB, array($enable, $voice->id));
	}

	/**
	 * Switch voices
	 *
	 * @param Voice $fromVoice voice to disable
	 * @param Voice $toVoice to enable
	 */
	function switchVoices($fromVoice, $toVoice) {
		//update message parts
		QuickUpdate("update messagepart set voiceid=? where voiceid=?", $this->customerDB, array($toVoice->id, $fromVoice->id));
	}

	/**
	 * Switch customer to TTS provider
	 * @param string $provider TTS provider
	 */
	function switchProviderTo($provider) {
		Query("BEGIN", $this->customerDB, false);

		$voicesToEnable = $this->providerVoices[$provider];
		$commonVoices = $this->getCommonVoices($provider);
		foreach ($commonVoices as $key => $voices) {
			$this->toggleVoice($voicesToEnable[$key], 1);
			foreach ($voices as $voice) {
				$this->toggleVoice($voice, 0);
				$this->switchVoices($voice, $voicesToEnable[$key]);
			}
		}
		//now change deafult provider
		setCustomerSystemSetting('_defaultttsprovider', $provider, $this->customerDB);

		Query("COMMIT", $this->customerDB, false);
	}

}

?>
