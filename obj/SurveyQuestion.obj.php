<?

class SurveyQuestion extends DBMappedObject {

	var $questionnaireid;
	var $questionnumber;
	var $webmessage;
	var $phonemessageid;
	var $validresponse;

	function SurveyQuestion ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "surveyquestion";
		$this->_fieldlist = array("questionnaireid", "questionnumber", "webmessage", "phonemessageid", "validresponse");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}
}

?>