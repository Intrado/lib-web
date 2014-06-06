<?

/**
 * Switches to TTS provider
 *
 * @param string $provider tts provider
 * @param string $dmMethod  DM method for the customer
 * @param type $customerDB customer DB
 */
function switchTTSProviderTo($provider, $dmMethod, $customerDB) {
	$ttsProviderManager = new VoiceProviderManager($customerDB);
	$ttsProviderManager->switchProviderTo($provider, $dmMethod);
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
	 * Return all overlapping voices for the provider:
	 *  Ex: 
	 * Provider A's voices=[en:male(id1), en:female(id2), es:male(id3)] 
	 * Provider B's voices=[en:male(id4), en:female(id5), es:female(id6)]
	 * Provider C's voices=[en:male(id7), tr:female(id8), es:male(id9)]
	 * 	getOverlappingVoicesForProvider($provider)  where provider is A, returns [en:male(id4), en:female(id5), en:male(id7), es:male(id9)]
	 * 
	 * 
	 * @param string $provider tts provider
	 * @return array overlapping voices for the provider
	 */
	function getOverlappingVoicesForProvider($provider) {
		$commonVoices = array();
		$providerVoices = $this->providerVoices[$provider];
		foreach ($this->providerVoices as $ttsProvider => $voices) {
			if ($provider != $ttsProvider) {
				foreach ($voices as $key => $voice) {
					if (isset($providerVoices[$key])) { //exists in provider voices
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
	 * Switch voice id from one voice ($fromVoice) to another ($toVoice) for all messageparts.
	 *
	 * @param Voice $fromVoice voice to switch from
	 * @param Voice $toVoice voice to switch to
	 */
	function switchVoices($fromVoice, $toVoice) {
		//update message parts
		QuickUpdate("update messagepart set voiceid=? where voiceid=?", $this->customerDB, array($toVoice->id, $fromVoice->id));
	}

	/**
	 * Check to see if smart call is enabled or not.
	 * 
	 * @param string $dmMethod dm method
	 */
	function isSmartCallEnabled($dmMethod) {
		$enabledSmartCall = QuickQuery("select 1 from custdm where enabledstate != 'disabled' limit 1", false, false);
		return ($dmMethod != 'asp' || $enabledSmartCall);
	}

	/**
	 * Sets everything to Loquendo for Smart Call
	 * @param type $provider
	 */
	function enableSmartCall($provider) {
		//disable all
		QuickUpdate("update ttsvoice set enabled = 0 ", $this->customerDB, false);
		QuickUpdate("update ttsvoice set enabled = 1 where provider=? ", $this->customerDB, array($provider));
		setCustomerSystemSetting('_defaultttsprovider', $provider, $this->customerDB);
	}

	/**
	 * Switch customer to TTS provider
	 * 
	 * @param string $provider TTS provider
	 * @param string $dmMethod DM method
	 */
	function switchProviderTo($provider, $dmMethod) {
		Query("BEGIN", $this->customerDB, false);
		//first check if smart call is enabled or not. If it is, change everything to Loquendo
		if ($this->isSmartCallEnabled($dmMethod)) { //Smart Call
			//default to loquendo
			$provider = 'loquendo';
			$this->enableSmartCall($provider);
		} else {
			//enable all in case customer switched from Smart Call to ASP
			QuickUpdate("update ttsvoice set enabled = 1 ", $this->customerDB, false);
		}
		//following call will switch overlapping voices to given provider.
		$voicesToEnable = $this->providerVoices[$provider];
		$commonVoices = $this->getOverlappingVoicesForProvider($provider);
		foreach ($commonVoices as $key => $voices) {
			$this->toggleVoice($voicesToEnable[$key], 1); //enable provider for this voice langugagecode:gender
			foreach ($voices as $voice) {
				$this->toggleVoice($voice, 0); //disable voice
				$this->switchVoices($voice, $voicesToEnable[$key]);
			}
		}
		//now change deafult provider
		setCustomerSystemSetting('_defaultttsprovider', $provider, $this->customerDB);

		Query("COMMIT", $this->customerDB, false);
	}

}

?>
