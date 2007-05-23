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

	function parse ($data, &$errors = NULL) {
		global $USER;

		if ($errors == NULL)
			$errors = array();

		$txtpart = "";
		$parts = array();
		$partcount = 0;
		while (true) {
			//get dist to next field and type of field
			$pos_f = strpos($data,"<<");
			$pos_a = strpos($data,"{{");

			if ($pos_a !== false && $pos_f !== false) {
				if ($pos_a < $pos_f) {
					$pos = $pos_a;
					$type = "A";
				} else {
					$pos = $pos_f;
					$type = "V";
				}
			} else if ($pos_a !== false) {
				$pos = $pos_a;
				$type = "A";
			} else if ($pos_f !== false) {
				$pos = $pos_f;
				$type = "V";
			} else {
				break;
			}

			//make a text part up to the pos of the field
			$txt = substr($data,0,$pos);
			if (strlen($txt) > 0) {
				$part = new MessagePart();
				$part->type="T";
				$part->txt = $txt;
				//$part->messageid = $this->id; // assign ID afterwards so ID is set
				$part->sequence = $partcount++;
				$parts[] = $part;
			}

			$pos += 2;

			$endtoken = ($type == "A") ? "}}" : ">>";
			$length = @strpos($data,$endtoken,$pos+2);

			if ($length === false) {
				$errors[] = "Can't find end of field, was expecting '$endtoken'";
				$length = 0;
			} else {
				$length -= $pos;

				$token  = substr($data,$pos,$length);
				$part = new MessagePart();
				$part->type = $type;
				//$part->messageid = $this->id; // assign ID afterwards so ID is set
				$part->sequence = $partcount++;

				switch ($type) {
					case "A":
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
						if (strpos($token,":") !== false) {
							list($fieldname,$defvalue) = explode(":",$token);
						} else {
							$fieldname = $token;
							$defvalue = "";
						}
						$fieldname = DBSafe($fieldname);
						$query = "select fieldnum from fieldmap where name='$fieldname'";

						$fieldnum = QuickQuery($query);

						if ($fieldnum !== false) {
							$part->fieldnum = $fieldnum;
							$part->defaultvalue = $defvalue;

							$parts[] = $part;
						} else {
							$errors[] = "Can't find field named '$fieldname'";
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
			$parts[] = $part;
		}

		return $parts;
	}

	function format ($parts) {

		$map = FieldMap::getMapNames();
		$data = "";
		foreach ($parts as $part) {
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
}


$MESSAGE_TYPES = array('phone' => 'Phone Message', 'email' => 'Email Message', 'print' => 'Print Message')

?>