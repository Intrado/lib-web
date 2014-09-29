<?
/**
 * Convert audio files between different formats
 *
 * User: nrheckman
 * Date: 9/22/14
 * Time: 11:16 AM
 */
class AudioConverter {
	private static $MODERN_SOX = array('name' => 'modern', 'pattern' => '/.*v14\.[0-9]+\.[0-9]+.*/');
	private static $LEGACY_SOX = array('name' => 'legacy', 'pattern' => '/.*12\.17\.[0-9]+.*/');

	private $supportedFormats;
	private $soxVersion;

	/**
	 * @return array of supported audio formats
	 */
	public function getSupportedFormats() {
		if (!$this->supportedFormats) {
			$formats = array('wav','aiff','au','aif','mp3', '3gp');
			switch ($this->getSoxVersion()) {
				case AudioConverter::$MODERN_SOX['name']:
					$formats[] = 'm4a';
					break;
			}
			$this->supportedFormats = $formats;
		}
		return $this->supportedFormats;
	}

	/**
	 * Attempt discovery of the current version of sox and return it as either 'modern' or 'legacy'
	 * @return null|string
	 */
	public function getSoxVersion() {
		if (!$this->soxVersion) {
			$stdOut = '';
			try {
				$stdOut = executeWithTimeout("sox --version", 100);
			} catch (Exception $e) {
				// command failed! Maybe the old version of sox is available?
				try {
					$stdOut = executeWithTimeout("sox -V 2>&1", 100, 1);
				} catch (Exception $e) {
					// don't do anything...
				}
			}
			$lines = explode("\n", $stdOut);
			$versionLine = $lines[0];
			if (preg_match(AudioConverter::$MODERN_SOX['pattern'], $versionLine)) {
				$this->soxVersion = AudioConverter::$MODERN_SOX['name'];
			} else if (preg_match(AudioConverter::$LEGACY_SOX['pattern'], $versionLine)) {
				$this->soxVersion = AudioConverter::$LEGACY_SOX['name'];
			} else {
				$this->soxVersion = null;
			}
		}
		return $this->soxVersion;
	}

	/**
	 * Combine all files together. Files must be in the exact same audio format.
	 * @param array $files files to be combined
	 * @return string full path to file containing combined output
	 */
	public function combineFiles($files) {
		$quotedFilesList = '"'. implode('" "',$files). '"';
		$outputFile = secure_tmpname('combine','.wav');
		$cmd = "sox $quotedFilesList \"$outputFile\" ";
		executeWithTimeout($cmd, 10 * 1000);
		return $outputFile;
	}

	/**
	 * Converts the data stored in the provided filename into a mono 8k wav file.
	 * @param string $filename the full path to the file to convert
	 * @param string|null $mimeType optional argument that can be used to identify the file type if the filename lacks an extension
	 * @return string the full path to the file which contains the output in the new format
	 * @throws Exception
	 */
	public function getMono8kPcm($filename, $mimeType = null) {
		$sourceFile = $this->createTempFile($filename, $mimeType);
		$outputFile = secure_tmpname(basename($filename),'.wav');

		// use ffmpeg for these specific mime-types
		if ($mimeType == 'audio/x-caf' || $mimeType == 'audio/3gpp' || $mimeType == 'audio/3gpp2') {
			$cmd = "ffmpeg -y -i \"$sourceFile\" -ar 8000 -ac 1 \"$outputFile\"";
		} else {
			// Use sox to convert all other file types, or when the mime-type is not known
			if ($this->getSoxVersion() == AudioConverter::$MODERN_SOX['name']) {
				$cmd = "sox \"$sourceFile\" -b 16 -e signed-integer \"$outputFile\" channels 1 rate 8k";
			} else {
				$cmd = "sox \"$sourceFile\" -r 8000 -c 1 -s -w \"$outputFile\" ";
			}
		}

		try {
			executeWithTimeout($cmd, 10 * 1000);
		} catch (Exception $e) {
			@unlink($sourceFile);
			throw $e;
		}
		@unlink($sourceFile);
		return $outputFile;
	}

	/**
	 * Copy the specified audio file to a temporary location and return it's filename
	 * @param string $filename the full path to the file to copy
	 * @param string|null $mimeType optional mime-type of the file contents
	 * @return string the full path to the created file
	 * @throws Exception
	 */
	private function createTempFile($filename, $mimeType) {
		// get file extension and verify it's one we support
		$path_parts = pathinfo($filename);
		$ext = isset($path_parts['extension']) ? $path_parts['extension'] : "";
		if (strlen($ext) < 1 || !in_array(strtolower($ext), $this->getSupportedFormats())) {
			// if uncertain, let's try the mime type
			if ($mimeType && $mimeType == 'audio/mpeg') {
				$ext = "mp3";
			} else {
				$ext = "wav"; // default
			}
		}

		$tempFile = secure_tmpname(basename($filename). 'orig', ".$ext");
		if(!copy($filename, $tempFile)) {
			throw new Exception("Unable to copy to temporary file");
		}
		return $tempFile;
	}
}