<?
class Message extends DBMappedObject {

	var $userid;
	var $name;
	var $description;
	var $data = ""; //for headers
	var $type;
	var $lastused;
	var $deleted = 0;

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
		$this->_fieldlist = array("userid", "name", "description", "type", "data", "deleted", "lastused");
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
		$currvoiceid = null;
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
						$audioname = DBSafe($token);
						$query = "select id from audiofile where userid=$USER->id and name='$audioname' and deleted = 0";

						$audioid = QuickQuery($query);
						if ($audioid !== false) {
							//find an audiofile with this name
							$part->audiofileid = $audioid;
							$parts[] = $part;
						} else {
							$errors[] = "Can't find audio file named '$audioname'";
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
						$fieldname = DBSafe($fieldname);
						$query = "select fieldnum from fieldmap where name='$fieldname' and fieldnum like 'f%'";

						$fieldnum = QuickQuery($query);

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
							$currvoiceid = QuickQuery("select id from ttsvoice where language = '" . DBSafe(strtolower($token)) . "' and gender = '" . $defaultvoice->gender ."'");
							if($currvoiceid == false){
								$errors[] = "Can't find that language: " . $token . ". Only English and Spanish are available";
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
				if (!($value = $fields[$part->fieldnum])) {
					$value = $part->defaultvalue;
				}
				$renderedparts[++$curpart] = array("t",$value,$part->voiceid);
				break;
			}
		}
		return $renderedparts;
	}

	static function playAudio($id, $fields){

	$message = new Message($id);
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
	$cmd = 'sox "' . implode('" "',$wavfiles) . '" "' . $outname . '"';

	$result = exec($cmd, $res1, $res2);


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
		echo "An error occuring trying to generate the preview file. Please try again.";
	}

	@unlink($outname);
	}

	// copy this message, the parts, and attachments
	function copyNew() {
		$newmsg = new Message($this->id);
		$newmsg->id = null;
		$newmsg->create();

		$parts = DBFindMany("MessagePart", "from messagepart where messageid=$this->id");
		foreach ($parts as $part) {
			$newpart = $part->copyNew(); // TODO should this instead take the messageid as a param? would we ever copy without updating the messageid
			$newpart->messageid = $newmsg->id;
			$newpart->update();
		}

		QuickUpdate("insert into messageattachment (messageid,contentid,filename,size,deleted) " .
					"select $newmsg->id, ma.contentid, ma.filename, ma.size, 1 as deleted " .
					"from messageattachment ma where ma.messageid=$this->id and not deleted");

		return $newmsg;
	}
}


$MESSAGE_TYPES = array('phone' => 'Phone Message', 'email' => 'Email Message', 'print' => 'Print Message')

?>
