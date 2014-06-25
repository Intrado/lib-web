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

//
//function setCustomerSystemSetting($name, $value, $db) {
//	
//}

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
				'provider' => 'loquendo'
			),
			array(
				'id' => '2',
				'language' => 'english',
				'languagecode' => 'en',
				'gender' => 'female',
				'name' => 'Susan',
				'enabled' => '0',
				'provider' => 'loquendo'
			),
			array(
				'id' => '3',
				'language' => 'english',
				'languagecode' => 'en',
				'gender' => 'male',
				'name' => 'James',
				'enabled' => '0',
				'provider' => 'neospeech'
			),
			array(
				'id' => '4',
				'language' => 'english',
				'languagecode' => 'en',
				'gender' => 'female',
				'name' => 'Julie',
				'enabled' => '0',
				'provider' => 'neospeech'
			),
			array(
				'id' => '5',
				'language' => 'spanish',
				'languagecode' => 'es',
				'gender' => 'male',
				'name' => 'Carlos',
				'enabled' => '1',
				'provider' => 'loquendo'
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
		$loquendoVoices = $this->voiceManager->providerVoices["loquendo"];
		$neoSpeechVoices = $this->voiceManager->providerVoices["neospeech"];
		$this->assertTrue((count($loquendoVoices) == 3), "3 overlapping Loquendo voices expected but found: " . count($loquendoVoices));
		$this->assertTrue((count($neoSpeechVoices) == 2), "2 overlapping NeoSpeech voices expected but found: " . count($neoSpeechVoices));
	}

	public function test_switchToLoquendo() {
		global $queryRules;
		$queryRules->add("/from setting where name/", array('_defaultttsprovider'), array(array("_defaultttsprovider" => "neospeech")));
		$queryRules->add("/select 1 from custdm where enablestate != 'disabled' limit 1/", false, array(array(0)));


		$mockManager = $this->getMockBuilder('VoiceProviderManager')
				->setConstructorArgs(array($this->db))
				->setMethods(array('switchVoices'))
				->getMock();

		$loquendoVoices = $this->voiceManager->providerVoices["loquendo"];
		$neoSpeechVoices = $mockManager->getOverlappingVoicesForProvider("loquendo");
		$this->assertTrue((count($neoSpeechVoices) == 2), "2 overlapping NeoSpeech voices expected but found: " . count($neoSpeechVoices));

		$fromMale = clone $neoSpeechVoices["en:male"][0];
		$toMale = clone $loquendoVoices["en:male"];

		$mockManager->expects($this->at(0))
				->method('switchVoices')
				->with($fromMale, $toMale);

		$fromFemale = clone $neoSpeechVoices["en:female"][0];
		$toFemale = clone $loquendoVoices["en:female"];

		$mockManager->expects($this->at(1))
				->method('switchVoices')
				->with($fromFemale, $toFemale);

		$mockManager->switchProviderTo("loquendo", "asp");
	}

	public function test_switchToNeoSpeech() {
		global $queryRules;
		$queryRules->add("/from setting where name/", array('_defaultttsprovider'), array(array("_defaultttsprovider" => "loquendo")));
		$queryRules->add("/select 1 from custdm where enablestate != 'disabled' limit 1/", false, array(array(0)));

		$mockManager = $this->getMockBuilder('VoiceProviderManager')
				->setConstructorArgs(array($this->db))
				->setMethods(array('switchVoices'))
				->getMock();

		$neoSpeechVoices = $this->voiceManager->providerVoices["neospeech"];
		$loquendoVoices = $mockManager->getOverlappingVoicesForProvider("neospeech");
		$this->assertTrue((count($loquendoVoices) == 2), "2 overlapping Loquendo voices expected but found: " . count($loquendoVoices));

		$toMale = clone $neoSpeechVoices["en:male"];
		$fromMale = clone $loquendoVoices["en:male"][0];

		$mockManager->expects($this->at(0))
				->method('switchVoices')
				->with($fromMale, $toMale);

		$toFemale = clone $neoSpeechVoices["en:female"];
		$fromFemale = clone $loquendoVoices["en:female"][0];

		$mockManager->expects($this->at(1))
				->method('switchVoices')
				->with($fromFemale, $toFemale);

		$mockManager->switchProviderTo("neospeech", "asp");
	}

	public function test_switchToLoquendoWhenSmartCallEnabled() {
		global $queryRules;
		$queryRules->add("/from setting where name/", array('_defaultttsprovider'), array(array("_defaultttsprovider" => "neospeech")));
		$queryRules->add("/select 1 from custdm where enablestate != 'disabled' limit 1/", false, array(array(1)));


		$mockManager = $this->getMockBuilder('VoiceProviderManager')
				->setConstructorArgs(array($this->db))
				->setMethods(array('enableSmartCall'))
				->getMock();


		$mockManager->expects($this->once())
				->method('enableSmartCall')
				->with("loquendo");

		$mockManager->switchProviderTo("loquendo", "asp");
	}
	
	public function test_switchToLoquendoWhenSmartCallSelected() {
		global $queryRules;
		$queryRules->add("/from setting where name/", array('_defaultttsprovider'), array(array("_defaultttsprovider" => "neospeech")));
		$queryRules->add("/select 1 from custdm where enablestate != 'disabled' limit 1/", false, array(array(0)));


		$mockManager = $this->getMockBuilder('VoiceProviderManager')
				->setConstructorArgs(array($this->db))
				->setMethods(array('enableSmartCall'))
				->getMock();


		$mockManager->expects($this->once())
				->method('enableSmartCall')
				->with("loquendo");

		$mockManager->switchProviderTo("loquendo", "hybrid");
	}

	public function test_switchToNeoSpeechWhenSmartCallEnabled() {
		global $queryRules;
		$queryRules->add("/from setting where name/", array('_defaultttsprovider'), array(array("_defaultttsprovider" => "neospeech")));
		$queryRules->add("/select 1 from custdm where enablestate != 'disabled' limit 1/", false, array(array(1)));


		$mockManager = $this->getMockBuilder('VoiceProviderManager')
				->setConstructorArgs(array($this->db))
				->setMethods(array('enableSmartCall'))
				->getMock();


		$mockManager->expects($this->once())
				->method('enableSmartCall')
				->with("loquendo");

		$mockManager->switchProviderTo("neospeech", "hybrid");
	}

	public function test_switchToNeoSpeechWhenSmartCallSelected() {
		global $queryRules;
		$queryRules->add("/from setting where name/", array('_defaultttsprovider'), array(array("_defaultttsprovider" => "neospeech")));
		$queryRules->add("/select 1 from custdm where enablestate != 'disabled' limit 1/", false, array(array(0)));


		$mockManager = $this->getMockBuilder('VoiceProviderManager')
				->setConstructorArgs(array($this->db))
				->setMethods(array('enableSmartCall'))
				->getMock();


		$mockManager->expects($this->once())
				->method('enableSmartCall')
				->with("loquendo");

		$mockManager->switchProviderTo("neospeech", "hybrid");
	}

}

?>
