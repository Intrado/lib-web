<?
//quick utf8 aware replacement for htmlentities
function escapehtml($var) {
	return htmlentities($var, ENT_COMPAT, 'UTF-8') ;
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

function firstset () {
	foreach(func_get_args() as $val)
		if(isset($val))
			return $val;
	return null;
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
		$query = "select count(*) from user where accesscode = ? and id != ?
								AND enabled = '1'";
		$result = QuickQuery($query, false, array($nextCode, $userid));
	}
	return $nextCode;
}

function getCustomerSystemSetting($name, $defaultvalue=false, $refresh=false, $custdb = false) {
	static $settings = array();

	if (isset($settings[$name]) && !$refresh)
		return $settings[$name];

	$value = QuickQuery("select value from setting where name = ?", $custdb, array($name));

	if($value === false) {
		$value = $defaultvalue;
	}
	return $settings[$name] = $value;
}

function getSystemSetting($name, $defaultvalue=false) {
	return getCustomerSystemSetting($name, $defaultvalue);
}


function setCustomerSystemSetting($name, $value, $custdb = false) {
	$old = getCustomerSystemSetting($name, false, true, $custdb);
	if($old === false && $value !== '' && $value !==NULL) {
		QuickUpdate("insert into setting (name, value) values (?, ?)", $custdb, array($name, $value));
	} else {
		if($value === '' || $value === NULL)
			QuickUpdate("delete from setting where name = ?", $custdb, array($name));
		elseif($value != $old)
			QuickUpdate("update setting set value = ? where name = ?", $custdb, array($value, $name));
	}
}

function setSystemSetting($name, $value) {
	setCustomerSystemSetting($name, $value);
}

// get the default callerid based on user prefs, privs, customer settings, etc.
function getDefaultCallerID() {
	$hascallback = getSystemSetting('_hascallback', '0');
	$callbackdefault = getSystemSetting('callbackdefault', 'inboundnumber');
	if ($hascallback) {
		return getSystemSetting($callbackdefault);
	}
	global $USER;
	return $USER->getSetting('callerid', getSystemSetting('callerid'));
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
	the same thing or similar.
	The password cannot be a substring or superstring of first, last or user.
*/
function validateNewPassword($user, $pass, $firstname, $lastname) {
	$user = strtolower($user);
	$pass = strtolower($pass);
	$firstname = strtolower($firstname);
	$lastname = strtolower($lastname);
	if(strlen($user)>=3 && (strpos($user, $pass)!==FALSE || strpos($pass,$user)!==FALSE)) {
		return("Username and password are too similar");
	}
	if(strlen($firstname)>=3 && (strpos($firstname,$pass)!==FALSE || strpos($pass,$firstname)!==FALSE)) {
		return("Firstname and password are too similar");
	}
	if(strlen($lastname) >=3 && (strpos($lastname,$pass)!==FALSE || strpos($pass,$lastname)!==FALSE)) {
		return("Lastname and password are too similar");
	}
	return false;
}

//Checks the password for required chars
//returns false if fails the test
function passwordcheck($password){
	$tally = 0;
	if(ereg("^0*$", $password)){
		return true;
	}
	if(ereg("[0-9]", $password))
		$tally++;
	if(ereg("[a-zA-Z]", $password))
		$tally++;
	if(ereg("[\!\@\#\$\%\^\&\*]", $password))
		$tally++;

	if($tally >= 2)
		return true;

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


function getDomainRegExp() {
    ##################################################################################
	# Beginning of Creative Commons Email Parser Code
	##################################################################################

	$qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';

	$dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';

	$atom = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';

    $quoted_pair = '\\x5c[\\x00-\\x7f]';

    $domain_literal = "\\x5b(?:$dtext|$quoted_pair)*\\x5d";

    $quoted_string = "\\x22(?:$qtext|$quoted_pair)*\\x22";

    $domain_ref = $atom;

    $sub_domain = "(?:$domain_ref|$domain_literal)";

    $word = "(?:$atom|$quoted_string)";
	// original code allows a domain to only contain a single sub_domain.
	// changed to require 2 domain parts ex.  "example.com"  instead of just "example"
    $domain = "(?:$sub_domain(?:\\x2e$sub_domain)+)";

	return $domain;
}

function getEmailRegExp() {
	#
    # RFC822 Email Parser
    #
    # By Cal Henderson <cal@iamcal.com>
    # This code is licensed under a Creative Commons Attribution-ShareAlike 2.5 License
    # http://creativecommons.org/licenses/by-sa/2.5/
    #
    # $Revision: 1.85 $
    # http://www.iamcal.com/publish/articles/php/parsing_email/
    ##################################################################################

    ##################################################################################
	# Beginning of Creative Commons Email Parser Code
	##################################################################################

	$qtext = '[^\\x0d\\x22\\x5c\\x80-\\xff]';

	$dtext = '[^\\x0d\\x5b-\\x5d\\x80-\\xff]';

	$atom = '[^\\x00-\\x20\\x22\\x28\\x29\\x2c\\x2e\\x3a-\\x3c\\x3e\\x40\\x5b-\\x5d\\x7f-\\xff]+';

    $quoted_pair = '\\x5c[\\x00-\\x7f]';

    $domain_literal = "\\x5b(?:$dtext|$quoted_pair)*\\x5d";

    $quoted_string = "\\x22(?:$qtext|$quoted_pair)*\\x22";

    $domain_ref = $atom;

    $sub_domain = "(?:$domain_ref|$domain_literal)";

    $word = "(?:$atom|$quoted_string)";
	// original code allows a domain to only contain a single sub_domain.
	// changed to require 2 domain parts ex.  "example.com"  instead of just "example"
    $domain = "(?:$sub_domain(?:\\x2e$sub_domain)+)";

    $local_part = "$word(?:\\x2e$word)*";

    $addr_spec = "($local_part)(\\x40)($domain)";

	##################################################################################
	# End of Creative Commons Email Parser Code
	##################################################################################
	
	return $addr_spec;
}

// returns true if email is valid, false otherwise
function validEmail($email){
	$addr_spec = getEmailRegExp();
	if(!preg_match("!^$addr_spec$!", $email)){
		return false;
	}
	return true;
}

//checks emails seperated by ";" in a single string
function checkemails($emaillist) {

	if($emaillist=="")
		return false;
	$bademaillist=array();
	$emails = explode(";", $emaillist);
	foreach($emails as $email){
		if($email=="")
			continue;
		if (!validEmail(trim($email))) {
			$bademaillist[] = $email;
		}
	}
	return $bademaillist;
}

// validate the email is from one of these domains, optionally subdomain
function checkEmailDomain($email, $domains, $subdomain=false) {
	if ($domains == "") return true;
	$emaildomain = strtolower(substr($email, strpos($email, "@")+1));
	$domains = explode(";", strtolower($domains));
	foreach ($domains as $domain) {
		if (strcmp($emaildomain, $domain)) {
			if ($subdomain && substr( $emaildomain, strlen( $emaildomain ) - strlen( $domain ) ) == $domain)
				return true;
		} else
			return true;
	}
	return false;
}

// return true if valid, otherwise error string
// validate the customer system setting for 'emaildomain' list of domains separated by semi-colon
function validateDomainList($emaildomain) {
		if ($emaildomain == "") return true;
		
		$domainregexp = getDomainRegExp();

		$domains = explode(";", $emaildomain);
		foreach ($domains as $domain) {
			if (!preg_match("!^$domainregexp$!", $domain))
				$errmsg .= $domain . ";";
		}
		if (isset($errmsg)) {
			$errmsg = substr($errmsg, 0, strlen($errmsg)-1);
			return "Each domain must be separated by a semi-colon. Invalid domains found are: " . $errmsg;
		}
		return true;
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


function ip4CalcNetmaskFromBits ($bitcount) {
	$mask = 0;
	for (; $bitcount > 0; $bitcount--) {
		$mask >>= 1;
		$mask |= 0x80000000;
	}
	return $mask;
}

function ip4HostIsInNetwork($host,$network) {
	
	$ip_pattern = "^([0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3})$";
	$slaship_pattern = "^([0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3})/([0-9]{1,2})$";
	$netmask_pattern = "^([0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}) ([0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3})$";
	
	if ($host == $network)
		return true; //assumes at least one of the arguments is properly formatted, would return true if both are blank or something
	
	$srcaddr = ip2long($host);
	
	$regs = array();
	if (ereg($ip_pattern,$network,$regs)) {
		$allowaddr = ip2long($regs[1]);
		$allowmask = ip2long("255.255.255.255");
	} else if (ereg($slaship_pattern,$network,$regs)) {
		$allowaddr = ip2long($regs[1]);
		$allowmask = ip4CalcNetmaskFromBits($regs[2]);
	} else if (ereg($netmask_pattern,$network,$regs)) {
		$allowaddr = ip2long($regs[1]);
		$allowmask = ip2long($regs[2]);
	} else {
		return false;
	}
		
	if (($srcaddr & $allowmask) == $allowaddr)
		return true;
	else
		return false;
}

function writeWav ($data) {
	global $SETTINGS;
	$name = secure_tmpname("preview_parts",".wav");
	if (file_put_contents($name,$data))
		return $name;
}

// fetches jobtype preferences and builds multidimensional array
// builds by type, sequence, jobtypeid
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
// builds by type, sequence, jobtypeid
function getContactPrefs($personid){
	$query = "Select type, sequence, jobtypeid, enabled from contactpref where personid = ?";
	$res = Query($query, false, array($personid));
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

//displays jobtype name and on hover, displays info
function jobtype_info($jobtype, $extrahtml = NULL) {
	$contents = nl2br($jobtype->info);
	if($contents == ""){
		$contents = "<br/>";
	}
	//TODO replace this with prototip
	$hover = '<span ' . $extrahtml . '>';
	$hover .= '<div style="color:#346799"';
	$hover .= ' onmouseover="this.nextSibling.style.display = \'block\'; setIFrame(this.nextSibling);"';
	$hover .= ' onmouseout="this.nextSibling.style.display = \'none\'; setIFrame(null);"';
	$hover .= '>&nbsp;' . escapehtml($jobtype->name) . '&nbsp;</div><div class="hoverhelp">' . $contents . '</div></span>';

	return $hover;
}

function format_delivery_type($string){
	switch($string){
		case 'sms':
			return "SMS";
		default:
			return ucfirst($string);
	}
}

//Function to index an array of objects by
function resequence($objectarray, $field){
	$temparray = array();
	foreach($objectarray as $obj){
		$temparray[$obj->$field] = $obj;
	}
	return $temparray;
}

//fetch destination labels and store them into a static array
function fetch_labels($type, $sequence, $refresh=false){
	static $labels = array();
	if(isset($labels[$type][$sequence]) && !$refresh)
		return $labels[$type][$sequence];

	$labels[$type][$sequence] = QuickQuery("select label from destlabel where type = ? and sequence = ?", false, array($type, $sequence));
	return $labels[$type][$sequence];
}

function destination_label($type, $sequence){
	$label = fetch_labels($type, $sequence);
	if($label){
		$text = format_delivery_type($type). " ". ($sequence+1) . " (" . escapehtml($label) . ")";
		return $text;
	} else {
		return format_delivery_type($type). " ". ($sequence+1);
	}
}

//TODO: Create more generic functions for popup that would wrap around
//code or text. ex startHover, endHover
function destination_label_popup($type, $sequence, $f, $s, $itemname){
	$label = destination_label($type, $sequence);


	if (!isset($GLOBALS['TIPS']))
		$GLOBALS['TIPS'] = array();
	$tipid = "tip_" . count($GLOBALS['TIPS']);

	$GLOBALS['TIPS'][] = array($tipid,$label); //navbotom.inc will load these for us

	NewFormItem($f, $s, $itemname, "checkbox", 0, 1, 'id="'.$tipid.'"');
}

function getBrand(){
	return $_SESSION['productname'];
}

function getBrandTheme(){
	if (isset($_SESSION['colorscheme']))
		return $_SESSION['colorscheme']['_brandtheme'];
	else
		return "3dblue"; // hack for buttons on pages not yet logged in
}

function getBrandPrimary(){
	return $_SESSION['colorscheme']['_brandprimary'];
}

function getBrandTheme1(){
	return $_SESSION['colorscheme']['_brandtheme1'];
}

function getBrandTheme2(){
	return $_SESSION['colorscheme']['_brandtheme2'];
}


//Load display settings based on system or user preference
//user must be loaded
//customer url must also be loaded
function loadDisplaySettings(){
	global $USER, $CUSTOMERURL;

	// fetch default scheme
	$scheme = getCustomerData($CUSTOMERURL);
	if($scheme == false){
		$scheme = array("_brandtheme" => "3dblue",
						"_supportemail" => "support@schoolmessenger.com",
						"_supportphone" => "8009203897",
						"colors" => array("_brandprimary" => "26477D"));
	}
	$userprefs = array();
	$userprefs['_brandprimary'] = QuickQuery("select value from usersetting where userid=? and name = '_brandprimary'", false, array($USER->id));
	$userprefs['_brandtheme1'] = QuickQuery("select value from usersetting where userid=? and name = '_brandtheme1'", false, array($USER->id));
	$userprefs['_brandtheme2'] = QuickQuery("select value from usersetting where userid=? and name = '_brandtheme2'", false, array($USER->id));
	$userprefs['_brandratio'] = QuickQuery("select value from usersetting where userid=? and name = '_brandratio'", false, array($USER->id));
	$userprefs['_brandtheme'] = QuickQuery("select value from usersetting where userid=? and name = '_brandtheme'", false, array($USER->id));

	if($userprefs['_brandprimary']){
		$_SESSION['colorscheme'] = $userprefs;
	} else {
		$_SESSION['colorscheme'] = array("_brandtheme" => $scheme['_brandtheme'],
									"_brandprimary" => $scheme['colors']['_brandprimary'],
									"_brandtheme1" => $scheme['colors']['_brandtheme1'],
									"_brandtheme2" => $scheme['colors']['_brandtheme2'],
									"_brandratio" => $scheme['colors']['_brandratio']);
	}

	$_SESSION['productname'] = isset($scheme['productname']) ? $scheme['productname'] : "" ;
	$_SESSION['_supportphone'] = $scheme['_supportphone'];
	$_SESSION['_supportemail'] = $scheme['_supportemail'];
	
	// set locale
	$_SESSION['_locale'] = $USER->getSetting('_locale', getSystemSetting('_locale'));
}


function SmartTruncate ($txt, $max) {
	if (strlen($txt) > $max)
		return substr($txt,0,$max-3) . "...";
	else
		return $txt;
}

?>
