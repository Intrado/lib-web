<?
//Used only in portal
include_once("common.inc.php");

include_once('../inc/securityhelper.inc.php');
include_once('../inc/content.inc.php');
include_once('../inc/appserver.inc.php');
include_once("../obj/Content.obj.php");
include_once("../obj/Message.obj.php");
include_once("../obj/MessagePart.obj.php");
include_once("../obj/AudioFile.obj.php");
include_once("../obj/Voice.obj.php");
include_once("../obj/FieldMap.obj.php");

// load the thrift api requirements.
$thriftdir = '../Thrift';
require_once("{$thriftdir}/Base/TBase.php");
require_once("{$thriftdir}/Protocol/TProtocol.php");
require_once("{$thriftdir}/Protocol/TBinaryProtocol.php");
require_once("{$thriftdir}/Protocol/TBinaryProtocolAccelerated.php");
require_once("{$thriftdir}/Transport/TTransport.php");
require_once("{$thriftdir}/Transport/TSocket.php");
require_once("{$thriftdir}/Transport/TBufferedTransport.php");
require_once("{$thriftdir}/Transport/TFramedTransport.php");
require_once("{$thriftdir}/Exception/TException.php");
require_once("{$thriftdir}/Exception/TProtocolException.php");
require_once("{$thriftdir}/Exception/TApplicationException.php");
require_once("{$thriftdir}/Type/TType.php");
require_once("{$thriftdir}/Type/TMessageType.php");
require_once("{$thriftdir}/StringFunc/TStringFunc.php");
require_once("{$thriftdir}/Factory/TStringFuncFactory.php");
require_once("{$thriftdir}/StringFunc/Core.php");
require_once("{$thriftdir}/packages/commsuite/Types.php");
require_once("{$thriftdir}/packages/commsuite/CommSuite.php");

session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

if (isset($_GET['id']) && isset($_GET['customerid'])) {
	$id = $_GET['id']+0;
	$customerid = $_GET['customerid']+0;
	$custinfo = QuickQueryRow("select s.dbhost, c.dbusername, c.dbpassword, c.urlcomponent, c.enabled from customer c inner join shard s on (c.shardid = s.id) where c.id = ?", false, false, array($customerid));
	$_dbcon = DBConnect($custinfo[0], $custinfo[1], $custinfo[2], "c_" . $customerid);
	if (!$_dbcon) {
		exit("Connection failed for customer: $custinfo[0], db: c_" . $customerid);
	}
	$fields = array();
	for ($i=1; $i <= 20; $i++) {
		$fieldnum = sprintf("f%02d", $i);
		if(isset($_REQUEST[$fieldnum]))
			$fields[$fieldnum] = $_REQUEST[$fieldnum];
	}
	
	$parts = DBFindMany('MessagePart', 'from messagepart where messageid=? order by sequence', false, array($id));
	$renderedparts = Message::renderPhoneParts($parts, $fields);
	$voices = DBFindMany("Voice","from ttsvoice");
	
	// -- get the wav files --
	$wavfiles = array();
	
	foreach ($renderedparts as $part) {
		if ($part[0] == "a") {
			list($contenttype,$data) = contentGetForCustomerId($customerid, $part[1]);
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
			$cmd = 'sox ' . $messageparts . ' "' . $outname . '"';
			$result = exec($cmd, $res1, $res2);
	
	foreach ($wavfiles as $file)
	@unlink($file);
	
	if (!$res2 && file_exists($outname)) {
		$data = file_get_contents ($outname); // readfile seems to cause problems
		header("HTTP/1.0 200 OK");
		header("Content-Type: audio/wav");
		if (isset($_GET['download']))
			header("Content-disposition: attachment; filename=message.wav");
		header('Pragma: private');
		header('Cache-control: private, must-revalidate');
		header("Content-Length: " . strlen($data));
		header("Connection: close");
		echo $data;
	} else {
		echo _L("An error occurred trying to generate the preview file. Please try again.");
	}
	@unlink($outname);
}
?>