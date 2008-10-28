<?
// inbound message retrieval : playback messages, increment list, goodbye

include_once("inboundutils.inc.php");

include_once("../obj/Message.obj.php");
include_once("../obj/MessagePart.obj.php");
include_once("../obj/Person.obj.php");
include_once("../obj/Voice.obj.php");
include_once("../obj/AudioFile.obj.php");
include_once("../obj/VoiceReply.obj.php");
include_once("msgcallbackMessagePlayback.obj.php");

global $BFXML_VARS;


function endoflist()
{
?>
<voice>
	<message name="endoflist">
		<field name="doendoflist" type="menu" timeout="5000">
			<prompt repeat="2">
				<tts gender="female">There are no more messages.  To listen to your messages from the beginning, press the pound key.  Otherwise, you may end the call by hanging up.  </tts>
			</prompt>

			<choice digits="#" />

			<default>
				<tts gender="female">Sorry, that was not a valid option. </tts>
			</default>

			<timeout>
				<goto message="error" />
			</timeout>
		</field>
	</message>

	<message name="error">
		<tts gender="female">You may call back to listen to your messages. Goodbye.</tts>
		<hangup />
	</message>
</voice>
<?
}


function renderMessageParts($playback, $ttsvoices) {
	$customerid = $_SESSION['customerid'];
	$messageparts = $playback->messageparts;
	$person = $playback->person;

	foreach ($messageparts as $part) {
		switch ($part->type) {
			case "A" :
				$contentid = QuickQuery("select contentid from audiofile where id=".$part->audiofileid);
				$guid = md5("$contentid".":"."$customerid");
				?>
				<audio cmid="<?echo $contentid?>" guid="<?echo $guid?>"/>
				<?
			break;
			case "T" :
				// TODO combine parts if same lang/gender
				?>
				<tts language="<?echo $ttsvoices[$part->voiceid]->language?>" gender="<?echo $ttsvoices[$part->voiceid]->gender?>"> <?echo $part->txt?></tts>
				<?
			break;
			case "V" :
				$fnum = $part->fieldnum;
				$vtxt = $person->$fnum;
				if ($vtxt === "") $vtxt = $part->defaultvalue;
				?>
				<tts language="<?echo $ttsvoices[$part->voiceid]->language?>" gender="<?echo $ttsvoices[$part->voiceid]->gender?>"> <?echo $vtxt?></tts>
				<?
			break;
		}
	}
}


function playback($messageindex, $messagetotal, $playback, $ttsvoices, $playintro = false) {
	$messageparts = $playback->messageparts;
	$person = $playback->person;
?>
<voice>
	<message name="playback">
		<field name="doplayback" type="menu" timeout="5000">
			<prompt>
				<?if ($playintro) {?>
					<tts gender="female">There are <?echo $messagetotal?> messages in the last 30 days.  You may press the pound key at any time to skip to the next message, or press the star key to repeat. </tts>
				<?}?>

				<tts gender="female">Message <?echo($messageindex +1)?> of <?echo $messagetotal?> for <?echo ("$person->f01 $person->f02");?>.  </tts>

				<?renderMessageParts($playback, $ttsvoices);?>
			</prompt>

			<choice digits="*" />
			<choice digits="#" />

			<?if ($playback->leavemessage === "1") {?>
				<choice digits="0">
					<goto message="recordvoicereply" />
				</choice>
			<?}?>
			<?if ($playback->messageconfirmation === "1") {?>
				<choice digits="1">
					<goto message="messageconfirmyes" />
				</choice>
				<choice digits="2">
					<goto message="messageconfirmno" />
				</choice>
			<?}?>

			<default>
				<tts gender="female">Sorry, that was not a valid option. </tts>
			</default>
		</field>
	</message>

	<?if ($playback->leavemessage === "1") {?>
	<message name="recordvoicereply">
		<field name="voicereply" type="record" max="60">
			<prompt>
				<tts>Please leave a message after the beep. </tts>
			</prompt>
		</field>
		<uploadaudio name="voicereply"/>
		<tts gender="female">Thank you, your message has been saved. </tts>
	</message>
	<?}?>

	<?if ($playback->messageconfirmation === "1") {?>
	<message name="messageconfirmyes">
		<setvar name="messageconfirm" value="1"/>
		<tts>Thank you, your response has been noted.</tts>
	</message>
	<message name="messageconfirmno">
		<setvar name="messageconfirm" value="2"/>
		<tts>Thank you, your response has been noted.</tts>
	</message>
	<?}?>

	<message name="error">
		<tts gender="female">I was not able to understand your response.  Goodbye.</tts>
		<hangup />
	</message>
</voice>
<?
}


////////////////////////////////////////
if($REQUEST_TYPE == "new"){
	?>
	<error>msgcallbackplayback: wanted result or continue, got new </error>
	<?
} else if($REQUEST_TYPE == "continue"){

	if (isset($_SESSION['messageindex'])) {

		if (isset($BFXML_VARS['voicereply'])) {
			glog("voicereply cmid=".$BFXML_VARS['voicereply']);
			$playback = $_SESSION['messagelist'][$_SESSION['messageindex']-1]; // get last message played
			$person = $playback->person;

			$vr = new VoiceReply();
			$vr->personid = $person->id;
			$vr->jobid = $playback->jobid;
			$vr->sequence = $playback->sequence;
			$vr->userid = $playback->userid;
			$vr->contentid = $BFXML_VARS['voicereply'];
			$vr->replytime = time()*1000;
			$vr->listened = 0;
			$vr->update();

			$query = "update reportcontact set participated=1, voicereplyid=".$vr->id." where jobid=".$playback->jobid." and personid=".$person->id." and type='phone' and sequence=".$playback->sequence;
			QuickUpdate($query);
		}

		if (isset($BFXML_VARS['messageconfirm'])) {
			glog("messageconfirm ".$BFXML_VARS['messageconfirm']);
			$playback = $_SESSION['messagelist'][$_SESSION['messageindex']-1]; // get last message played
			$person = $playback->person;

			$query = "update reportcontact set participated=1, response=".$BFXML_VARS['messageconfirm']." where jobid=".$playback->jobid." and personid=".$person->id." and type='phone' and sequence=".$playback->sequence;
			QuickUpdate($query);
		}

		$playintro = false;
		if (isset($BFXML_VARS['doplayback'])) {
			if ($BFXML_VARS['doplayback'] === "*") {
				// repeat last message
				$_SESSION['messageindex'] = $_SESSION['messageindex'] - 1;
			}
			// pound #, or anything else just go to next message
		} else {
			$playintro = true;
		}

		if (isset($BFXML_VARS['doendoflist'])) {
			// pound # pressed on end of list, reset to beginning
			$_SESSION['messageindex'] = 0;
			$playintro = false;
		}

		glog("messageindex = ".$_SESSION['messageindex']);

		// end of list
		if ($_SESSION['messageindex'] == $_SESSION['messagetotal']) {
			endoflist();

		// next message
		} else {
			$playback = $_SESSION['messagelist'][$_SESSION['messageindex']];
			$ttsvoices = DBFindMany("Voice", "from ttsvoice"); // TODO move out of loop?

			playback($_SESSION['messageindex'], $_SESSION['messagetotal'], $playback, $ttsvoices, $playintro);
			$_SESSION['messageindex'] = $_SESSION['messageindex'] + 1; // increment to next message
		}
	} else {
		glog("MISSING INDEX");
		?>
		<error>msgcallbackplayback: continue requires messageindex </error>
		<?
	}
} else {
	//huh, they must have hung up
	$_SESSION = array();
	?>
	<ok />
	<?
}

?>
