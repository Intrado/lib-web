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

?>
