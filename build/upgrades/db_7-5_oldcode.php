<?
//db_7-5_oldcode.php

/*
 * job object as it exists in 7.5/2
 * used to upgrade to rev3
 * basically a 7.1.x with messagegroupid and copyMessage()
 */
class Job_7_5_r2 extends DBMappedObject {
	var $messagegroupid;
	var $userid;
	var $scheduleid;
	var $jobtypeid;
	var $name;
	var $description;
	var $phonemessageid;
	var $emailmessageid;
	var $printmessageid;
	var $smsmessageid;
	var $questionnaireid;
	var $type;
	var $modifydate;
	var $createdate;
	var $startdate;
	var $enddate;
	var $starttime;
	var $endtime;
	var $finishdate;
	var $status;
	var $percentprocessed = 0;
	var $deleted = 0;

	var $cancelleduserid;

	function Job_7_5_r2 ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "job";
		$this->_fieldlist = array("messagegroupid","userid", "scheduleid", "jobtypeid", "name", "description",
				"phonemessageid", "emailmessageid", "printmessageid", "smsmessageid", "questionnaireid",
				"type", "modifydate", "createdate", "startdate", "enddate", "starttime", "endtime", "finishdate",
				"status", "percentprocessed", "deleted", "cancelleduserid");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function copyMessage($msgid) {
		// copy the message
		$newmsg = new Message($msgid);
		$newmsg->id = null;
		$newmsg->create();

		// copy the parts
		$parts = DBFindMany("MessagePart_7_5_r2", "from messagepart where messageid=$msgid");
		foreach ($parts as $part) {
			$newpart = new MessagePart_7_5_r2($part->id);
			$newpart->id = null;
			$newpart->messageid = $newmsg->id;
			$newpart->create();
		}

		// copy the attachments
		QuickUpdate("insert into messageattachment (messageid,contentid,filename,size,deleted) " .
			"select $newmsg->id, ma.contentid, ma.filename, ma.size, 1 as deleted " .
			"from messageattachment ma where ma.messageid=$msgid and not deleted");

		return $newmsg;
	}
}


class Message_7_5_r2 extends DBMappedObject {

	var $userid;
	var $name;
	var $description;
	var $data = ""; //for headers
	var $type;
	var $modifydate;
	var $lastused;
	var $deleted = 0;
	var $permanent = 0;


	function Message_7_5_r2 ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "message";
		$this->_fieldlist = array("userid", "name", "description", "type", "data", "deleted","modifydate", "lastused", "permanent");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}


class MessagePart_7_5_r2 extends DBMappedObject {

	var $messageid;
	var $type;
	var $audiofileid;
	var $txt;
	var $fieldnum;
	var $defaultvalue;
	var $voiceid;
	var $sequence;
	var $maxlen;
	var $imagecontentid;

	var $audiofile;

	function MessagePart_7_5_r2 ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "messagepart";
		$this->_fieldlist = array("messageid", "type", "audiofileid", "txt", "fieldnum", "defaultvalue", "voiceid", "sequence", "maxlen");

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

}


?>
