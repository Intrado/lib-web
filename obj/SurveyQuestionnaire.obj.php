<?

class SurveyQuestionnaire extends DBMappedObject {

	var $userid;
	var $name;
	var $description;
	var $hasphone = 1;
	var $hasweb = 0;
	var $dorandomizeorder = 0;
	var $machinemessageid;
	var $emailmessageid;
	var $intromessageid;
	var $exitmessageid;
	var $webpagetitle;
	var $webexitmessage;
	var $deleted = 0;
	var $usehtml = 0;
	var $leavemessage = 0;

	function SurveyQuestionnaire ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "surveyquestionnaire";
		$this->_fieldlist = array("userid", "name", "description", "hasphone", "hasweb",
			"dorandomizeorder", "machinemessageid", "emailmessageid","intromessageid",
			"exitmessageid", "webpagetitle", "webexitmessage", "usehtml","leavemessage", "deleted");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>