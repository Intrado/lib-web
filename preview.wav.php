<?
include_once("inc/common.inc.php");

include_once('inc/securityhelper.inc.php');
include_once('inc/content.inc.php');
include_once("obj/Content.obj.php");
include_once("obj/Message.obj.php");
include_once("obj/MessagePart.obj.php");
include_once("obj/AudioFile.obj.php");
include_once("obj/Voice.obj.php");
include_once("obj/FieldMap.obj.php");

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

function writeWav ($data) {
	$name = secure_tmpname("tmp","preview_parts",".wav");
	if (file_put_contents($name,$data))
		return $name;
}

if(isset($_GET['id'])) {
	$id = DBSafe($_GET['id']);
	if (userOwns("message",$id)) {
		$message = new Message($id);
		$parts = DBFindMany("MessagePart", "from messagepart where messageid=$message->id order by sequence");
		$voices = DBFindMany("Voice","from ttsvoice");

		// -- digest the message --
		$renderedparts = array();
		$curpart = 0;

		$lastVoice = null;
		foreach ($parts as $part) {
			switch ($part->type) {
			case "A":
				//invalidate the tts joining (audio breaks tts)
				$lastVoice = null;

				$af = new AudioFile($part->audiofileid);
				$renderedparts[++$curpart] = array("a",$af->contentid);
				break;
			case "T":
				//see if we should combine, or make a new one
				if ($lastVoice == $part->voiceid) {
					//just append to the last one
					$renderedparts[$curpart][1] .= " " . $part->txt;
				} else {
					$renderedparts[++$curpart] = array("t",$part->txt,$part->voiceid);
					$lastVoice = $part->voiceid;
				}
				break;
			case "V":
				if (!($value = $_REQUEST[$part->fieldnum])) {
					$value = $part->defaultvalue;
				}
				//see if we should combine, or make a new one
				if ($lastVoice == $part->voiceid) {
					//just append to the last one
					$renderedparts[$curpart][1] .= " " . $value;
				} else {
					$renderedparts[++$curpart] = array("t",$value,$part->voiceid);
					$lastVoice = $part->voiceid;
				}
				break;
			}
		}

		// -- get the wav files --
		$wavfiles = array();
		foreach ($renderedparts as $part) {
			if ($part[0] == "a") {
				list($contenttype,$data) = contentGet($part[1]);
				$wavfiles[] = writeWav($data);
			} else if ($part[0] == "t") {
				$voice = $voices[$part[2]];
				list($contenttype,$data) = renderTts($part[1],$voice->language,$voice->gender);
				$wavfiles[] = writeWav($data);
			}
		}

		//finally, merge the wav files
		$outname = secure_tmpname("tmp","preview",".wav");
		$cmd = 'sox "' . implode('" "',$wavfiles) . '" "' . $outname . '"';

		$result = exec($cmd, $res1,$res2);


		foreach ($wavfiles as $file)
			@unlink($file);

		if(!$res2 && file_exists($outname)) {


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

		} else {
			echo "oops";
		}

		@unlink($outname);
	}
}

?>
