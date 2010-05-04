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
		$newmsg = new Message_7_5_r2($msgid);
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

class MessageGroup_7_5_r2 extends DBMappedObject {
	var $userid;
	var $name;
	var $description;
	var $modified;
	var $lastused;
	var $permanent = 0;
	var $deleted = 0;

	function MessageGroup_7_5_r2 ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "messagegroup";
		$this->_fieldlist = array(
			"userid",
			"name",
			"description",
			"modified",
			"lastused",
			"permanent",
			"deleted"
		);

		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

class FieldMap_7_5_r14 extends DBMappedObject {

	var $fieldnum;
	var $name;
	var $options;

	var $optionsarray = false;

	function FieldMap_7_5_r14 ($id = NULL) {
		$this->_tablename = "fieldmap";
		$this->_fieldlist = array("fieldnum", "name","options");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

class Rule_7_5_r14 extends DBMappedObject {
	var $logical;
	var $fieldnum;
	var $op;
	var $val;

	function Rule_7_5_r14 ($id = NULL) {
		$this->_tablename = "rule";
		$this->_fieldlist = array("logical", "fieldnum", "op", "val");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

class ReportInstance_7_5_r14 extends DBMappedObject {
	//table vars
	
	var $parameters;
	var $instancehash;

	//related parent object
	var $report;

	function ReportInstance_7_5_r14 ($id = NULL) {
		$this->_allownulls = false;
		$this->_tablename = "reportinstance";
		$this->_fieldlist = array("parameters", "instancehash" );
		DBMappedObject::DBMappedObject($id);
	}
	
	function setParameterString ($paramstring) {
		$params = explode("&", $paramstring);
		sort($params);
		$this->parameters = implode("&", $params);	
		//$this->rehash();
	}
	
	//takes an input string formatted like http get query
	function setParameters ($paramarray, $keepnumericindex = false) {
		//resort the params by name, value
		//explaination if we have name[]=2&abc=123&name[]=1 then
		//it will break into "name=value2" "abc=123" "name=value1" 
		//then sort to "abc=123" "name[]=1" "name[]=2"
		//this way we can reorder randomly ordered parameters so that
		//the hash will check out ok.
		if(isset($paramarray['rules'])){
			$paramarray['rules'] = $this->ruleArraytoString($paramarray['rules']);	
		}
		
		if(isset($paramarray['sectionids']) && is_array($paramarray['sectionids']) && count($paramarray['sectionids']) > 0) {
			$paramarray['sectionids'] = implode(';', $paramarray['sectionids']);
			unset($paramarray['organizationids']);
		} else {
			unset($paramarray['sectionids']);
		}
		
		if(isset($paramarray['organizationids']) && is_array($paramarray['organizationids']) && count($paramarray['organizationids']) > 0) {
			$paramarray['organizationids'] = implode(';', $paramarray['organizationids']);
		} else {
			unset($paramarray['organizationids']);
		}
		
		$paramstring = http_build_query($paramarray, false, "&");
		$this->setParameterString($paramstring);
			
	}
	
	function setString ($paramstring) {
		$params = explode("&", $paramstring);
		sort($params);
		return implode("&", $params);
		//$this->rehash();
	}
		
	function getParameters () {
		$paramarray = array();
		$paramarray = sane_parsestr($this->parameters);
		if(isset($paramarray['rules']))
			$paramarray['rules'] = $this->ruleStringtoArray($paramarray['rules']);
			
		if(isset($paramarray['sectionids']))
			$paramarray['sectionids'] = explode(';', $paramarray['sectionids']);
			
		if(isset($paramarray['organizationids']))
			$paramarray['organizationids'] = explode(';', $paramarray['organizationids']);
			
		return $paramarray;
	}
	
	function setReport ($reportobj) {
		$this->report = $reportobj;
		$this->reportid = $reportobj->id;
	}
	
	function setReportID ($id) {
		$this->reportid = $id;
		$this->report = new Report($id);
	}
	
	
	// takes the rules string and converts it to an array of rules
	function ruleStringtoArray($rules = ""){
		$finalarray = array();
		if($rules != ""){
			$rulearray = explode("||", $rules);
			foreach($rulearray as $rule){
				if($rule != ""){
					$rule = explode(";", $rule);
					$newrule = new Rule_7_5_r14();
					$newrule->logical = $rule[0];
					$newrule->op = $rule[1];
					$newrule->fieldnum = $rule[2];
					$newrule->val = $rule[3];
					$finalarray[] = $newrule;
					$newrule->id = array_search($newrule, $finalarray);
					$finalarray[$newrule->id] = $newrule;
				}
			}
		}
		return $finalarray;
	}
	
	//converts an array of rule objects to a big string
	function ruleArraytoString($rules = array()){
		$rulestringarray = array();
		foreach($rules as $rule){
			$rulestringarray[] = implode(";", array($rule->logical, $rule->op, $rule->fieldnum, $rule->val));
		}
		return implode("||", $rulestringarray);
	
	}
	
}
?>
