<?
//same algorithm used in authserver
function calculatePasswordHash($rawPassword,$salt) {
	$hash_iterations = 5000;
	
	//we assume the password and salt are in utf-8 format
	$ctx = hash_init('sha256');
	
	hash_update($ctx, $rawPassword);
	hash_update($ctx, $salt);
	
	$hash = hash_final($ctx, true);
	
	//perform any additional iterations
	for ($i = 1; $i < $hash_iterations; $i++) {
		$ctx = hash_init('sha256');
		hash_update($ctx, $hash);
		$hash = hash_final($ctx, true);
	}
	
	return base64_encode($hash);	
}


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
		case "monitor";
			return QuickQuery("select count(*) from $type where userid=? and id=?", false, array($USER->id, $id));
		default:
			return false;
	}
}

// can the user "see" this object? Used when previewing items
// TODO: add more useful methods beyond just message and messagegroup
function userCanSee ($type,$id) {
	global $USER;
	
	//basically a superset of userOwns functionality
	if (userOwns($type,id))
		return true;
	
	switch($type) {
		case "message":
			// vieable if:
			//   it's message group matches below
			$messagegroupid = QuickQuery("select messagegroupid from message where id=?", false, array($id));
		case "messagegroup":
			if (!isset($messagegroupid))
				$messagegroupid = $id;
			// viewable if:
			// User owns it... obviously they can see it
			if (userOwns("messagegroup", $messagegroupid))
				return true;
			// User could subscribe to it...
			if (userCanSubscribe("messagegroup", $messagegroupid))
				return true;
			// This message group has been used by a job this user owns...
			$query = "select 1
				from  messagegroup mg
				inner join job j on (j.messagegroupid = mg.id)
				where mg.id = ?
				and j.userid = ?";
			return QuickQuery($query, false, array($messagegroupid, $USER->id));
		case "job":
			// TODO add restrictions
			return true;
			
		default:
			return false;
	}
}
function isSubscribed ($type,$id) {
	global $USER;
	switch($type) {
		case "messagegroup":
			return QuickQuery("select 1 from publish where userid = ? and action = 'subscribe' and type = 'messagegroup' and messagegroupid=?", false, array($USER->id, $id));
		case "list":
			return QuickQuery("select 1 from publish where userid = ? and action = 'subscribe' and type = 'list' and listid=?", false, array($USER->id, $id));
	}
	return false;
}

// returns true if the object is published
function isPublished ($type,$id) {
	switch($type) {
		case "messagegroup":
			return QuickQuery("select 1 from publish where action = 'publish' and type = 'messagegroup' and messagegroupid=?", false, array($id));
		case "list":
			return QuickQuery("select 1 from publish where action = 'publish' and type = 'list' and listid=?", false, array($id));
	}
	return false;
}

// returns true if the current user can subscribe to the requested type
// if optional id is used, it checks if the user can subscribe to the type and id requested
function userCanSubscribe($type, $id = false) {
	global $USER;
	global $ACCESS;
	$cansubscribe = in_array($type, explode("|", $ACCESS->getValue('subscribe')));
	if ($id !== false && $cansubscribe) {
		// check that this id is published
		if(!isPublished($type, $id))
			return false;
		
		// check if the user has restrictions
		$userrestrictions = QuickQuery("select 1 from userassociation where userid = ? and type in ('organization', 'section') limit 1", false, array($USER->id));
		
		// if they are restricted
		if ($userrestrictions) {
			$authorgs = Organization::getAuthorizedOrgKeys();
			$args = array($id);
			foreach ($authorgs as $orgid => $orgkey)
				$args[] = $orgid;
			
			// get organization restriction sql
			$orgrestrictionsql = "";
			if ($authorgs)
				$orgrestrictionsql = "or organizationid in (". DBParamListString(count($authorgs)) . ")";
			
			$publishedtoorg = false;
			switch ($type) {
				case "messagegroup":
					$publishedtoorg = QuickQuery("
						select 1
						from publish
						where action = 'publish' and type = 'messagegroup' and messagegroupid = ? and (organizationid is null $orgrestrictionsql) limit 1",
						false, $args);
					break;
				case "list";
					$publishedtoorg = QuickQuery("
						select 1
						from publish
						where action = 'publish' and type = 'list' and listid = ? and (organizationid is null $orgrestrictionsql) limit 1",
						false, $args);
						break;
					break;
			}
			return $publishedtoorg;
		}
	}
	return $cansubscribe;
}

// returns true if the current user can publish the requested type
function userCanPublish($type) {
	global $ACCESS;
	return in_array($type, explode("|", $ACCESS->getValue('publish')));
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

// get currently authorized facebook pages
function getFbAuthorizedPages() {
	$dbdata = QuickQueryMultiRow("select value from setting where name like 'fbauthorizedpage%'");
	$pages = array();
	foreach ($dbdata as $row)
		$pages[] = $row[0];
	return $pages;
}

// store facebook authorized pages
function setFbAuthorizedPages($pages) {
	// clear existing autorized pages
	QuickUpdate("delete from setting where name like 'fbauthorizedpage%'");
	
	// batch insert the new pages
	if ($pages) {
		$args = array();
		$count = 0;
		foreach ($pages as $page) {
			$args[] = "fbauthorizedpage" . $count++;
			$args[] = $page;
		}
		
		$query = "insert into setting (name,value) values " . repeatWithSeparator("(?,?)", ",", count($pages));
		QuickUpdate($query, false, $args);
	}
}

?>