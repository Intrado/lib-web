<?

require_once("../obj/SpecialTask.obj.php");
include_once("../inc/utils.inc.php");


//remember to embed the $SESSIONID manually in each task element
//when you are done with the session, set $SESSIONDATA to null

//$SESSIONDATA = array_merge($SESSIONDATA,$BFXML_VARS);


//load the specialtask
$specialtask = new SpecialTask($SESSIONDATA['specialtaskid']);

if ($REQUEST_TYPE == "new") {
?>
	<email sessionid="<?= $SESSIONID ?>">
		<to>
<?
			$x = 1;
			while ($specialtask->getData("toemail$x")) {
				$toemail = $specialtask->getData("toemail$x");
				$toname = $specialtask->getData("toname$x");
?>
				<emailaddress name="<?= htmlentities($toname, ENT_COMPAT, "UTF-8") ?>" address="<?= htmlentities($toemail, ENT_COMPAT, "UTF-8") ?>" />
<?
				$x++;
			}
?>
		</to>
		<from>
			<emailaddress name="<?= htmlentities($specialtask->getData("fromname"), ENT_COMPAT, "UTF-8") ?>"
						address="<?= htmlentities($specialtask->getData("fromemail"), ENT_COMPAT, "UTF-8") ?>" />
		</from>
		<subject><?= htmlentities($specialtask->getData("subject"), ENT_COMPAT, "UTF-8") ?></subject>
		<body><?= htmlentities($specialtask->getData("body"), ENT_COMPAT, "UTF-8") ?></body>
<?
		$x = 1;
		while ($specialtask->getData("attachmentcmid$x")) {
			$attachmentname = $specialtask->getData("attachmentname$x");
			$attachmentcmid = $specialtask->getData("attachmentcmid$x");
?>
			<attachment name="<?= htmlentities($attachmentname, ENT_COMPAT, "UTF-8")?>" cmid="<?= htmlentities($attachmentcmid, ENT_COMPAT, "UTF-8")?>" />
<?
			$x++;
		}
?>
	</email>
<?
} else if ($REQUEST_TYPE == "continue") {
?>
	<error>Email does not support continue</error>
<?
} else if ($REQUEST_TYPE == "result") {
	//update the specialtask data showing the email is sent

	$specialtask->setData("sent", $BFXML_VARS['sent'] == "true" ? "true" : "false");
	$specialtask->status = "done";
	$specialtask->update();

	$SESSIONDATA = null; //done w/ sessiondata

?>
	<ok />
<?
}

?>