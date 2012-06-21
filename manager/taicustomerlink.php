<?
include_once("common.inc.php");
include_once("../inc/html.inc.php");

if (!$MANAGERUSER->authorized("logincustomer"))
	exit("Not Authorized");
	
////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	$custid = $_GET['id'] + 0;
}

$key = $SETTINGS['portalauth']['key'];
$secret = $SETTINGS['portalauth']['secret'];

// first we need a request token
$oauth_request_url = $SETTINGS['portalauth']['portalauth_url_prefix'] . "request?oauth_accessor_secret=" . $secret . "&oauth_consumer_key=" . $key . "&oauth_nonce=1&oauth_timestamp=".time()."&oauth_signature_method=PLAINTEXT&oauth_signature=TODO";
		
$http = array('method' => 'GET');
		
$ctx = stream_context_create(array('http' => $http));
$fp = @fopen($oauth_request_url, 'rb', false, $ctx);
		
$result = array();
if (!$fp) {
	$response = "error";
} else {
	$response = @stream_get_contents($fp);
}
//error_log("request token response ".$response);

// TODO should we generate asptoken and pass that in too? or can we just trust the app by key/secret and oauth request token.

// send request to force login
redirect($SETTINGS['portalauth']['portalauth_url_prefix'] . "forcedlogin?" . $response . "&customerid=" . $custid . "&appType=tai");
?>