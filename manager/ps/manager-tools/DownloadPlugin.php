<?php

class DownloadPlugin {

	private $pluginVersion = '1.0';
	private $pluginName = '';
	private $pluginsBasePath = '';

	function __construct() {
		$this->pluginsBasePath = dirname(__FILE__) . '/../plugins';
	}

	static public function getHeaderText($pluginName) {

		$headerText = '';

		switch ($pluginName) {
			case 'sso-admin' :
				$headerText = 'SSO Admin Plugin';
				break;
			case 'contactmanager-guardian':
				$headerText = 'Contact Manager Guardian Plugin';
				break;
			default:
				$headerText = '[missing header text]';
				break;
		}

		return $headerText;
	}

	public function setParameters($params) {
		foreach ($params as $key => $value) {
			if (isset($this->$key)) {
				$this->$key = $value;
			}
		}
	}

	public function getPlugins() {
		$folders = scandir($this->pluginsBasePath);

		unset($folders[0]);   // remove .
		unset($folders[1]);   // remove '..'

		return $folders;
	}

	// retrieve available versions of plugin.
	private function getVersions($pluginPath) {
		$dir = dirname(__FILE__) . "/../plugins/{$pluginPath}/versions";

		$versions = scandir($dir);

		unset($versions[0]);   // remove '.'
		unset($versions[1]); // remove '..'

		return $versions;
	}

	// create an array to use with the control property of a form object
	private function versionsToFormArray($versions) {
		$pluginValues = array();

		foreach ($versions as $key => $version) {
			$pluginValues[$version] = $version;
		}

		return $pluginValues;
	}

	public function getPluginForm($pluginName) {

		if ($pluginName === 'sso-admin') {

			$formData = array(
				"pluginName" => array(
					"value" => $pluginName,
					"control" => array("HiddenField")
				),
				"pluginVersion" => array(
					"label" => _L('Plugin Version'),
					"value" => '1.0',
					"validators" => array(),
					"control" => array("SelectMenu", "values" => $this->versionsToFormArray($this->getVersions('sso-admin'))),
					"helpstep" => 1
				)
			);

			return $formData;
		}

		if ($pluginName === 'contactmanager-guardian') {
			$formData = array(
				"pluginName" => array(
					"value" => $pluginName,
					"control" => array("HiddenField")
				),
				"pluginVersion" => array(
					"label" => _L('Plugin Version'),
					"value" => '1.0',
					"validators" => array(),
					"control" => array("SelectMenu", "values" => $this->versionsToFormArray($this->getVersions('contactmanager-guardian'))),
					"helpstep" => 1
				),
				"fqdn" => array(
					"label" => _L('FQDN'),
					"value" => "",
					"validators" => array(
						array("ValRequired")
					),
					"control" => array("TextField"),
					"helpstep" => 2
				),
				"pluginLinkID" => array(
					"label" => _L('Plugin Link ID'),
					"value" => "",
					"validators" => array(
						array("ValRequired")
					),
					"control" => array("TextField"),
					"helpstep" => 2
				)
			);

			return $formData;
		}
	}

	public function getFilenamesToCompile() {

		$files = array();

		if ($this->pluginName === 'sso-admin') {
			$files[] = dirname(__FILE__) . "/../plugins/{$this->pluginName}/versions/{$this->pluginVersion}/plugin.xml";
		}

		if ($this->pluginName === 'contactmanager-guardian') {
			$files[] = dirname(__FILE__) . "/../plugins/{$this->pluginName}/versions/{$this->pluginVersion}/web_root/guardian/home.schoolmessenger-parent-plugin.leftnav.footer.txt";
			$files[] = dirname(__FILE__) . "/../plugins/{$this->pluginName}/versions/{$this->pluginVersion}/web_root/guardian/home_not_available.schoolsessenger-parent-plugin.leftnav.footer.txt";
		}

		return $files;
	}

	// replace placeholders with customer specific information
	public function compilePlugin($settingNames, $files) {

		$fileDatas = array();
		$fileDataRecord = array();

		foreach ($files as $fileName) {
			$fileContents = file_get_contents($fileName);

			foreach ($settingNames as $settingName => $value) {
				$fileContents = str_replace('$' . $settingName, $value, $fileContents);
			}

			$fileDataRecord['filepath'] = $fileName;
			$fileDataRecord['compiled'] = $fileContents;
			$fileDatas[] = $fileDataRecord;
		}

		return $fileDatas;
	}

	// zip up the plugin to a temporary compiled strings and return it
	public function zipPlugin($fileDatas) {

		$zip = new ZipArchive();

		$zipFile = tempnam('/tmp', 'powerschool-plugin');

		if ($zip->open($zipFile, ZipArchive::OVERWRITE) !== TRUE) {
			exit("cannot open {$zipFile}\n");
		}

		$files = new RecursiveIteratorIterator(
				new RecursiveDirectoryIterator($this->pluginsBasePath . "/{$this->pluginName}/versions/{$this->pluginVersion}"), RecursiveIteratorIterator::LEAVES_ONLY
		);

		foreach ($files as $name => $file) {
			// Skip directories (they would be added automatically)
			if (!$file->isDir()) {
				// Get real and relative path for current file
				$filePath = $file->getRealPath();
				$relativePath = preg_replace('/.*' . $this->pluginVersion . '\//i', '', $filePath);

				$zip->addFile($filePath, $relativePath);
			}
		}
		foreach ($fileDatas as $fileData) {
			$relativePath = preg_replace('/.*' . $this->pluginVersion . '\//i', '', $fileData['filepath']);
			$zip->addFromString($relativePath, $fileData['compiled']);
		}

		$zip->close();

		return $zipFile;
	}

	public function setZipMimeHeaders($customerName) {

		// http headers for zip downloads
		header("Pragma: public");
		header("Expires: 0");
		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
		header("Cache-Control: public");
		header("Content-Description: File Transfer");
		header("Content-type: application/octet-stream");
		header("Content-Disposition: attachment; filename=\"plugin-{$customerName}.zip\"");
		header("Content-Transfer-Encoding: binary");

		ob_clean();
		flush();
	}

}
