<?

/**
 * appends user@customer and stack trace to the error message before calling error_log
 * @param string $msg
 */
function error_log_helper ($msg) {
	global $CUSTOMERURL, $USER;
	//the first frame is the original caller
	$trace = debug_backtrace();
	
	array_shift($trace); //remove this fn call
	
	$user = isset($USER->login) ? $USER->login : "-";
	$customer = isset($CUSTOMERURL) ? $CUSTOMERURL : "-";
	$debug = " --- " . $user  . "@" . $customer . " [";
	foreach ($trace as $frame) {
		$file = isset($frame['file']) ? str_replace("/usr/commsuite/www/", "", $frame['file']) : "-";
		$line = isset($frame['line']) ? $frame['line'] : "-";
		$fn = isset($frame['function']) ? $frame['function'] : "-";
		
		$debug .= "{" . $file . ":" . $line . "::" . $fn . "} ";
	}

	$debug .= "]";
	
	error_log($msg . $debug);
}

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
	if ($_SERVER['HTTP_REFERER']) {
		if (strpos($_SERVER['HTTP_REFERER'], "index.php") === false) {
			$url = $_SERVER['HTTP_REFERER'];
		} else {
			$url = "index.php";
		}
	} else {
		$url = $fallbackUrl;
	}
	
	header('Location: ' . $url );
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

// a customer system setting is one where organizationid is null
function getCustomerSystemSetting($name, $defaultvalue=false, $refresh=false, $custdb = false) {
	static $settings = array();

	if (isset($settings[$name]) && !$refresh)
		return $settings[$name];

	$value = QuickQuery("select value from setting where name = ? and organizationid is null", $custdb, array($name));

	if($value === false) {
		$value = $defaultvalue;
	}
	return $settings[$name] = $value;
}

function getSystemSetting($name, $defaultvalue=false) {
	return getCustomerSystemSetting($name, $defaultvalue);
}


// a customer system setting is one where organizationid is null
function setCustomerSystemSetting($name, $value, $custdb = false) {
	$old = getCustomerSystemSetting($name, false, true, $custdb);
	if($old === false && $value !== '' && $value !==NULL) {
		QuickUpdate("insert into setting (name, value) values (?, ?)", $custdb, array($name, $value));
	} else {
		if($value === '' || $value === NULL)
			QuickUpdate("delete from setting where name = ? and organizationid is null", $custdb, array($name));
		elseif($value != $old)
			QuickUpdate("update setting set value = ? where name = ? and organizationid is null", $custdb, array($value, $name));
	}
}

function setSystemSetting($name, $value) {
	setCustomerSystemSetting($name, $value);
}

function setOrganizationSetting($name, $value, $organizationId, $custdb = false) {
//TODO refactor with setCustomerSystemSetting to share code, unclear what if sql param is null for orgId

	// get existing setting value (if any)
	$old = QuickQuery("select value from setting where name = ? and organizationid = ?", $custdb, array($name, $organizationId));
	//
	if ($old === false && $value !== '' && $value !==NULL) {
		QuickUpdate("insert into setting (name, value, organizationid) values (?, ?, ?)", $custdb, array($name, $value, $organizationId));
	} else {
		if($value === '' || $value === NULL)
			QuickUpdate("delete from setting where name = ? and organizationid = ?", $custdb, array($name, $organizationId));
		elseif($value != $old)
			QuickUpdate("update setting set value = ? where name = ? and organizationid = ?", $custdb, array($value, $name, $organizationId));
	}
}

// get the default callerid based on user prefs, privs, customer settings, etc.
function getDefaultCallerID() {
	$hascallback = getSystemSetting('_hascallback', '0');
	$callbackdefault = getSystemSetting('callbackdefault', 'inboundnumber');
	if ($hascallback) {
		return getSystemSetting($callbackdefault);
	}
	global $USER;
	
	// additional phone parse paranoia
	return Phone::parse($USER->getSetting('callerid', getSystemSetting('callerid')));
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
	if($password == 'nopasswordchange'){
		return true;
	}
	if(preg_match("/[0-9]/", $password))
		$tally++;
	if(preg_match("/[a-zA-Z]/", $password))
		$tally++;
	if(preg_match("/[\!\@\#\$\%\^\&\*]/", $password))
		$tally++;

	if($tally >= 2)
		return true;

	return false;
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
	return getEmailRegExp(true);
}

function getEmailRegExp($domainonly = false) {
	#
    # RFC822 Email Parser
    #
    # By Cal Henderson <cal@iamcal.com>
    # This code is licensed under a Creative Commons Attribution-ShareAlike 2.5 License
    # http://creativecommons.org/licenses/by-sa/2.5/
    #
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

	$domain_ref = '[^\\x00-\\x2c\\x2e\\x2f\\x3a-\\x40\\x5b-\\x5e\\x60\\x7b-\\xff]+';

    $sub_domain = "(?:$domain_ref|$domain_literal)";

    $word = "(?:$atom|$quoted_string)";
	// original code allows a domain to only contain a single sub_domain.
	// changed to require 2 domain parts ex.  "example.com"  instead of just "example"
    $domain = "(?:$sub_domain(?:\\x2e$sub_domain)+)";
	
	if ($domainonly)
		return $domain;

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
	
	$ip_pattern = "/(^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$)/";
	$slaship_pattern = "/(^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3})\/([0-9]{1,2}$)/";
	$netmask_pattern = "/(^[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}) ([0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}\\.[0-9]{1,3}$)/";
	
	if ($host == $network)
		return true; //assumes at least one of the arguments is properly formatted, would return true if both are blank or something
	
	$srcaddr = ip2long($host);
	
	$regs = array();
	if (preg_match($ip_pattern,$network,$regs)) {
		$allowaddr = ip2long($regs[1]);
		$allowmask = ip2long("255.255.255.255");
	} else if (preg_match($slaship_pattern,$network,$regs)) {
		$allowaddr = ip2long($regs[1]);
		$allowmask = ip4CalcNetmaskFromBits($regs[2]);
	} else if (preg_match($netmask_pattern,$network,$regs)) {
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
	$hover = '<span ' . $extrahtml . '>';
	$mouseover = 'onmouseover="new Tip(this, \'' . addslashes(escapehtml(escapehtml($contents))) . '\', {style:\'protogrey\'});"';
	$hover .= '<div ' . $mouseover . ' style="color:#346799">&nbsp;' . escapehtml($jobtype->name) . '&nbsp;</div></span>';

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
	static $maxtypes = null;
	if ($maxtypes == null) {
		$maxtypes = array();
		$settings = QuickQueryList("select name, value from setting where organizationid is null and name in ('maxphones', 'maxemails', 'maxsms')", true);
		foreach ($settings as $name => $value) {
			switch ($name) {
				case 'maxphones':
					$maxtypes['phone'] = $value;
					break;
				case 'maxemails':
					$maxtypes['email'] = $value;
					break;
				case 'maxsms':
					$maxtypes['sms'] = $value;
					break;
			}
		}
	}
	// prevent division by zero, where the max is zero or unset
	if (!$maxtypes[$type])
		return "";

	// mod the sequence against the max value for this type (might be an appended person's contact data)
	$actualSequence = ($sequence % $maxtypes[$type]);
	if(isset($labels[$type][$actualSequence]) && !$refresh)
		return $labels[$type][$actualSequence];

	$labels[$type][$actualSequence] = QuickQuery("select label from destlabel where type = ? and sequence = ?", false, array($type, $actualSequence));
	return $labels[$type][$actualSequence];
}

function destination_label($type, $sequence){
	$label = fetch_labels($type, $sequence);
	if($label){
		$text = format_delivery_type($type). " ". ($sequence+1) . " (" . $label . ")";
		return $text;
	} else {
		return format_delivery_type($type). " ". ($sequence+1);
	}
}

//TODO: Create more generic functions for popup that would wrap around
//code or text. ex startHover, endHover
function destination_label_popup($type, $sequence, $f, $s, $itemname){
	$label = escapehtml(destination_label($type, $sequence));


	if (!isset($GLOBALS['TIPS']))
		$GLOBALS['TIPS'] = array();
	$tipid = "tip_" . count($GLOBALS['TIPS']);

	$GLOBALS['TIPS'][] = array($tipid,$label); //navbotom.inc will load these for us

	NewFormItem($f, $s, $itemname, "checkbox", 0, 1, 'id="'.$tipid.'"');
}

function getBrand(){
	return $_SESSION['productname'];
}

//Load display settings based on system or user preference
//user must be loaded
//customer url must also be loaded
function loadDisplaySettings(){
	global $USER, $CUSTOMERURL;

	// fetch default scheme
	$scheme = getCustomerData($CUSTOMERURL);
	if($scheme == false){
		$scheme = array(
					"_supportemail" => "support@schoolmessenger.com",
					"_supportphone" => "8009203897"
		);
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

function notice ($message) {
	if (!isset($_SESSION['confirmnotice']) || !is_array($_SESSION['confirmnotice']))
		$_SESSION['confirmnotice'] = array();
		
	$_SESSION['confirmnotice'][] = $message;
}

function repeatWithSeparator($str, $sep, $count) {
	return implode($sep, array_fill(0, $count, $str));
}

// Returns a url-encoded string containing each header's name and value as a parameter.
function makeUrlDataString($headers) {
	$data = array();
	
	foreach ($headers as $name => $value) {
		$data[] = "$name=" . urlencode($value);
	}
	
	return implode('&', $data);
}

function escape_csvfield ($value) {
	//TODO conditionally wrap with doublequotes only when needed
	return '"' . str_replace('"', '""',$value) . '"';
}

function array_to_csv($arr) {
	return implode(",",array_map("escape_csvfield",$arr));
}


function getJobTitle() {
	$job = "Broadcast";
	return $job;
}

function getJobsTitle() {
	$job = "Broadcasts";
	return $job;
}

function removeIllegalXmlChars($data) {
	// remove invalid ascii control characters (for xml encoding) from this data (bugs: 5659, 4873)
	return preg_replace('/[\x00-\x08\x0b\x0c\x0e-\x1f]/', '', $data);
}

/**
 * Converts the given .wav into .mp3
 * @param string $filename existing wav file
 * @return bool|string false if unable to convert, or if file is not found. Otherwise the raw mp3 data is returned
 */
function convertWavToMp3($filename) {
	$data = false;
	if (file_exists($filename)) {
		$mp3Filename = secure_tmpname("preview", ".mp3");
		$cmd = 'lame -S -b24 "' . $filename . '" "' . $mp3Filename . '"';
		$result = exec($cmd, $res1, $res2);
		if (!$res2) {
			$data = file_get_contents($mp3Filename);
		} else {
			error_log_helper("An error occurred trying to convert the file '" . $filename . "' to an mp3");
		}
		@unlink($mp3Filename);
	} else {
		error_log_helper("The file to convert does not exist: '" . $filename . "'");
	}
	return $data;
}

/**
 * Creates a simple modal dialog. NOTE: modal elements are defined in nav.inc.php. Thus, it requires nav.inc.php.
 * @param string $content content to display
 * @param string $heading header 
 * @return String script
 */
function modalErrorDialog($content, $heading = 'Error', $width='600px') {
	$html = <<<END
		<script language="JavaScript">
			(function($) {
				var modal = $('#defaultmodal');
				var header = modal.find(".modal-header h3");
				var body = modal.find(".modal-body");
				modal.modal();
				modal.height("auto");
				modal.width("$width");
				header.html("$heading");
				body.html("$content");
			}) (jQuery);
		</script>
END;

	return($html);
}

/**
 * Execute a command in another process and kill it if it runs for longer than expected.
 *
 * Pieced together from multiple sources:
 * http://php.net/manual/en/function.proc-open.php
 * http://stackoverflow.com/questions/5309900/php-process-execution-timeout
 * http://stackoverflow.com/questions/9419122/exec-with-timeout
 *
 * @param string $command the command and it's arguments. Be sure to quote filenames if they have spaces.
 * @param int $timeoutMs the maximum number of milliseconds this command can execute for before it is killed
 * @param int $expectedExitValue the expected exit value from the command. Defaults to zero
 * @return string the output from stdOut. if you want stdError as well, pipe it with your command
 * @throws Exception if the process cannot be started, or if an unexpected exit value is returned
 */
function executeWithTimeout($command, $timeoutMs, $expectedExitValue = 0) {
	// Start the process.
	$process = proc_open('exec ' . $command,
		array(
			0 => array('pipe', 'r'),  // stdIn
			1 => array('pipe', 'w'),  // stdOut
			2 => array('pipe', 'w')   // stdError
		), $pipes);

	if (!is_resource($process)) {
		throw new Exception("Unable to open process for command: '$command'");
	}

	// set stdOut and stdError to non-blocking
	stream_set_timeout($pipes[1], 0);
	stream_set_timeout($pipes[2], 0);

	// time to stop, in ms
	$stopTime = round(microtime(true) * 1000) + $timeoutMs;

	$stdOut = '';
	// attempt to read from stdOut till the stream returns false, or the timeout is reached
	while (($data = fread($pipes[1], 4096)) && ($stopTime > round(microtime(true) * 1000))) {
		$meta = stream_get_meta_data($pipes[1]);
		if (!$meta['timed_out'])
			$stdOut .= $data;
	}

	$stdOut .= stream_get_contents($pipes[1]);
	$stdError = stream_get_contents($pipes[2]);
	$exitValue = proc_close($process);

	if ($exitValue != $expectedExitValue) {
		throw new Exception("Unexpected exit value: $exitValue, stdError: '$stdError'");
	}

	return $stdOut;
}

?>
