<?
require_once("../obj/Content.obj.php");
require_once("../inc/content.inc.php");
require_once("../inc/auth.inc.php");
require_once("dmapidb.inc.php");
require_once("../inc/sessiondata.inc.php");

if (isset($BFXML_ELEMENT['attrs']['SESSIONID'])) {
	$SESSIONID = $BFXML_ELEMENT['attrs']['SESSIONID'];

	$success = false;
	if (strpos($SESSIONID,"outbound_") !== false) {

		//parse out the sessionid
		//trim off the "outbound_" marker from the sessionid
		$sessid = substr($SESSIONID,9); //trim off "outbound_" from the sessionid
		//parse out the bits:
		list($taskid,$customerid,$shardid,$tasktime) = explode(":",base64url_decode($sessid));

		$success = authorizeTaskRequest($shardid, $taskid); //get DB connection info

	} else {

		$SESSIONDATA = loadSessionData($SESSIONID);
		if (isset($SESSIONDATA['authSessionID'])) {
			$ret = getSessionData($SESSIONDATA['authSessionID']); //actually just load the DB connection for the customer
			$success = $ret !== false;
		}
	}

	if ($success) {
		if ($BFXML_ELEMENT['attrs']['REQUEST'] == "get") {
			$content = findChild($BFXML_ELEMENT,"CONTENT");
			$cmid = $content['attrs']['ID'] + 0;

			if ($c = contentGet($cmid, true)) {
				list($contenttype,$data) = $c;
?>
			<content id="<?= $cmid ?>">
				<data mime-type="<?= $contenttype ?>"><?= $data ?></data>
			</content>
<?
			} else {
?>
				<error>Unable to retrieve the content for cmid:<?= $cmid ?></error>
<?
			}
		} else if ($BFXML_ELEMENT['attrs']['REQUEST'] == "put") {
			$content = findChild($BFXML_ELEMENT,"CONTENT");
			$dataelement = findChild($content,"DATA");
			$contenttype = $dataelement['attrs']['MIME-TYPE'];
			$data = $dataelement['txt'];
			$tmpname = secure_tmpname("dmapicontent",".b64");
			if($tmpname != false) {
				file_put_contents($tmpname,$data);
				unset($data); // don't keep in memory
				if ($cmid = contentPut($tmpname,$contenttype,true)) {
?>
					<content id="<?= $cmid ?>" />
<?
				} else {
?>
					<error>Unable to upload content</error>
<?
				}
				unlink($tmpname);
			} else {
?>
				<error>Unable to create tmp file</error>
<?
			}
		}
	} else {
?>
		<error>Not Authorized.</error>
<?
	}
} else {
?>
	<error>No sessionid</error>
<?
}


/*
Content get requests request a specific content ID aka cmid. Currently, the cmid must be an integer. The response is base64 encoded data.
<bfxml>
	<auth><name>email</name><passcode>dd6e2fe5-6b81-11da-81bc-bdb1faac8600</passcode></auth>
	<contentrequest request="get">
		<content id="384" />
	</contentrequest>
</bfxml>


Response:

<bfxml>
	<authorized />
	<content id="384">
		<data mime-type="application/pdf">JVBERi0xLjQKJeLjz9MKMyAwIG...</data>
	</content>
</bfxml>

** Put **
Uploads data to the content repository and assigns a new cmid. This cmid is returned in the response.

<bfxml>
	<auth><name>asdf</name><passcode>0cea7868-4038-11da-92e5-7f70db90b020</passcode></auth>
	<contentrequest request="put">
		<content id="new">
			<data mime-type="audio/wav">UklGRmRRAABXQV...</data>
		</content>
	</contentrequest>
</bfxml>

Response:

<bfxml>
	<authorized />
	<content id="386" />
</bfxml>
*/