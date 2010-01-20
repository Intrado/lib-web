<?

//most functions require DBSafe first!

function exists($table, $name) {
	global $USER;
	$lowername = strtolower($name);
	return QuickQuery("select id from ? where userid = ? and lower(name) = ?", false, array($table, $USER->id, $lowername));
}

function userOwns ($type,$id) {
	global $USER;
	switch($type) {
		case "reportsubscription":
		case "list":
		case "job":
		case "smsjob":
		case "audiofile":
		case "person":
		case "surveyquestionnaire":
		case "voicereply":
		case "message":
		case "messagegroup":
			return QuickQuery("select count(*) from $type where userid=? and id=?", false, array($USER->id, $id));
		default:
			return false;
	}
}

function permitContent($id) {
	if (!isset($_SESSION['usercontentids']))
		$_SESSION['usercontentids'] = array();
	$_SESSION['usercontentids'][$id] = true;
}

function contentAllowed($id) {
	return isset($_SESSION['usercontentids']) && isset($_SESSION['usercontentids'][$id]);
}

function setIfOwnsOrNew ($id,$name, $type, $checkcustomer = false) {
	if ($id === "new") {
		$_SESSION[$name] = NULL;
	} else {
		$id = $id + 0;
		if ($checkcustomer) {
			$_SESSION[$name] = $id;
		} else if (userOwns($type,$id)) {
			$_SESSION[$name] = $id;
		}
	}
}

function setCurrentList ($newid) {
	setIfOwnsOrNew($newid, "listid", "list");
}

function getCurrentList() {
	return $_SESSION['listid'];
}

function setCurrentMessage ($newid) {
	setIfOwnsOrNew($newid, "messageid", "message");
}

function getCurrentMessage() {
	return $_SESSION['messageid'];
}

function setCurrentQuestionnaire ($newid) {
	setIfOwnsOrNew($newid, "surveyquestionnaireid", "surveyquestionnaire");
}

function getCurrentQuestionnaire() {
	return $_SESSION['surveyquestionnaireid'];
}

function setCurrentSurvey ($newid) {
	setIfOwnsOrNew($newid, "surveyid", "job");
}

function getCurrentSurvey() {
	return $_SESSION['surveyid'];
}

function setCurrentJob ($newid) {
	setIfOwnsOrNew($newid, "jobid", "job");

}

function getCurrentJob() {
	return $_SESSION['jobid'];
}

function setCurrentPerson ($newid) {
	setIfOwnsOrNew($newid, "personid", "person");
}

function getCurrentPerson() {
	return $_SESSION['personid'];
}


function setCurrentAudio ($newid) {
	setIfOwnsOrNew($newid, "audiofileid", "audiofile");
}

function getCurrentAudio() {
	return $_SESSION['audiofileid'];
}

function setCurrentMessageGroup ($newid) {
	return setIfOwnsOrNew($newid, "messagegroupid", "messagegroup");
}

function getCurrentMessageGroup() {
	return $_SESSION['messagegroupid'];
}

function setCurrentAccess ($newid) {
	return setIfOwnsOrNew($newid, "accessid", "access", true);
}

function getCurrentAccess() {
	return $_SESSION['accessid'];
}


function setCurrentImport ($newid) {
	return setIfOwnsOrNew($newid, "importid", "import", true);
}

function getCurrentImport() {
	return $_SESSION['accessid'];
}


function setCurrentUser ($newid) {
	return setIfOwnsOrNew($newid, "userid", "user", true);
}

function getCurrentUser() {
	return $_SESSION['userid'];
}

?>