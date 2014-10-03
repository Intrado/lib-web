<?
require_once(realpath(dirname(dirname(__FILE__)) . '/konaenv.php'));


require_once("{$konadir}/inc/common.inc.php");
require_once("{$konadir}/obj/Voice.obj.php");
require_once("{$konadir}/manager/obj/VoiceProviderManager.php");

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class VoiceProviderManagerTest extends PHPUnit_Framework_TestCase {

	var $voiceManager;
	var $db; //mock db connection

	public function setup() {
		$mockVoiceManager = $this->getMockBuilder('VoiceProviderManager')
			->setConstructorArgs(array(null))
			->setMethods(array('getAllVoices', 'disableAllVoices', 'enableVoices', 'setDefaultProvider'))
			->getMock();

		$mockVoiceManager->expects($this->any())
			->method('getAllVoices')
			->will($this->returnValue($this->getMockVoices()));

		$this->voiceManager = $mockVoiceManager;
	}

	/**
	 * Create a list of Voice objects similar to what would be returned by DBFindMany('Voice', ... )
	 * @return array
	 */
	private function getMockVoices() {
		$voices = array();

		$voice = new Voice();
		$voice->id = 1;
		$voice->language = 'english';
		$voice->languagecode = 'en';
		$voice->gender = 'male';
		$voice->name = 'Dave';
		$voice->enabled = '1';
		$voice->provider = 'loquendo';
		$voices[$voice->id] = $voice;

		$voice = new Voice();
		$voice->id = 2;
		$voice->language = 'english';
		$voice->languagecode = 'en';
		$voice->gender = 'female';
		$voice->name = 'Susan';
		$voice->enabled = '0';
		$voice->provider = 'loquendo';
		$voices[$voice->id] = $voice;

		$voice = new Voice();
		$voice->id = 3;
		$voice->language = 'english';
		$voice->languagecode = 'en';
		$voice->gender = 'male';
		$voice->name = 'James';
		$voice->enabled = '0';
		$voice->provider = 'neospeech';
		$voices[$voice->id] = $voice;

		$voice = new Voice();
		$voice->id = 4;
		$voice->language = 'english';
		$voice->languagecode = 'en';
		$voice->gender = 'female';
		$voice->name = 'Julie';
		$voice->enabled = '0';
		$voice->provider = 'neospeech';
		$voices[$voice->id] = $voice;

		$voice = new Voice();
		$voice->id = 5;
		$voice->language = 'spanish';
		$voice->languagecode = 'es';
		$voice->gender = 'female';
		$voice->name = 'Esperanza';
		$voice->enabled = '1';
		$voice->provider = 'loquendo';
		$voices[$voice->id] = $voice;

		$voice = new Voice();
		$voice->id = 6;
		$voice->language = 'spanish';
		$voice->languagecode = 'es';
		$voice->gender = 'male';
		$voice->name = 'Carlos';
		$voice->enabled = '1';
		$voice->provider = 'neospeech';
		$voices[$voice->id] = $voice;

		return $voices;
	}

	public function tearDown() {
		unset($this->voiceManager);
	}

	/**
	 * Assert that switching to loquendo and allowing other providers correctly enables all loquendo voices and those only provided
	 * by another provider
	 */
	public function test_switchToLoquendo() {
		$this->voiceManager->expects($this->once())
			->method('disableAllVoices');

		$this->voiceManager->expects($this->once())
			->method('enableVoices')
			->with($this->callback(function($voiceIds) {
				sort($voiceIds);
				return ($voiceIds == array(1,2,5,6));
			}));

		$this->voiceManager->expects($this->once())
			->method('setDefaultProvider')
			->with($this->equalTo('loquendo'));

		$this->voiceManager->switchTo('loquendo');
	}

	/**
	 * Assert that switching to neospeech and allowing other providers correctly enables all neospeech voices and those only provided
	 * by another provider
	 */
	public function test_switchToNeospeech() {
		$this->voiceManager->expects($this->once())
			->method('disableAllVoices');

		$this->voiceManager->expects($this->once())
			->method('enableVoices')
			->with($this->callback(function($voiceIds) {
				sort($voiceIds);
				return ($voiceIds == array(3,4,5,6));
			}));

		$this->voiceManager->expects($this->once())
			->method('setDefaultProvider')
			->with($this->equalTo('neospeech'));

		$this->voiceManager->switchTo('neospeech');
	}

	/**
	 * Assert that switching to loquendo and disallowing other providers correctly enables all loquendo voices only
	 */
	public function test_switchToLoquendoOnly() {
		$this->voiceManager->expects($this->once())
			->method('disableAllVoices');

		$this->voiceManager->expects($this->once())
			->method('enableVoices')
			->with($this->callback(function($voiceIds) {
				sort($voiceIds);
				return ($voiceIds == array(1,2,5));
			}));

		$this->voiceManager->expects($this->once())
			->method('setDefaultProvider')
			->with($this->equalTo('loquendo'));

		$this->voiceManager->switchTo('loquendo', false);
	}

	/**
	 * Assert that switching to neospeech and disallowing other providers correctly enables all neospeech voices only
	 */
	public function test_switchToNeospeechOnly() {
		$this->voiceManager->expects($this->once())
			->method('disableAllVoices');

		$this->voiceManager->expects($this->once())
			->method('enableVoices')
			->with($this->callback(function($voiceIds) {
				sort($voiceIds);
				return ($voiceIds == array(3,4,6));
			}));

		$this->voiceManager->expects($this->once())
			->method('setDefaultProvider')
			->with($this->equalTo('neospeech'));

		$this->voiceManager->switchTo('neospeech', false);
	}

}

?>
