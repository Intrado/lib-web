<?
class Message extends DBMappedObject {

	var $userid;
	var $name;
	var $description;
	var $data = ""; //for headers
	var $type;
	var $modifydate;
	var $lastused;
	var $deleted = 0;
	var $permanent = 0;
	
	//generated members
	var $header1;
	var $header2;
	var $header3;
	var $subject;
	var $fromname;
	var $fromaddress;
	var $fromemail;


	function Message ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "message";
		$this->_fieldlist = array("userid", "name", "description", "type", "data", "deleted","modifydate", "lastused", "permanent");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function readHeaders () {
		//parse_str($this->data, $data);
		$data = sane_parsestr($this->data);
		foreach($data as $key => $value)
		{
			$this->$key = $value;
		}
	}

	function stuffHeaders () {
		if($this->type == 'email')
		{
			$this->data = 'subject=' . urlencode($this->subject) . '&fromname=' .  urlencode($this->fromname) . '&fromemail=' . urlencode($this->fromemail);
		}
		elseif($this->type == 'print')
		{
			$this->data = 'header1=' . urlencode($this->header1) . '&header2=' .  urlencode($this->header2) . '&header3=' . urlencode($this->header3) . '&fromaddress=' . urlencode($this->fromaddress);
		}
	}

	function firstVoiceID() {
		return QuickQuery("select voiceid from messagepart where messageid = $this->id order by sequence limit 1");
	}

	function parse ($data, &$errors = NULL, $defaultvoiceid=null) {
		global $USER;

		if ($errors == NULL)
			$errors = array();

		$txtpart = "";
		$parts = array();
		$partcount = 0;
		$defaultvoice = new Voice($defaultvoiceid);
		$currvoiceid = $defaultvoiceid;
		while (true) {
			//get dist to next field and type of field
			$pos_f = strpos($data,"<<");
			$pos_a = strpos($data,"{{");
			$pos_l = strpos($data,"[[");

			$poses = array();
			if($pos_f !== false)
				$poses[] = $pos_f;
			if($pos_a !== false)
				$poses[] = $pos_a;
			if($pos_l !== false)
				$poses[] = $pos_l;

			if(!count($poses))
				break;

			$pos = min($poses);
			if($pos !== false){
				if($pos === $pos_f)
					$type = "V";
				if($pos === $pos_a)
					$type = "A";
				if($pos === $pos_l)
					$type = "newlang";
			}

			//make a text part up to the pos of the field
			$txt = substr($data,0,$pos);
			if (strlen($txt) > 0) {
				$part = new MessagePart();
				$part->type="T";
				$part->txt = $txt;
				//$part->messageid = $this->id; // assign ID afterwards so ID is set
				$part->sequence = $partcount++;
				if($currvoiceid !== null)
					$part->voiceid = $currvoiceid;
				$parts[] = $part;
			}

			$pos += 2; // pass over the begintoken

			switch($type){
				case "A":
					$endtoken = "}}";
					break;
				case "V":
					$endtoken = ">>";
					break;
				case "newlang":
					$endtoken = "]]";
					break;
			}
			//$endtoken = ($type == "A") ? "}}" : ">>";
			$length = @strpos($data,$endtoken,$pos+1); // assume at least one char for audio/field name

			if ($length === false) {
				$errors[] = "Can't find end of field, was expecting '$endtoken'";
				$length = 0;
			} else {
				$length -= $pos;

				$token  = substr($data,$pos,$length);
				$part = new MessagePart();
				$part->type = $type;
				//$part->messageid = $this->id; // assign ID afterwards so ID is set


				switch ($type) {
					case "A":
						$part->sequence = $partcount++;
						$query = "select id from audiofile where userid=? and name=? and deleted = 0";

						$audioid = QuickQuery($query, false, array($USER->id, $token));
						if ($audioid !== false) {
							//find an audiofile with this name
							$part->audiofileid = $audioid;
							$parts[] = $part;
						} else {
							$errors[] = "Can't find audio file named '$token'";
						}

						break;
					case "V":
						$part->sequence = $partcount++;
						if (strpos($token,":") !== false) {
							list($fieldname,$defvalue) = explode(":",$token);
						} else {
							$fieldname = $token;
							$defvalue = "";
						}
						$query = "select fieldnum from fieldmap where name=? and fieldnum like 'f%'";

						$fieldnum = QuickQuery($query, false, array($fieldname));

						if ($fieldnum !== false) {
							$part->fieldnum = $fieldnum;
							$part->defaultvalue = $defvalue;
							if($currvoiceid !== null)
								$part->voiceid = $currvoiceid;
							$parts[] = $part;
						} else {
							$errors[] = "Can't find field named '$fieldname'";
						}
						break;
					case "newlang":
						if(isset($defaultvoice->gender)){
							$currvoiceid = QuickQuery("select id from ttsvoice where language = ? and gender = ?",
								false, array(strtolower($token), $defaultvoice->gender));
							if($currvoiceid == false){
								$currvoiceid = QuickQuery("select id from ttsvoice where language = ? and gender = ?",false, array(strtolower($token), 
									($defaultvoice->gender=="female"?"male":"female")));
							}
							if($currvoiceid == false){
								$errors[] = "Can't find that language: " . $token . ".";
								$currvoiceid = null;
							}
						}
						break;
				}
			}
			//skip the end if we found it
			if ($length)
				$skip = $pos + $length +2;
			else
				$skip = $pos + $length ;

			$data = substr($data,$skip );
		}

		//get trailing txt part;
		if (strlen($data) > 0) {
			$part = new MessagePart();
			$part->type="T";
			$part->txt = $data;
			//$part->messageid = $this->id; // assign ID afterwards so ID is set
			$part->sequence = $partcount++;
			if($currvoiceid !== null)
				$part->voiceid = $currvoiceid;
			$parts[] = $part;
		}

		return $parts;
	}

	function format ($parts) {

		$map = FieldMap::getMapNames();
		$data = "";
		$voices = DBFindMany("Voice", "from ttsvoice");
		$currvoiceid=null;
		foreach ($parts as $part) {
			if($currvoiceid == null){
				$currvoiceid = $part->voiceid;
			} else if($part->voiceid != $currvoiceid){
				$data .= "[[" . ucfirst($voices[$part->voiceid]->language) . "]]";
				$currvoiceid = $part->voiceid;
			}
			switch ($part->type) {
			case 'A':
				$part->audiofile = new AudioFile($part->audiofileid);
				$data .= "{{" . $part->audiofile->name . "}}";
				break;
			case 'T':
				$data .= $part->txt;
				break;
			case 'V':
				$data .= "<<" . $map[$part->fieldnum];

				if ($part->defaultvalue !== null && strlen($part->defaultvalue) > 0)
					$data .= ":" . $part->defaultvalue;
				$data .= ">>";
				break;
			}
		}

		return $data;
	}

	static function renderMessageParts($id, $fields) {
		$parts = DBFindMany("MessagePart", "from messagepart where messageid=$id order by sequence");
		return Message::renderParts($parts,$fields);
	}
	static function renderParts($parts, $fields) {
		// -- digest the message --
		$renderedparts = array();
		$curpart = 0;

		foreach ($parts as $part) {
			switch ($part->type) {
			case "A":
				$af = new AudioFile($part->audiofileid);
				$renderedparts[++$curpart] = array("a",$af->contentid);
				break;
			case "T":
				$renderedparts[++$curpart] = array("t",$part->txt,$part->voiceid);
				break;
			case "V":
				if (!isset($fields[$part->fieldnum]) || !($value = $fields[$part->fieldnum])) {
					$value = $part->defaultvalue;
				}
				$renderedparts[++$curpart] = array("t",$value,$part->voiceid);
				break;
			}
		}
		return $renderedparts;
	}

	static function playAudio($id, $fields,$audioformat = "wav",$intro = "") {
		$renderedparts = Message::renderMessageParts($id, $fields);
		Message::playParts($renderedparts,$audioformat,$intro);
	}
	static function playParts($renderedparts, $audioformat = "wav",$intro = "") {
		
		$voices = DBFindMany("Voice","from ttsvoice");

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
		if($intro && file_exists($intro)){
			$intro = $intro?('"' . $intro . '" "media/2secondsilence.wav" '):'';
		}
		
		//finally, merge the wav files
		$outname = secure_tmpname("preview",".wav");
		
		$messageparts = empty($wavfiles)?'':'"' . implode('" "',$wavfiles) . '" ';
		$cmd = 'sox ' . $intro . $messageparts . '"' . $outname . '"';
		$result = exec($cmd, $res1, $res2);

		foreach ($wavfiles as $file)
			unlink($file);
	
		if (!$res2 && file_exists($outname)) {	
			if($audioformat == "mp3") {
				$outnamemp3 = secure_tmpname("preview",".mp3");
				$cmd = 'lame -S -b 64 "' . $outname . '" "' . $outnamemp3 . '"';
				$result = exec($cmd, $res1, $res2);
				if (!$res2 && file_exists($outname)) {
					$data = file_get_contents ($outnamemp3); //readfile seems to cause problems
					header("HTTP/1.0 200 OK");
					if (isset($_GET['download']))
						header('Content-type: application/x-octet-stream');
					else {
						header("Content-Type: audio/mpeg");
					} 
					header("Content-disposition: attachment; filename=message.$audioformat");
					header('Pragma: private');
					header('Cache-control: private, must-revalidate');
					header("Content-Length: " . strlen($data));
					header("Connection: close");
					echo $data;
				} else {
					echo _L("An error occurred trying to generate the preview file. Please try again.");
				}
				unlink($outnamemp3);
			} else {		
				$data = file_get_contents ($outname); // readfile seems to cause problems
				header("HTTP/1.0 200 OK");
				if (isset($_GET['download']))
					header('Content-type: application/x-octet-stream');
				else {
					header("Content-Type: audio/wav");
				} 
				header("Content-disposition: attachment; filename=message.$audioformat");
				header('Pragma: private');
				header('Cache-control: private, must-revalidate');
				header("Content-Length: " . strlen($data));
				header("Connection: close");
				echo $data;
			}
		} else {
			echo _L("An error occurred trying to generate the preview file. Please try again.");
		}

		unlink($outname);
	}

	
	// The only reliable way to check the message length is to render it. Return negative value on error. 
	static function getAudioLength($id, $fields) {
		$size = -1;
		$renderedparts = Message::renderMessageParts($id, $fields);
		$voices = DBFindMany("Voice","from ttsvoice");

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
		$outname = secure_tmpname("preview",".wav");
		
		$messageparts = empty($wavfiles)?'':'"' . implode('" "',$wavfiles) . '" ';
		$cmd = 'sox ' . $messageparts . '"' . $outname . '"';
		$result = exec($cmd, $res1, $res2);

		foreach ($wavfiles as $file)
			unlink($file);
	
		if (!$res2 && file_exists($outname)) {	
			$data = file_get_contents ($outname); // readfile seems to cause problems	
			$size = strlen($data);	
		} 

		unlink($outname);	
		return $size;
	}
}


$MESSAGE_TYPES = array('phone' => 'Phone Message', 'email' => 'Email Message', 'print' => 'Print Message')

?>
