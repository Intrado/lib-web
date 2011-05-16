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

function ttsGetForTextLanguageGenderFormat($text, $language, $gender, $format) {
	global $appserverCommsuiteSocket;
	global $appserverCommsuiteTransport;
	global $appserverCommsuiteProtocol;
		
	$attempts = 0;
	while (true) {
		try {
			$client = new CommSuiteClient($appserverCommsuiteProtocol);
			$appserverCommsuiteTransport->open();
			
			// Connect and be sure to catch and log all exceptions
			try {
				$result = $client->ttsGetForTextLanguageGenderFormat($text, $language, $gender,$format);
			} catch (commsuite_NotFoundException $e) {
				error_log("ttsGetForTextLanguageGenderFormat: Contentid not found for Language $language, Gender: $gender and Text: $text");
				return false;
			}
			$appserverCommsuiteTransport->close();
			break;
		} catch (TException $tx) {
			$attempts++;
			// a general thrift exception, like no such server
			error_log("ttsGetForTextLanguageGenderFormat: Exception Connection to AppServer (" . $tx->getMessage() . ")");
			$appserverCommsuiteTransport->close();
			if ($attempts > 2) {
				error_log("ttsGetForTextLanguageGenderFormat: Failed 3 times to get content from appserver");
				return false;
			}
		}
	}
	return $result;
}

function audioFileGetForFormat($contentid, $format) {
	global $appserverCommsuiteSocket;
	global $appserverCommsuiteTransport;
	global $appserverCommsuiteProtocol;
		
	$attempts = 0;
	while (true) {
		try {
			$client = new CommSuiteClient($appserverCommsuiteProtocol);
			$appserverCommsuiteTransport->open();
			
			// Connect and be sure to catch and log all exceptions
			try {
				$result = $client->audioFileGetForFormat(session_id(), $contentid, $format);
			} catch (commsuite_SessionInvalidException $e) {
				error_log("audioFileGetForFormat: Invalid Sessionid");
				return false;
			} catch (commsuite_NotFoundException $e) {
				error_log("audioFileGetForFormat: Contentid $contentid not found");
				return false;
			}
			$appserverCommsuiteTransport->close();
			break;
		} catch (TException $tx) {
			$attempts++;
			// a general thrift exception, like no such server
			error_log("audioFileGetForFormat: Exception Connection to AppServer (" . $tx->getMessage() . ")");
			$appserverCommsuiteTransport->close();
			if ($attempts > 2) {
				error_log("audioFileGetForFormat: Failed 3 times to get content from appserver");
				return false;
			}
		}
	}
	return $result;
}

function phoneMessageGetMp3AudioFile($parts) {
	global $appserverCommsuiteSocket;
	global $appserverCommsuiteTransport;
	global $appserverCommsuiteProtocol;
	
	$attempts = 0;
	while (true) {
		try {
			$client = new CommSuiteClient($appserverCommsuiteProtocol);
			$appserverCommsuiteTransport->open();
			
			// Connect and be sure to catch and log all exceptions
			try {
				$result = $client->phoneMessageGetMp3AudioFile(session_id(), $parts);
			} catch (commsuite_SessionInvalidException $e) {
				error_log("phoneMessageGetMp3AudioFile: Invalid Sessionid");
				return false;
			} catch (commsuite_NotFoundException $e) {
				error_log("phoneMessageGetMp3AudioFile: Content not found");
				return false;
			}
			$appserverCommsuiteTransport->close();
			break;
		} catch (TException $tx) {
			$attempts++;
			// a general thrift exception, like no such server
			error_log("phoneMessageGetMp3AudioFile: Exception Connection to AppServer (" . $tx->getMessage() . ")");
			$appserverCommsuiteTransport->close();
			if ($attempts > 2) {
				error_log("phoneMessageGetMp3AudioFile: Failed 3 times to get content from appserver");
				return false;
			}
		}
	}
	return $result;
}

?>
