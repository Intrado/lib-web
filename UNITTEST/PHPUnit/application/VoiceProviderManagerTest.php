<?

require_once(realpath(dirname(dirname(__FILE__)) . '/konaenv.php'));
require_once(realpath(dirname(dirname(__FILE__)) . '/DBStub.php'));


require_once("{$konadir}/inc/common.inc.php");
require_once("{$konadir}/obj/Voice.obj.php");
require_once("{$konadir}/obj/VoiceProviderManager.obj.php");

class PDOMock extends PDO {

	public function __construct() {

	}

}

function QuickUpdate($query, $db, $args) {

}

/**
 * @backupGlobals disabled
 * @backupStaticAttributes disabled
 */
class VoiceProviderManagerTest extends PHPUnit_Framework_TestCase {

	var $voiceManager;
	var $db; //mock db connection

	public function setup() {
		global $queryRules;
		// 1) Hit the reset switch!
		$queryRules->reset();
		$this->mockVoices();

		$queryRules->add("/BEGIN/", null);
		$queryRules->add("/COMMIT/", null);

		$this->db = $this->getMockBuilder('PDOMock')
				->getMock();
		$this->voiceManager = new VoiceProviderManager($this->db);
	}

	private function mockVoices() {
		global $queryRules;

		$results = array(
			array(
				'id' => '1',
				'language' => 'english',
				'languagecode' => 'en',
				'gender' => 'male',
				'name' => 'Dave',
				'enabled' => '1',
			),
			array(
				'id' => '2',
				'language' => 'english',
				'languagecode' => 'en',
				'gender' => 'female',
				'name' => 'Susan',
				'enabled' => '0',
			),
			array(
				'id' => '3',
				'language' => 'english',
				'languagecode' => 'en',
				'gender' => 'male',
				'name' => 'James',
				'enabled' => '0',
			),
			array(
				'id' => '4',
				'language' => 'english',
				'languagecode' => 'en',
				'gender' => 'female',
				'name' => 'Julie',
				'enabled' => '0',
			),
			array(
				'id' => '5',
				'language' => 'spanish',
				'languagecode' => 'es',
				'gender' => 'male',
				'name' => 'Carlos',
				'enabled' => '1',
			)
		);

		// 1) SQL response: get voices
		$queryRules->add("/from ttsvoice/", $results);
		return $results;
	}

	public function tearDown() {
		unset($this->voiceManager);
	}

	public function test_loadVoices() {
		$loquendoVoices = $this->voiceManager->loquendoVoices;
		$neoSpeechVoices = $this->voiceManager->loquendoVoices;
		$this->assertTrue((count($loquendoVoices) == 2), "2 overlapping Loquendo voices expected but found: " . count($loquendoVoices));
		$this->assertTrue((count($neoSpeechVoices) == 2), "2 overlapping NeoSpeech voices expected but found: " . count($neoSpeechVoices));
	}

	public function test_switchToLoquendo() {
		global $queryRules;
		$queryRules->add("/from setting where name/", array('_defaultttsprovider'), array(array("_defaultttsprovider" => "neospeech")));

		$mockManager = $this->getMockBuilder('VoiceProviderManager')
				->setConstructorArgs(array($this->db))
				->setMethods(array('switchVoices'))
				->getMock();
		//$mockManager->loadVoices();
		$loquendoVoices = $mockManager->loquendoVoices;
		$neoSpeechVoices = $mockManager->neoSpeechVoices;
		$this->assertTrue((count($loquendoVoices) == 2), "2 overlapping Loquendo voices expected but found: " . count($loquendoVoices));
		$this->assertTrue((count($neoSpeechVoices) == 2), "2 overlapping NeoSpeech voices expected but found: " . count($neoSpeechVoices));

		$fromMale = clone $neoSpeechVoices["en:male"];
		$toMale = clone $loquendoVoices["en:male"];

		$mockManager->expects($this->at(0))
				->method('switchVoices')
				->with($fromMale, $toMale);

		$fromFemale = clone $neoSpeechVoices["en:female"];
		$toFemale = clone $loquendoVoices["en:female"];

		$mockManager->expects($this->at(1))
				->method('switchVoices')
				->with($fromFemale, $toFemale);

		$mockManager->switchProviderTo("loquendo");
	}

	public function test_switchToNeoSpeech() {
		global $queryRules;
		$queryRules->add("/from setting where name/", array('_defaultttsprovider'), array(array("_defaultttsprovider" => "loquendo")));

		$mockManager = $this->getMockBuilder('VoiceProviderManager')
				->setConstructorArgs(array($this->db))
				->setMethods(array('switchVoices'))
				->getMock();
		//$mockManager->loadVoices();
		$loquendoVoices = $mockManager->loquendoVoices;
		$neoSpeechVoices = $mockManager->neoSpeechVoices;
		$this->assertTrue((count($loquendoVoices) == 2), "2 overlapping Loquendo voices expected but found: " . count($loquendoVoices));
		$this->assertTrue((count($neoSpeechVoices) == 2), "2 overlapping NeoSpeech voices expected but found: " . count($neoSpeechVoices));

		$toMale = clone $neoSpeechVoices["en:male"];
		$fromMale = clone $loquendoVoices["en:male"];

		$mockManager->expects($this->at(0))
				->method('switchVoices')
				->with($fromMale, $toMale);

		$toFemale = clone $neoSpeechVoices["en:female"];
		$fromFemale = clone $loquendoVoices["en:female"];

		$mockManager->expects($this->at(1))
				->method('switchVoices')
				->with($fromFemale, $toFemale);

		$mockManager->switchProviderTo("neospeech");
	}

}

?>
