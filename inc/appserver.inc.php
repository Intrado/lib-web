<?
//////////////////////////////////////////////////////
// this file includes all CommSuite AppServer methods
//////////////////////////////////////////////////////


// Returns TemplateDTO filled with the reportperson values of the given job.
//
// Finds the job's messagegroup and priority, 
//		appends all message parts for the message of the given language,
//		merges message body into notification template,
//		fills all customer variables, fills all person variables,
//		returns both plain and html email bodies.
// Used by Contact Manager and Subscriber message viewer
function renderEmailTemplateForJobLanguagePerson($jobid, $languagecode, $personid) {
	global $appserverCommsuiteSocket;
	global $appserverCommsuiteTransport;
	global $appserverCommsuiteProtocol;
	
//	error_log("renderEmailTemplateForJobLanguagePerson jobid=".$jobid." languagecode=".$languagecode." personid=".$personid);
	
	$attempts = 0;
	while (true) {
		try {
			$client = new CommSuiteClient($appserverCommsuiteProtocol);
		
			// Open up the connection
			$appserverCommsuiteTransport->open();
			try {
				$result = $client->renderEmailTemplateForJobLanguagePerson(session_id(), $jobid, $languagecode, $personid);
			} catch (commsuite_CommSuite_MessageRendererException $e) {
				error_log("Unable to render jobid=".$jobid." languagecode=".$languagecode." personid=".$personid);
				return false;
			}
			$appserverCommsuiteTransport->close();
			break;
		} catch (TException $tx) {
			$attempts++;
			// a general thrift exception, like no such server
			error_log("renderEmailTemplateForJobLanguagePerson: Exception Connection to AppServer (" . $tx->getMessage() . ")");
			$appserverCommsuiteTransport->close();
			if ($attempts > 2) {
				error_log("renderEmailTemplateForJobLanguagePerson: Failed 3 times to get content from appserver");
				return false;
			}
		}
	}
	return $result;
}

// Returns TemplateDTO filled with the customer variables for a given language and jobpriority
//
// Finds the template based on job priority,
//		appends all message parts for the message of the given language,
//		merges message body into notification template,
//		fills all customer variables, leaving person field inserts as <<First Name>> for display
//		returns both plain and html email bodies.
// Used by job message preview
function renderEmailTemplateForMessageLanguage($messagegroupid, $jobpriority, $languagecode) {
	global $appserverCommsuiteSocket;
	global $appserverCommsuiteTransport;
	global $appserverCommsuiteProtocol;
	
//	error_log("renderEmailTemplateForMessageLanguage messagegroupid=".$messagegroupid." jobpriority=".$jobpriority." languagecode=".$languagecode);
	
	$attempts = 0;
	while (true) {
		try {
			$client = new CommSuiteClient($appserverCommsuiteProtocol);
		
			// Open up the connection
			$appserverCommsuiteTransport->open();
			try {
				$result = $client->renderEmailTemplateForMessageLanguage(session_id(), $messagegroupid, $jobpriority, $languagecode);
			} catch (commsuite_CommSuite_MessageRendererException $e) {
				error_log("Unable to render messagegroupid=".$messagegroupid." jobpriority=".$jobpriority." languagecode=".$languagecode);
				return false;
			}
			$appserverCommsuiteTransport->close();
			break;
		} catch (TException $tx) {
			$attempts++;
			// a general thrift exception, like no such server
			error_log("renderEmailTemplateForMessageLanguage: Exception Connection to AppServer (" . $tx->getMessage() . ")");
			$appserverCommsuiteTransport->close();
			if ($attempts > 2) {
				error_log("renderEmailTemplateForMessageLanguage: Failed 3 times to get content from appserver");
				return false;
			}
		}
	}
	return $result;
}


?>
