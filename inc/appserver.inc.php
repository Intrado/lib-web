<?
// this file includes all CommSuite AppServer methods


// returns TemplateGroupDTO, essentially a list of templates one per language
// all variables filled with customer and person data
// If personid is 0, filled with <<First Name>> appropriate field insert
function renderEmailNotification($messagegroupid, $personid = 0) {
	global $appserverCommsuiteSocket;
	global $appserverCommsuiteTransport;
	global $appserverCommsuiteProtocol;
	
	$customerid = $_SESSION['_dbcid'];

	error_log("renderEmailNotification cid=".$customerid." msggroupid=".$messagegroupid." pid=".$personid);
	
	$attempts = 0;
	while (true) {
		try {
			$client = new CommSuiteClient($appserverCommsuiteProtocol);
		
			// Open up the connection
			$appserverCommsuiteTransport->open();
			try {
				$result = $client->renderEmailTemplateGroupForCustomerMessagePerson($customerid, $messagegroupid, $personid);
			} catch (commsuite_CommSuite_MessageRendererException $e) {
				error_log("Unable to render messagegroupid: " . $messagegroupid);
				return false;
			}
			$appserverCommsuiteTransport->close();
			break;
		} catch (TException $tx) {
			$attempts++;
			// a general thrift exception, like no such server
			error_log("renderEmailTemplateGroupForCustomerMessagePerson: Exception Connection to AppServer (" . $tx->getMessage() . ")");
			$appserverCommsuiteTransport->close();
			if ($attempts > 2) {
				error_log("renderEmailTemplateGroupForCustomerMessagePerson: Failed 3 times to get content from appserver");
				return false;
			}
		}
	}
	return $result;
}

/*
 * Returns TemplateDTO filled with the reportperson values of the given job.
 */
function renderEmailTemplateForJobLanguagePerson($jobid, $languagecode, $personid) {
	global $appserverCommsuiteSocket;
	global $appserverCommsuiteTransport;
	global $appserverCommsuiteProtocol;
	
	error_log("renderEmailTemplateForJobLanguagePerson jobid=".$jobid." languagecode=".$languagecode." personid=".$personid);
	
	$sessionid = "todosessionid"; // TODO
	
	$attempts = 0;
	while (true) {
		try {
			$client = new CommSuiteClient($appserverCommsuiteProtocol);
		
			// Open up the connection
			$appserverCommsuiteTransport->open();
			try {
				$result = $client->renderEmailTemplateForJobLanguagePerson($sessionid, $jobid, $languagecode, $personid);
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

function renderEmailTemplateForMessageLanguage($messagegroupid, $jobpriority, $languagecode) {
	global $appserverCommsuiteSocket;
	global $appserverCommsuiteTransport;
	global $appserverCommsuiteProtocol;
	
	error_log("renderEmailTemplateForMessageLanguage messagegroupid=".$messagegroupid." jobpriority=".$jobpriority." languagecode=".$languagecode);
	
	$sessionid = "todosessionid"; // TODO
	
	$attempts = 0;
	while (true) {
		try {
			$client = new CommSuiteClient($appserverCommsuiteProtocol);
		
			// Open up the connection
			$appserverCommsuiteTransport->open();
			try {
				$result = $client->renderEmailTemplateForMessageLanguage($sessionid, $messagegroupid, $jobpriority, $languagecode);
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
