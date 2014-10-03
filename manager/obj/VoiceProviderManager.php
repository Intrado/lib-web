<?
/**
 * Created by IntelliJ IDEA.
 * User: nrheckman
 * Date: 8/6/14
 * Time: 1:42 PM
 */

class VoiceProviderManager {
	var $customerDbConnection = null;
	var $voices = null;

	function __construct($customerDbConnection) {
		$this->customerDbConnection = $customerDbConnection;
	}

	/**
	 * Switch the customer attached to this class to the indicated tts voice provider
	 *
	 * If $allowAdditionalProviders is true, enable those voices which are only
	 * available through another provider also.
	 *
	 * @param {string} $provider
	 * @param bool $allowAdditionalProviders
	 */
	function switchTo($provider, $allowAdditionalProviders = true) {
		if ($this->voices === null)
			$this->voices = $this->getAllVoices();

		$voiceIdsToEnable = array();
		$languageGenderSelected = array();
		foreach ($this->voices as $id => $voice) {
			if ($voice->provider == $provider) {
				$voiceIdsToEnable[] = $id;
				$languageGenderSelected[$voice->languagecode . ':' . $voice->gender] = true;
			}
		}

		// if additional languages, which are only available from another provider, should be enabled
		if ($allowAdditionalProviders) {
			foreach ($this->voices as $id => $voice) {
				$languageGender = $voice->languagecode . ':' . $voice->gender;
				if (!isset($languageGenderSelected[$languageGender])) {
					$voiceIdsToEnable[] = $id;
				}
			}
		}

		$this->disableAllVoices();
		$this->enableVoices($voiceIdsToEnable);
		$this->setDefaultProvider($provider);
	}

	/**
	 * Load and return all voices available to this customer
	 */
	protected function getAllVoices() {
		return DBFindMany('Voice', 'from ttsvoice', false, false, $this->customerDbConnection);
	}

	/**
	 * Disable all voices
	 */
	protected function disableAllVoices() {
		QuickUpdate('update ttsvoice set enabled = 0', $this->customerDbConnection, false);
	}

	/**
	 * Enable the voices provided
	 * @param {array} $voiceIds
	 */
	protected function enableVoices($voiceIds) {
		QuickUpdate('update ttsvoice set enabled = 1 where id in (' . repeatWithSeparator('?', ',', count($voiceIds)) . ')',
			$this->customerDbConnection, $voiceIds);
		QuickUpdate('update messagepart mp inner join ttsvoice t2 on (t2.id = mp.voiceid)
			set mp.voiceid = (select id from ttsvoice t where t.languagecode = t2.languagecode and t.gender = t2.gender and t.enabled = 1)',
			$this->customerDbConnection);
	}

	/**
	 * Update settings which indicate the default tts voice provider
	 * @param {string} $provider
	 */
	protected function setDefaultProvider($provider) {
		setCustomerSystemSetting('_defaultttsprovider', $provider, $this->customerDbConnection);
	}
}
?>