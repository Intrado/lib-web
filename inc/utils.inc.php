<?
function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}


function array_append (&$ar1, $ar2) {
	foreach ($ar2 as $index => $value) {
		$ar1[] = $value;
	}
}

function redirect($url = NULL) {
	header('Location: ' . ($url ? $url : $_SERVER['SCRIPT_NAME']));
	exit();
}

/*
	Function added to facilitate redirect to referring page

	@param fallbackUrl - Optional param to which to redirect as a default page if
		the HTTP_REFERER is null, as in when someone types in a URL.
*/
function redirectToReferrer($fallbackUrl = NULL) {
	if (!$fallbackUrl) {
		$fallbackUrl = $_SERVER['SCRIPT_NAME'];
	}

	header('Location: ' . ($_SERVER['HTTP_REFERER'] ? $_SERVER['HTTP_REFERER'] : $fallbackUrl) );
	exit();
}

function first() {
	foreach(func_get_args() as $val)
		if($val)
			return $val;
}

/*
	Function to find the next seuential access code in a phone system
	@param currentCode The current access code for which we are trying to find a replacement
	@param userid The id of the user for whom we are trying to find a unique value
*/
function getNextAvailableAccessCode($currentCode, $userid) {
	$result=1;
	while($result !=0) {
		$nextCode = rand(1000,999999);
		$result = QuickQuery("select count(*) from user where accesscode = '$nextCode' and id != '$userid'
								AND enabled = '1'");
	}
	return $nextCode;
}

function getCustomerSystemSetting($name, $defaultvalue=false, $refresh=false, $custdb = false) {
	static $settings = array();

	if (isset($settings[$name]) && !$refresh)
		return $settings[$name];

	$value = QuickQuery("select value from setting where name = '" . DBSafe($name) . "'", $custdb);

	if($value === false) {
		$value = $defaultvalue;
	}
	return $settings[$name] = $value;
}

function getSystemSetting($name, $defaultvalue=false) {
	return getCustomerSystemSetting($name, $defaultvalue);
}

function isvalidtimestamp ($time) {
	if ($time === -1 || $time === false)
		return false;
	else
		return true;
}
/**
	Checks to see if all digits in number are all the same.
*/
function isAllSameDigit($number){
	$same = 0;
	for($itor=0;$itor<strlen($number)-1;$itor++){
		if($number[$itor] == $number[$itor+1]){
			$same = 1;
		} else {
			$same = 0;
			break;
		}
	}
	if($same == 1){
		return true;
	}
	return false;
}

/**
	Function to test if the user, pass, firstname, and last name are
	the same thing.
*/
function isSameUserPass($user, $pass, $firstname, $lastname) {
	$user = strtolower($user);
	$pass = strtolower($pass);
	$firstname = strtolower($firstname);
	$lastname = strtolower($lastname);
	if(strlen($user)>=3 && strpos($pass, $user)!==FALSE) {
		return("Username and password are too similiar");
	}
	if(strlen($firstname)>=3 && strpos($pass, $firstname)!==FALSE) {
		return("Firstname and password are too similiar");
	}
	if(strlen($lastname) >=3 && strpos($pass, $lastname)!==FALSE) {
		return("Lastname and password are too similiar");
	}
	return false;
}

/**
	returns false if password is complex
	returns msg string if password is not complex
	Php.ini setup:
		extension=php_crack.dll must not be commented out
		To add default crack library, add this line to php.ini:
			crack.default_dictionary = "/usr/share/cracklib"
		The location of the cracklib directory may differ.
*/
function isNotComplexPass($pass) {

	// Perform password check
	$check = crack_check($pass);
	if($check) {
		return false;
	} else {
		$diag = crack_getlastmessage();
		switch($diag){
			case "it is based on a dictionary word":
				return("The password is based on a word from the dictionary");
			case "it is based on a (reversed) dictionary word":
				return("The password is based on a reversed word from the dictionary");
			case "it is too simplistic/systematic":
				return("The password is too simplistic/systematic");
			case "it is all whitespace":
				return("The password cannot contain spaces");
			case "it does not contain enough DIFFERENT characters":
				return("The password needs more different characters");
			case "it is too short":
				return("The password needs to be at least 6 characters long");
			case "it's WAY too short":
				return("The password needs to be at least 6 characters long");
			case "it looks like a National Insurance number.":
				return false;
			default:
				return("Password is too weak");
		}
	}
}

function isSequential($number){
	$isseq = 0;
	$neg=0;
	if($number == "")
		return $isseq;
	if($number[0]-$number[1] == -1){
		$isseq=1;
		$neg = 0;
	} elseif($number[0]-$number[1] == 1){
		$isseq=1;
		$neg = 1;
	} else {
		return $isseq;
	}
	for($itor = 1;$itor<strlen($number)-1;$itor++){
		if($number[$itor]-$number[$itor+1] == -1 && $neg==0){
			$isseq=1;
		} elseif($number[$itor]-$number[$itor+1] == 1 && $neg == 1){
			$isseq=1;
		} else {
			$isseq=0;
			break;
		}
	}
	return $isseq;
}

//returns an adapted url safe base64 string.
function base64url_encode($data)
{
	return rtrim(strtr(base64_encode($data), '+/', '-_'),"=");
}

//modified from php.net
function base64url_decode($string) {
    $data = strtr($string, '-_','+/');
    $mod4 = strlen($data) % 4;
    if ($mod4) {
        $data .= substr('====', $mod4);
    }
    return base64_decode($data);
}

//checks emails seperated by ";" in a single string
function checkemails($emaillist) {
	if($emaillist=="")
		return false;
	$bademaillist=array();
	$emails = explode(";", $emaillist);
	$i=0;
	foreach($emails as $email){
		if($email=="")
			continue;
		if (!preg_match("/^[\w-\.]{1,}\@([\da-zA-Z-]{1,}\.){1,}[\da-zA-Z-]{2,}$/", trim($email))) {
			$bademaillist[$i] = $email;
			$i++;
		}
	}
	return $bademaillist;
}

//from php.net comments
function secure_tmpname($prefix = 'tmp', $postfix = '.dat') {
	global $SETTINGS;
	$dir = $SETTINGS['feature']['tmp_dir'];

   // validate arguments
	if (! (isset($postfix) && is_string($postfix))) {
		return false;
	}
	if (! (isset($prefix) && is_string($prefix))) {
		return false;
	}

	$filename = $dir . "/" . $prefix . microtime(true) . mt_rand() . $postfix;

	$fp = fopen($filename, "w");
	if(file_exists($filename)){
		fclose($fp);
		return $filename;
	} else {
	   return false;
	}
}


function makeparentdirectories ($filepath) {
	$parts = preg_split("/(\/|\\\)+/",$filepath);

	if (count($parts) <= 1) {
		return;
	}

	$path = false;
	$file = array_pop($parts);
	foreach ($parts as $part) {
		if ($path === false)
			$path .= $part;
		else
			$path .= "/" . $part;

		@mkdir($path);
	}
}

function sane_parsestr($url) {
	$data = array();
	if($url == "")
		return $data;
	$pairs = explode("&",$url);
	foreach ($pairs as $pair) {
		$parts = explode("=",$pair);
		if (count($parts) == 2) {
			$name = urldecode($parts[0]);
			$value = urldecode($parts[1]);
			$data[$name] = $value;
		} else if (count($parts) == 1) {
			$name = urldecode($parts[0]);
			$data[$name] = "";
		}
	}

	return $data;
}

function writeWav ($data) {
	global $SETTINGS;
	$name = secure_tmpname("preview_parts",".wav");
	if (file_put_contents($name,$data))
		return $name;
}

function playAudio($id){

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
	$outname = secure_tmpname("preview",".wav");
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

//array is the initial array
//insertitems is an array of items you want inserted
//startindex is the position before you want items inserted
function array_insert($array, $insertitems = array(), $startindex){
	$newarray = array();
	foreach($array as $index => $item){
		if($index == $startindex){
			$newarray[] = $item;
			foreach($insertitems as $insertitem){
				$newarray[] = $insertitem;
			}
		} else {
			$newarray[] = $item;
		}
	}
	return $newarray;
}

// fetches jobtype preferences and builds multidimensional array
function getDefaultContactPrefs(){
	$query = "Select type, sequence, jobtypeid, enabled from jobtypepref";
	$res = Query($query);
	$contactprefs = array();
	while($row = DBGetRow($res)){
		if(!isset($contactprefs[$row[0]]))
			$contactprefs[$row[0]] = array();
		if(!isset($contactprefs[$row[0]][$row[1]]))
			$contactprefs[$row[0]][$row[1]] = array();
		if(!isset($contactprefs[$row[0]][$row[1]][$row[2]]))
			$contactprefs[$row[0]][$row[1]][$row[2]] = array();
		$contactprefs[$row[0]][$row[1]][$row[2]] = $row[3];
	}
	return $contactprefs;
}

//fetches contact preferences and builds multidimesional array
function getContactPrefs($personid){
	$query = "Select type, sequence, jobtypeid, enabled from contactpref where personid = '" . $personid . "'";
	$res = Query($query);
	$contactprefs = array();
	while($row = DBGetRow($res)){
		if(!isset($contactprefs[$row[0]]))
			$contactprefs[$row[0]] = array();
		if(!isset($contactprefs[$row[0]][$row[1]]))
			$contactprefs[$row[0]][$row[1]] = array();
		if(!isset($contactprefs[$row[0]][$row[1]][$row[2]]))
			$contactprefs[$row[0]][$row[1]][$row[2]] = array();
		$contactprefs[$row[0]][$row[1]][$row[2]] = $row[3];
	}
	return $contactprefs;
}

//displays jobtype name and on hover, displays infoforparents
function jobtype_info($jobtype, $extrahtml = NULL) {
	$contents = nl2br($jobtype->infoforparents);
	if($contents == ""){
		$contents = "<br/>";
	}

	$hover = '<span ' . $extrahtml . '>';
	$hover .= '<div';
	$hover .= ' onmouseover="this.nextSibling.style.display = \'block\'; setIFrame(this.nextSibling);"';
	$hover .= ' onmouseout="this.nextSibling.style.display = \'none\'; setIFrame(null);"';
	$hover .= '>&nbsp;' . $jobtype->name . '&nbsp;</div><div class="hoverhelp">' . $contents . '</div></span>';

	return $hover;
}
?>