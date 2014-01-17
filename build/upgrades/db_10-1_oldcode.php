<?
class MessageAttachment extends DBMappedObject {

	var $messageid;
	var $contentid;
	var $filename;
	var $size;
	var $type;
	var $contentattachmentid;
	var $burstattachmentid;

	function MessageAttachment ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "messageattachment";
		$this->_fieldlist = array("messageid", "contentid", "filename", "size", "type", "contentattachmentid", "burstattachmentid");
		DBMappedObject::DBMappedObject($id);
	}
}

class BurstAttachment extends DBMappedObject {
	var $contentid;
	var $filename;
	var $size;

	function ContentAttachment ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "burstattachment";
		$this->_fieldlist = array("burstid", "filename");
		DBMappedObject::DBMappedObject($id);
	}
}

class ContentAttachment extends DBMappedObject {
	var $contentid;
	var $filename;
	var $size;

	function ContentAttachment ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "contentattachment";
		$this->_fieldlist = array("contentid", "filename", "size");
		DBMappedObject::DBMappedObject($id);
	}
}

// create the subscriber application database user
function createLimitedUser($limitedusername, $limitedpassword, $custdbname, $sharddb, $grantedhost = '%') {
	QuickUpdate("drop user '$limitedusername'@'$grantedhost'", $sharddb);
	Query("FLUSH PRIVILEGES", $sharddb);
	QuickUpdate("create user '$limitedusername'@'$grantedhost' identified by '$limitedpassword'", $sharddb);

	$tables = array();
	$tables['audiofile'] 	= "select";
	$tables['content'] 		= "select";
	$tables['contactpref'] 	= "select, insert, update, delete";
	$tables['email'] 		= "select, update";
	$tables['fieldmap'] 	= "select";
	$tables['groupdata'] 	= "select, insert, update, delete";
	$tables['job'] 			= "select";
	$tables['jobsetting'] 	= "select";
	$tables['jobtype'] 		= "select";
	$tables['notificationtype']	= "select";
	$tables['language']		= "select";
	$tables['message'] 		= "select";
	$tables['messageattachment'] = "select";
	$tables['contentattachment'] = "select";
	$tables['burstattachment'] = "select";
	$tables['messagegroup'] = "select";
	$tables['messagepart'] 	= "select";
	$tables['organization'] = "select";
	$tables['persondatavalues'] = "select";
	$tables['person'] 		= "select, update";
	$tables['personassociation'] = "select, insert, update, delete";
	$tables['phone'] 		= "select, update";
	$tables['reportperson'] = "select";
	$tables['setting'] 		= "select";
	$tables['sms'] 			= "select, update";
	$tables['subscriber'] 	= "select, update";
	$tables['subscriberpending'] = "select, delete";
	$tables['ttsvoice'] 	= "select";
	$tables['user'] 		= "select";

	foreach ($tables as $tablename => $privs) {
		if (QuickUpdate("grant ".$privs." on $custdbname . ".$tablename." to '$limitedusername'@'$grantedhost'", $sharddb) === false)
			dieWithError("Failed to grant ".$tablename." on ".$custdbname, $sharddb);

	}
	Query("FLUSH PRIVILEGES", $sharddb);
}

function genpassword($digits = 15) {
	$passwd = "";
	$chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
	while ($digits--) {
		$passwd .= $chars[mt_rand(0,strlen($chars)-1)];
	}
	return $passwd;
}


function dieWithError($error, $pdo = false) {
	$dberr = null;
	if ($pdo) {
		$e = $pdo->errorInfo();
		$dberr = $e[2];
	}
	die ($error . " : " . $dberr);
}
?>