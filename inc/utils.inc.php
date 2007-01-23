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
	if (!fallbackUrl) {
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

function getChildObject($person, $type, $table) {
	$obj = DBFind($table, "from $table where personid = $person");
	return $obj ? $obj : new $type();
}


/*
	Function to find the next seuential access code in a phone system
	@param currentCode The current access code for which we are trying to find a replacement
	@param userid The id of the user for whom we are trying to find a unique value
	@param customerid The customer ID under which we are trying to find and access code
*/
function getNextAvailableAccessCode($currentCode, $userid, $customerid) {
	$result=1;
	while($result !=0) {
		$nextCode = rand(1000,999999);
		$result = QuickQuery("select count(*) from user where accesscode = '$nextCode' and id != '$userid' and customerid = '$customerid'
								AND enabled = '1'");
	}
	return $nextCode;
}
function getCustomerSystemSetting($name, $customerid, $defaultvalue=false) {
	static $settings = array();

	if (isset($settings[$name]))
		return $settings[$name];

	$value = QuickQuery("select value from setting where customerid = $customerid and name = '" . DBSafe($name) . "'");
	if($value === false) {
		$value = $defaultvalue;
	}
	return $settings[$name] = $value;
}

function getSystemSetting($name) {
	global $USER;
	return getCustomerSystemSetting($name, $USER->customerid);
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
	if(strpos($pass, $user)!==FALSE) {
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
	if(strlen($pass) < 5){
		return("Password must be atleast 5 characters long");
	}
	if(strlen($pass) < 6){
		$pass = $pass."a";
	}
	
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
				return("The password cannot contain spaces.");
			case "it does not contain enough DIFFERENT characters":
				return("The password needs more different characters.");
			case "it is too short":
				return("The password needs to be atleast 5 characters long");
			case "it's WAY too short":
				return("The password needs to be atleast 5 characters long");
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

?>