<?php

class DownloadPlugin {

    private $requestedVersion = '1.0';
    private $basePath;

    function __construct($requestedVersion = "1.0") {
        $this->requestedVersion = $requestedVersion;
        $this->basePath = dirname(__FILE__) . "/../versions/{$this->requestedVersion}";
    }

    // retrieve available versions of plugin.
    public static function getVersions() {
        $dir = dirname(__FILE__) . '/../versions';
        $files = scandir($dir);

        unset($files[0]);   // remove '.'
        unset($files[1]);   // remove '..'

        return $files;
    }

    // create an array to use with the control property of a form object
    public static function getFormArray($versions) {
        $pluginValues = array();

        foreach ($versions as $key => $version) {
            $pluginValues[$version] = $version;
        }

        return $pluginValues;
    }

    // replace placeholders with customer specific information
    public function compilePlugin($settingNames) {

        $xml = $this->getPluginAsXML();
        $xmlString = $xml->asXML();

        foreach ($settingNames as $settingName => $value) {
            $xmlString = str_replace('$' . $settingName, $value, $xmlString);
        }

        return $xmlString;
    }

    // get the plugin.xml file as a simple XML object
    private function getPluginAsXML() {
        $path = $this->basePath . '/plugin.xml';

        $xml = simplexml_load_file($path);

        return $xml;
    }

    // zip up the plugin to a temporary file and return it
    public function zipPlugin($pluginXML) {
        $zip = new ZipArchive();
        $zipFile = tempnam('/tmp', 'powerschool-plugin');

        if ($zip->open($zipFile, ZipArchive::OVERWRITE) !== TRUE) {
            exit("cannot open {$zipFile}\n");
        }

        $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($this->basePath), RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $name => $file) {
            // Skip directories (they would be added automatically)
            if (!$file->isDir()) {
                // Get real and relative path for current file
                $filePath = $file->getRealPath();
                $relativePath = preg_replace('/.*' . $this->requestedVersion . '/i', '', $filePath);

                $zip->addFile($filePath, $relativePath);
            }
        }
        $zip->addFromString("plugin.xml", $pluginXML);
        $zip->close();

        return $zipFile;
    }

}
