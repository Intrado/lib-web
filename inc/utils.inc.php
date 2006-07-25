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
	$nextCode = $currentCode + 0;
	$result = 1;
	while ($result != 0) {
		$nextCode++;
		$result = QuickQuery("select count(*) from user where accesscode = '$nextCode' and id != '$userid' and customerid = '$customerid'");
	}

	return $nextCode;
}

function getSystemSetting($name) {
	global $USER;
	$name = DBSafe($name);
	return QuickQuery("select value from setting where customerid = $USER->customerid and name = '$name'");
}

?>