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

function isSubscribed ($type,$id) {
	global $USER;
	switch($type) {
		case "message":
			// see if the current user is subscribed to the requested message group id
			return QuickQuery("
				select 1
				from message m
					inner join publish p on
						(m.messagegroupid = p.messagegroupid)
				where p.type = 'messagegroup'
					and p.userid = ?
					and p.action = 'subscribe'
					and m.id = ?", false, array($USER->id, $id));
		case "messagegroup":
			return QuickQuery("select 1 from publish where userid = ? and action = 'subscribe' and type = 'messagegroup' and messagegroupid=?", false, array($USER->id, $id));
		default:
			return false;
	}
}

function isPublished ($type,$id) {
	global $USER;
	switch($type) {
		case "message":
			// if the user is not autorized to subscribe to message groups. return false
			if (!$USER->authorize('subscribemessagegroup'))
				return false;
			// look up the requested message id and see if it is associated with a published message group
			return QuickQuery("
				select 1
				from message m
					inner join publish p on
						(m.messagegroupid = p.messagegroupid)
				where p.type = 'messagegroup'
					and p.action = 'publish'
					and m.id = ?", false, array($id));
		case "messagegroup":
			// if the user is not autorized to subscribe to message groups. return false
			if (!$USER->authorize('subscribemessagegroup'))
				return false;
			// look up the requested message group id and see if it is published
			return QuickQuery("select 1 from publish where action = 'publish' and type = 'messagegroup' and messagegroupid=?", false, array($id));
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