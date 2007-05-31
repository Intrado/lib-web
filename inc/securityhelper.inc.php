<?

//most functions require DBSafe first!

function exists($table, $name) {
	global $USER;
	$name = DBSafe(strtolower($name));
	return QuickQuery("select id from $table where userid = $USER->id and lower(name) = '$name'");
}

function userOwns ($type,$id) {
	global $USER;
	switch($type) {
		case "list":
		case "job":
		case "smsjob":
		case "audiofile":
		case "person":
		case "surveyquestionnaire":
		case "voicereply":
			return QuickQuery("select count(*) from $type where userid='$USER->id' and id='$id'");
		case "message":
			return QuickQuery("select count(*) from $type where userid='$USER->id' and id='$id' and deleted=0");
		default:
			return false;
	}
}
//jjl
function customerOwns($type, $id) {
	global $USER;
	switch ($type) {

		case "blockednumber":
		case "fieldmap":
		case "import":
		case "jobtype":
		case "language":
		case "persondatavalues":
		case "setting":	
		case "access":
			return QuickQuery("select count(*) from `$type` where id = '$id'");
		case "person":
		case "user":
			return QuickQuery("select count(*) from `$type` where id = '$id' and deleted=0");
		case "job":
			return QuickQuery("select count(*) from job, user where job.id = '$id' and user.id = job.userid");
		case "smsjob":
			return QuickQuery("select count(*) from smsjob, user where smsjob.id = '$id' and user.customerid = $USER->customerid and user.id = smsjob.userid");
		default:
			return false;
	}
}

/*
	Function to see if a job is owned by a user with the same customerid as the job owner.
*/
function customerOwnsJob($jobid) {
	global $USER;
	return QuickQuery("select count(*) from job, user where job.id = $jobid and user.id = job.userid");
}

function setIfOwnsOrNew ($id,$name, $type, $checkcustomer = false) {
	$newid = DBSafe($id);
	if ($id == "new") {
		$_SESSION[$name] = NULL;
	} else {
		if ($checkcustomer && customerOwns($type,$id)) {
			$_SESSION[$name] = $newid;
		} else if (userOwns($type,$newid)) {
			$_SESSION[$name] = $newid;
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