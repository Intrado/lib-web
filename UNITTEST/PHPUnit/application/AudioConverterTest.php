<?
/**
 * User: nrheckman
 * Date: 9/23/14
 * Time: 4:20 PM
 */

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));

require_once("{$konadir}/obj/AudioConverter.obj.php");

// a global object for controlling the behavior of the executeWithTimeout function
$GLOBALS['commandControl'] = array();

function executeWithTimeout($cmd) {
	$GLOBALS['commandControl']['lastCommand'] = $cmd;
	if ($GLOBALS['commandControl']['throw']) {
		throw new Exception();
	}
	return $GLOBALS['commandControl']['response'];
}

function secure_tmpname($prefix, $postfix) {
	return "$prefix.$postfix";
}

class AudioConverterTest extends PHPUnit_Framework_TestCase {
	/**
	 * @var AudioConverter
	 */
	var $converter;

	// before each test
	public function setUp() {
		$GLOBALS['commandControl'] = array('response' => true, 'throw' => false, 'lastCommand' => false);
		$this->converter = new AudioConverter();
	}

	public function test_getModernSoxVersion() {
		$GLOBALS['commandControl']['response'] = "sox:      SoX v14.4.1\n";
		$version = $this->converter->getSoxVersion();

		$this->assertEquals('modern', $version, 'Should parse as a modern sox version');
	}

	public function test_getLegacySoxVersion() {
		$GLOBALS['commandControl']['response'] = "sox: Version 12.17.7\n";
		$version = $this->converter->getSoxVersion();

		$this->assertEquals('legacy', $version, 'Should parse as a modern sox version');
	}

	public function test_combineFiles() {
		$files = array('file1', 'file2', 'file3');
		$filename = $this->converter->combineFiles($files);

		$command = $GLOBALS['commandControl']['lastCommand'];
		foreach ($files as $file) {
			$this->assertTrue((strpos($command, $file) !== false), 'Command executed should contain file: '. $file);
		}
		$this->assertNotNull($filename, 'Should return a filename');
	}
}