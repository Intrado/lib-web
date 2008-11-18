<?
include_once("inc/common.inc.php");
include_once("inc/content.inc.php");
include_once("obj/Voice.obj.php");

$ttstext = "Hello World This is a test";
$ttslanguage = "english";
$ttsgender = "female";


$voice = new Voice();
$voice->language = $ttslanguage;
$voice->gender = $ttsgender;

	list($contenttype, $data) = renderTts($ttstext, $voice->language, $voice->gender);
	$outname = writeWav($data);

	if (file_exists($outname)) {

		$data = file_get_contents ($outname); //readfile seems to cause problems

		header("HTTP/1.0 200 OK");
		if (isset($_GET['download']))
			header('Content-type: application/x-octet-stream');
		else
			header("Content-Type: audio/wav");


		header('Pragma: private');
		header('Cache-control: private, must-revalidate');
		header("Content-Length: " . strlen($data));
		header("Connection: close");

		echo $data;

	}
	@unlink($outname);

?>
