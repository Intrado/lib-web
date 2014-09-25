<?
/**
 * User: nrheckman
 * Date: 9/23/14
 * Time: 4:20 PM
 */

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
require_once(realpath(dirname(dirname(__FILE__)) .'/PhpStub.php'));

require_once("{$konadir}/obj/AudioConverter.obj.php");

// a global object for controlling the behavior of the executeWithTimeout function
$GLOBALS['commandControl'] = array();

/**
 * Mock of the required global functions which are controlled by the php $GLOBALS variable
 * @param $cmd
 * @return mixed
 * @throws Exception
 */
function executeWithTimeout($cmd) {
	$GLOBALS['commandControl']['lastCommand'] = $cmd;
	if ($GLOBALS['commandControl']['executeThrow']) {
		throw new Exception();
	}
	return $GLOBALS['commandControl']['executeReturnValue'];
}
function secure_tmpname($prefix, $postfix) {
	return "$prefix.$postfix";
}

function copy_stub() {
	return $GLOBALS['commandControl']['copyReturnValue'];
}
runkit_function_rename('copy', 'orig_copy');
runkit_function_rename('copy_stub', 'copy');

class AudioConverterTest extends PHPUnit_Framework_TestCase {
	const LEGACY_SOX_VERSION = "sox: Version 12.17.7\n";
	const MODERN_SOX_VERSION = "sox:      SoX v14.4.1\n";
	/**
	 * @var AudioConverter
	 */
	var $converter;

	// before each test
	public function setUp() {
		$GLOBALS['commandControl'] = array(
			'executeReturnValue' => true,
			'executeThrow' => false,
			'copyReturnValue' => true,
			'lastCommand' => false);
		$this->converter = new AudioConverter();
	}

	public function test_getModernSoxVersion() {
		$GLOBALS['commandControl']['executeReturnValue'] = AudioConverterTest::MODERN_SOX_VERSION;
		$version = $this->converter->getSoxVersion();

		$this->assertEquals('modern', $version, 'Should parse as a modern sox version');
	}

	public function test_getLegacySoxVersion() {
		$GLOBALS['commandControl']['executeReturnValue'] = AudioConverterTest::LEGACY_SOX_VERSION;
		$version = $this->converter->getSoxVersion();

		$this->assertEquals('legacy', $version, 'Should parse as a modern sox version');
	}

	public function test_combineFiles() {
		$files = array('file1', 'file2', 'file3');
		$filename = $this->converter->combineFiles($files);

		$this->assertNotNull($filename, 'Should return a filename');

		$command = $GLOBALS['commandControl']['lastCommand'];
		foreach ($files as $file) {
			$this->assertTrue((strpos($command, $file) !== false), 'Command executed should contain file: '. $file);
		}
	}

	public function test_convertToWavWithFfmpeg() {
		$file = 'file1';
		foreach (array('audio/x-caf', 'audio/3gpp', 'audio/3gpp2') as $mimeType) {
			$filename = $this->converter->getMono8kPcm($file, $mimeType);
			$this->assertNotNull($filename, 'Should return a filename');
			$command = $GLOBALS['commandControl']['lastCommand'];
			$this->assertTrue((strpos($command, 'ffmpeg') === 0), 'Should have called the ffmpg command');
		}
	}

	public function test_convertToWavWithModernSox() {
		// first, cause the object to cache the sox version
		$GLOBALS['commandControl']['executeReturnValue'] = AudioConverterTest::MODERN_SOX_VERSION;
		$this->converter->getSoxVersion();

		$file = 'file1';
		$GLOBALS['commandControl']['executeReturnValue'] = true;
		$filename = $this->converter->getMono8kPcm($file);
		$this->assertNotNull($filename, 'Should return a filename');

		$command = $GLOBALS['commandControl']['lastCommand'];
		$this->assertTrue((strpos($command, 'sox') === 0), 'Should have called the sox command');
	}

	public function test_convertToWavWithLegacySox() {
		// first, cause the object to cache the sox version
		$GLOBALS['commandControl']['response'] = AudioConverterTest::LEGACY_SOX_VERSION;
		$this->converter->getSoxVersion();

		$file = 'file1';
		$GLOBALS['commandControl']['response'] = true;
		$filename = $this->converter->getMono8kPcm($file);
		$this->assertNotNull($filename, 'Should return a filename');

		$command = $GLOBALS['commandControl']['lastCommand'];
		$this->assertTrue((strpos($command, 'sox') === 0), 'Should have called the sox command');
	}

	public function test_convertToWavWithException() {
		$file = 'file1';
		$exceptionThrown = false;
		$GLOBALS['commandControl']['executeThrow'] = true;
		try {
			$this->converter->getMono8kPcm($file);
		} catch (Exception $e) {
			$exceptionThrown = true;
		}
		$this->assertTrue($exceptionThrown, 'Should have thrown an exception');
	}
}