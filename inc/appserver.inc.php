<?
//////////////////////////////////////////////////////
// this file includes all CommSuite AppServer methods
//////////////////////////////////////////////////////

/////////////////////////////////////
// AppServer MessageLink service

function initMessageLinkApp() {
	global $SETTINGS;
	$appservertransport = null;
	$appserverprotocol = null;

	try {
		if (isset($SETTINGS['appserver']) && isset($SETTINGS['appserver']['host'])) {

			$appserverhost = explode(":",$SETTINGS['appserver']['host']);
			if (count($appserverhost) == 2) {

				$appserversocket = new TSocket($appserverhost[0], $appserverhost[1]);
				//$appserversocket->setDebug(true);
				if (isset($SETTINGS['appserver']['timeout'])) {
					$appserversocket->setRecvTimeout($SETTINGS['appserver']['timeout']);
				} else {
					$appserversocket->setRecvTimeout(5500);
				}
				$appservertransport = new TFramedTransport($appserversocket);
				//$appserverprotocol = new TBinaryProtocol($appservertransport);

				$appserverprotocol = new TBinaryProtocolAccelerated($appservertransport);

			}
		}
	} catch (TException $tx) {
		// a general thrift exception, like no such server
		error_log("Exception Connection to AppServer MessageLink service (" . $tx->getMessage() . ")");
	}
	return array($appserverprotocol,$appservertransport);
}
//////////////////////////////////////////////////////////
// AppServer CommSuite service

function initCommsuiteApp() {
	global $SETTINGS;
	$appservertransport = null;
	$appserverprotocol = null;

	try {

		if (isset($SETTINGS['appserver_commsuite']) && isset($SETTINGS['appserver_commsuite']['host'])) {
			$appserverhost = explode(":",$SETTINGS['appserver_commsuite']['host']);
			if (count($appserverhost) == 2) {
				$appserversocket = new TSocket($appserverhost[0], $appserverhost[1]);
				//$appserversocket->setDebug(true);
				if (isset($SETTINGS['appserver_commsuite']['timeout'])) {
					$appserversocket->setRecvTimeout($SETTINGS['appserver_commsuite']['timeout']);
				} else {
					$appserversocket->setRecvTimeout(5500);
				}
				$appservertransport = new TFramedTransport($appserversocket);
				//$appserverprotocol = new TBinaryProtocol($appservertransport);

				$appserverprotocol = new TBinaryProtocolAccelerated($appservertransport);

			}
		}
	} catch (TException $tx) {
		// a general thrift exception, like no such server
		error_log("Exception Connection to AppServer CommSuite service (" . $tx->getMessage() . ")");
	}
	return array($appserverprotocol,$appservertransport);
}



// Returns MessageView filled with the reportperson values of the given job.
//
// Finds the job's messagegroup and priority, 
//		appends all message parts for the message of the given language,
//		merges message body into notification template,
//		fills all customer variables, fills all person variables,
//		returns both plain and html email bodies.
// Used by Contact Manager and Subscriber message viewer
function messageViewForJobPerson($messageid, $jobid, $personid) {
	list($appserverCommsuiteProtocol,$appserverCommsuiteTransport) = initCommsuiteApp();
	if ($appserverCommsuiteProtocol == null || $appserverCommsuiteTransport == null) {
		error_log("Cannot use AppServer");
		return null;
	}
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
	list($appserverCommsuiteProtocol,$appserverCommsuiteTransport) = initCommsuiteApp();
	if ($appserverCommsuiteProtocol == null || $appserverCommsuiteTransport == null) {
		error_log("Cannot use AppServer");
		return null;
	}
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

function emailMessageViewForMessageParts($message,$parts,$jobpriority) {
	list($appserverCommsuiteProtocol,$appserverCommsuiteTransport) = initCommsuiteApp();
	
	if ($appserverCommsuiteProtocol == null || $appserverCommsuiteTransport == null) {
		error_log("Cannot use AppServer");
		return null;
	} 
	
	$messagedto = new commsuite_MessageDTO();
	$messagedto->type = $message->type;
	$messagedto->subtype = $message->subtype;
	$messagedto->data = $message->data;
	$messagedto->languagecode = $message->languagecode;
	$partdtos = array();
	foreach($parts as $part) {
		if ($part->type == "T" || $part->type == "V" || $part->type == "I" ) {
			$partdto = new commsuite_MessagePartDTO();
			switch ($part->type) {
				case "T":
					$partdto->type = commsuite_MessagePartTypeDTO::T;
					break;
				case "V":
					$partdto->type = commsuite_MessagePartTypeDTO::V;
					break;
				case "I":
					$partdto->type = commsuite_MessagePartTypeDTO::I;
					break;
			}
			
			$partdto->txt = $part->txt;
			$partdto->languagecode = "en";
			$partdto->gender = "female";
			$partdto->fieldnum = $part->fieldnum;
			$partdto->defaultvalue = $part->defaultvalue;
			$partdto->contentid = $part->imagecontentid;
			$partdtos[] = $partdto;
		}
	}
	
	$attempts = 0;
	while (true) {
		try {
			$client = new CommSuiteClient($appserverCommsuiteProtocol);
		
			// Open up the connection
			$appserverCommsuiteTransport->open();
			try {
				$result = $client->emailMessageViewForMessageParts(session_id(), $messagedto,$partdtos, $jobpriority);
			} catch (emailMessageViewForMessageParts $e) {
				error_log("phoneMessageGetMp3AudioFile: Invalid Sessionid");
				return false;
			}
			$appserverCommsuiteTransport->close();
			break;
		} catch (TException $tx) {
			$attempts++;
			// a general thrift exception, like no such server
			error_log("emailMessageViewForMessageParts: Exception Connection to AppServer (" . $tx->getMessage() . ")");
			$appserverCommsuiteTransport->close();
			if ($attempts > 2) {
				error_log("emailMessageViewForMessageParts: Failed 3 times to get content from appserver");
				return false;
			}
		}
	}
	return $result;
}


function ttsGetForTextLanguageGenderFormat($text, $language, $gender, $format) {
	list($appserverCommsuiteProtocol,$appserverCommsuiteTransport) = initCommsuiteApp();
	
	if ($appserverCommsuiteProtocol == null || $appserverCommsuiteTransport == null) {
		error_log("Cannot use AppServer");
		return null;
	} 
		
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
	list($appserverCommsuiteProtocol,$appserverCommsuiteTransport) = initCommsuiteApp();
	
	if ($appserverCommsuiteProtocol == null || $appserverCommsuiteTransport == null) {
		error_log("Cannot use AppServer");
		return null;
	} 
		
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
	list($appserverCommsuiteProtocol,$appserverCommsuiteTransport) = initCommsuiteApp();
	
	if ($appserverCommsuiteProtocol == null || $appserverCommsuiteTransport == null) {
		error_log("Cannot use AppServer");
		return null;
	} 
	
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

function processIncomingSms($smsParams) {
	list($appserverCommsuiteProtocol,$appserverCommsuiteTransport) = initCommsuiteApp();
	
	if ($appserverCommsuiteProtocol == null || $appserverCommsuiteTransport == null) {
		error_log("Cannot use AppServer");
		return null;
	}
	
	$attempts = 0;
	while (true) {
		try {
			$client = new CommSuiteClient($appserverCommsuiteProtocol);
			$appserverCommsuiteTransport->open();
				
			// Connect and be sure to catch and log all exceptions
			$client->processIncomingSms($smsParams);
			return true;
		} catch (TException $tx) {
			$attempts++;
			// a general thrift exception, like no such server
			error_log("processIncomingSms: Exception Connection to AppServer (" . $tx->getMessage() . ")");
			$appserverCommsuiteTransport->close();
			if ($attempts > 2) {
				error_log("processIncomingSms: Failed 3 times to send sms to appserver");
				return false;
			}
		}
	}
}

?>
