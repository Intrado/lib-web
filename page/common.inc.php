<?
////////////////////////////////////////////////////////////////////////////////
// Defaults And Settings
////////////////////////////////////////////////////////////////////////////////

setlocale(LC_ALL, 'en_US.UTF-8');
mb_internal_encoding('UTF-8');

$SETTINGS = parse_ini_file("pagesettings.ini.php",true);

apache_note("CS_APP","page"); //for logging


////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////

require_once("../inc/utils.inc.php");
require_once("../inc/table.inc.php");

require_once('../thrift/Thrift.php');
require_once $GLOBALS['THRIFT_ROOT'].'/protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/transport/TFramedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/packages/pagelink/PageLink.php';

////////////////////////////////////////////////////////////////////////////////
// Init
////////////////////////////////////////////////////////////////////////////////


class PageLinkService {
	var $transport;
	var $protocol;
	function PageLinkService() {
		global $SETTINGS;
		
		try {
			$appserverhost = explode(":",$SETTINGS['appserver_pagelink']['host']);
			$appserverPageLinkSocket = new TSocket($appserverhost[0], $appserverhost[1]);
			//$appserverPageLinkSocket->setDebug(true);
			$timeout = isset($SETTINGS['appserver_pagelink']['timeout']) ? $SETTINGS['appserver_pagelink']['timeout'] : 5500;
			$appserverPageLinkSocket->setRecvTimeout($timeout);
			$this->transport = new TFramedTransport($appserverPageLinkSocket);
			$this->protocol = new TBinaryProtocolAccelerated($this->transport);
		
		} catch (TException $tx) {
			// a general thrift exception, like no such server
			error_log("Exception Connection to AppServer PageLink service (" . $tx->getMessage() . ")");
			do503();
		}
	}
}

//connect to appserver and init page link service
$PLS = new PageLinkService();

//get the requesturl, check for base64 characters, or some other path
//codes have only [a-zA-Z0-9] and '-' and '_'
//other urls will have dots, slashes, etc

$CODE = isset($_GET['code']) ? $_GET['code'] : $_SERVER['REQUEST_URI'];
if (strrpos($CODE,"/") !== false)
	$CODE = substr($CODE,1 + strrpos($CODE, "/"));
	
//sanity check, don't bother passing tons of unknown data to appserver
if (strlen($CODE) > 10 || !preg_match("/^[-_a-zA-Z0-9]+$/", $CODE))
	do404();

apache_note("CS_USER", urlencode($CODE));

////////////////////////////////////////////////////////////////////////////////
// Helper functions (used on most every page)
////////////////////////////////////////////////////////////////////////////////

/**
 * Terminates execution with a HTTP 404 Not Found. Called when the resource (or code) doesn't exist.
 */
function do404() {
	header("HTTP/1.1 404 Not Found");
?>
<html><head>
<title>404 Not Found</title>
</head><body>
<h1>Not Found</h1>
<p>The requested URL was not found on this server.</p>
</body></html>
<?
	exit();
}

/**
 * Terminates execution with a HTTP 503 Service Unavailable. Called when there is a server-side problem, and execution cannot continue.
 */
function do503() {
	header("HTTP/1.1 503 Service Unavailable");
?>
<html><head>
<title>503 Service Unavailable</title>
</head><body>
<h1>Service Unavailable</h1>
<p>The service is currently unavailable.</p>
</body></html>
<?
	exit();
}


/**
 * Calls the PageLink service for the given method, prepending $CODE.
 * Can terminate early, and set headers. Gives 404 if code isn't found, and 503 if thrift
 * service is unavail.
 * @param unknown_type $method the method to use, ie "postPageGetForCode"
 * @param unknown_type $args extra args, NOTE that $CODE is prepended
 */
function reliablePageLinkCall ($method, $args = array()) {
	global $CODE, $PLS;
	$res = false;
	
	$newargs = $args;
	
	array_unshift($newargs, $CODE);
	
	//try to get the data from appserver up to 3 times, unless the code isn't found
	$attempts = 3;
	do {
		try {
			$client = new PageLinkClient($PLS->protocol);
			
			// Open up the connection
			$PLS->transport->open();
			
			$res = call_user_func_array(array($client, $method),$newargs);

			$PLS->transport->close();
			break;
		} catch (pagelink_NotFoundException $nfe) {
			error_log("WARN: code not found:" . urlencode($CODE));
			do404(); //terminates early
		} catch (TException $tx) {
			error_log("ERROR: TException trying to call " . $method . " for code:" . urlencode($CODE) . " msg:" . $tx->getMessage());
		}
	} while (--$attempts > 0);
	
	if ($res === false)
		do503();
	
	return $res;
}


