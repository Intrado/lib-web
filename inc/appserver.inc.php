<?
//////////////////////////////////////////////////////
// this file includes all CommSuite AppServer methods
//////////////////////////////////////////////////////


// Returns MessageView filled with the reportperson values of the given job.
//
// Finds the job's messagegroup and priority, 
//		appends all message parts for the message of the given language,
//		merges message body into notification template,
//		fills all customer variables, fills all person variables,
//		returns both plain and html email bodies.
// Used by Contact Manager and Subscriber message viewer
function messageViewForJobPerson($messageid, $jobid, $personid) {
	global $appserverCommsuiteSocket;
	global $appserverCommsuiteTransport;
	global $appserverCommsuiteProtocol;
	
//	error_log("messageViewForJobPerson messageid=".$messageid." jobid=".$jobid." personid=".$personid);
	
	$attempts = 0;
	while (true) {
		try {
			$client = new CommSuiteClient($appserverCommsuiteProtocol);
		
			// Open up the connection
			$appserverCommsuiteTransport->open();
			try {
				$result = $client->emailMessageViewForJobPerson(session_id(), $messageid, $jobid, $personid);
			} catch (commsuite_CommSuite_MessageRendererException $e) {
				error_log("Unable to render messageid=".$messageid." jobid=".$jobid." personid=".$personid);
				return false;
			}
			$appserverCommsuiteTransport->close();
			break;
		} catch (TException $tx) {
			$attempts++;
			// a general thrift exception, like no such server
			error_log("messageViewForJobPerson: Exception Connection to AppServer (" . $tx->getMessage() . ")");
			$appserverCommsuiteTransport->close();
			if ($attempts > 2) {
				error_log("messageViewForJobPerson: Failed 3 times to get content from appserver");
				return false;
			}
		}
	}
	return $result;
}

// Returns MessageView filled with the customer variables for a given language and jobpriority
//
// Finds the template based on job priority,
//		appends all message parts for the message of the given language,
//		merges message body into notification template,
//		fills all customer variables, leaving person field inserts as <<First Name>> for display
//		returns both plain and html email bodies.
// Used by job message preview
function messagePreviewForPriority($messageid, $jobpriority) {
	global $appserverCommsuiteSocket;
	global $appserverCommsuiteTransport;
	global $appserverCommsuiteProtocol;
	
//	error_log("messagePreviewForPriority messageid=".$messageid." jobpriority=".$jobpriority);
	
	$attempts = 0;
	while (true) {
		try {
			$client = new CommSuiteClient($appserverCommsuiteProtocol);
		
			// Open up the connection
			$appserverCommsuiteTransport->open();
			try {
				$result = $client->emailMessagePreviewForPriority(session_id(), $messageid, $jobpriority);
			} catch (commsuite_CommSuite_MessageRendererException $e) {
				error_log("Unable to render messageid=".$messageid." jobpriority=".$jobpriority);
				return false;
			}
			$appserverCommsuiteTransport->close();
			break;
		} catch (TException $tx) {
			$attempts++;
			// a general thrift exception, like no such server
			error_log("messagePreviewForPriority: Exception Connection to AppServer (" . $tx->getMessage() . ")");
			$appserverCommsuiteTransport->close();
			if ($attempts > 2) {
				error_log("messagePreviewForPriority: Failed 3 times to get content from appserver");
				return false;
			}
		}
	}
	return $result;
}


?>
