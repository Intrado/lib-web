<?
// inbound message retrieval : playback messages, increment list, goodbye

include_once("inboundutils.inc.php");

include_once("../obj/Message.obj.php");
include_once("../obj/MessagePart.obj.php");
include_once("../obj/Voice.obj.php");
include_once("../obj/AudioFile.obj.php");
include_once("../obj/VoiceReply.obj.php");

global $BFXML_VARS;


function endoflist() {
?>
<voice>
	<message name="endoflist">
		<field name="doendoflist" type="menu" timeout="10000">
			<prompt repeat="2">
				<tts gender="female">There are no more messages for this phone number. To hear your messages again, press the pound key. To check for messages sent to a different phone number, please hang up and call again. Otherwise, you may simply hang up to end this call.  </tts>
			</prompt>

			<choice digits="#" />

			<default>
				<tts gender="female">Sorry, that was not a valid selection. </tts>
			</default>

			<timeout>
				<goto message="error" />
			</timeout>
		</field>
	</message>

	<message name="error">
		<tts gender="female">You may call again to listen to your messages. goodbye!</tts>
		<hangup />
	</message>
</voice>
<?
}


function renderMessageParts($messagedata) {
	$customerid = $_SESSION['customerid'];
	$msgid = $messagedata['messageid'];
	$fields = $messagedata['personfields'];
	$parts = DBFindMany('MessagePart', 'from messagepart where messageid=? order by sequence', false, array($msgid));

	$renderedparts = Message::renderPhoneParts($parts, $fields);
	$voices = DBFindMany("Voice","from ttsvoice");

	foreach ($renderedparts as $part) {
		if ($part[0] == "a") {
			$contentid = $part[1];
			$guid = md5("$contentid".":"."$customerid");
			?>
			<audio cmid="<?echo $contentid?>" guid="<?echo $guid?>"/>
			<?
		} else if ($part[0] == "t") {
			$voice = $voices[$part[2]];
			?>
			<tts language="<?echo $voice->language?>" gender="<?echo $voice->gender?>"><![CDATA[ <?=str_replace(']]>', '', $part[1])?>]]></tts>
			<?
		}
	}
}


function playback($messageindex, $messagetotal, $messagedata, $playintro = false) {
	$messageparts = $messagedata['messageparts'];
	$fields = $messagedata['personfields'];
?>
<voice>
	<message name="playback">

<?	if ($playintro) {
		if ($messagetotal == 1) {?>
			<tts gender="female">There is 1  message for this phone number. At any time while the message is playing, you may press the star key to repeat the message, or the pound key to skip to the next message. </tts>
		<?} else {?>
			<tts gender="female">There are</tts>
			<tts gender="female"><?echo $messagetotal?></tts>
			<tts gender="female">messages for this phone number. At any time while the message is playing, you may press the star key to repeat the message, or the pound key to skip to the next message. </tts>
		<?}?>
<?	} ?>

		<field name="doplayback" type="menu" timeout="500">
			<prompt>
				<? if ($messagetotal == 1) {?>
					<tts gender="female">Message for -- </tts>
				<?} else {?>
					<tts gender="female">Message</tts>
					<tts gender="female"><?echo($messageindex +1)?> of <?echo $messagetotal?>, for -- </tts>
				<?}?>
				<tts gender="female"><?=escapehtml($fields['f01'])?></tts>
				<tts gender="female"><?=escapehtml($fields['f02'])?></tts>
				<tts gender="female">,Last sent on,</tts>
				<tts gender="female"><?= date("l, F jS. \a\\t g:i a.",$messagedata['starttime']/1000)?></tts>
				<delay duration="250"/>
				<?renderMessageParts($messagedata);?>
				<tts gender="female">To repeat the message press the star key.</tts>
				<delay duration="1000"/>
			</prompt>

			<choice digits="*" />
			<choice digits="#" />

			<?if ($messagedata['leavemessage'] === "1") {?>
				<choice digits="0">
					<goto message="recordvoicereply" />
				</choice>
			<?}?>
			<?if ($messagedata['messageconfirmation'] === "1") {?>
				<choice digits="1">
					<goto message="messageconfirmyes" />
				</choice>
				<choice digits="2">
					<goto message="messageconfirmno" />
				</choice>
			<?}?>

			<default>
				<tts gender="female">Sorry, that was not a valid selection. </tts>
			</default>
			<timeout>
				<setvar name="timeout" value="1" />
			</timeout>
		</field>
	</message>

	<?if ($messagedata['leavemessage'] === "1") {?>
	<message name="recordvoicereply">
		<field name="voicereply" type="record" max="60">
			<prompt>
				<tts gender="female">Please leave a message after the beep. When you are finished recording, press any key to continue. </tts>
			</prompt>
		</field>
		<uploadaudio name="voicereply"/>
		<delay duration="1000"/>
		<tts gender="female">Thank you, your message has been saved. </tts>
	</message>
	<?}?>

	<?if ($messagedata['messageconfirmation'] === "1") {?>
	<message name="messageconfirmyes">
		<setvar name="messageconfirm" value="1"/>
		<delay duration="1000"/>
		<tts gender="female">Thank you, your response has been noted.</tts>
	</message>
	<message name="messageconfirmno">
		<setvar name="messageconfirm" value="2"/>
		<delay duration="1000"/>
		<tts gender="female">Thank you, your response has been noted.</tts>
	</message>
	<?}?>

	<message name="error">
		<tts gender="female">I'm sorry, but I was not able to understand your selection. Please call back and try again. goodbye!</tts>
		<hangup />
	</message>
</voice>
<?
}

function hangup() {
?>
<voice>
	<message name="hangup">
	       	<tts gender="female">Your session has timed out. You may call again to listen to your messages. goodbye!</tts>
	       	<hangup />

	</message>
</voice>
<?
}


////////////////////////////////////////
if($REQUEST_TYPE == "new"){
	?>
	<error>msgcallbackplayback: wanted result or continue, got new </error>
	<hangup />
	<?
} else if($REQUEST_TYPE == "continue"){

	if (isset($_SESSION['messageindex'])) {

		$dohangup = false;

		if (isset($BFXML_VARS['voicereply'])) {
			//error_log("voicereply cmid=".$BFXML_VARS['voicereply']);
			$messagedata = $_SESSION['messagelist'][$_SESSION['messageindex']-1]; // get last message played
			$personid = $messagedata['personid'];

			$vr = new VoiceReply();
			$vr->personid = $personid;
			$vr->jobid = $messagedata['jobid'];
			$vr->sequence = $messagedata['sequence'];
			$vr->userid = $messagedata['userid'];
			$vr->contentid = DBSafe($BFXML_VARS['voicereply']);
			$vr->replytime = time()*1000;
			$vr->listened = 0;
			$vr->update();

			$query = "update reportcontact set participated=1, voicereplyid=? where jobid=? and personid=? and type='phone' and sequence=?";
			QuickUpdate($query,false,array($vr->id,$messagedata['jobid'],$personid,$messagedata['sequence']));
		}

		if (isset($BFXML_VARS['messageconfirm'])) {
			//error_log("messageconfirm ".$BFXML_VARS['messageconfirm']);
			$messagedata = $_SESSION['messagelist'][$_SESSION['messageindex']-1]; // get last message played
			$personid = $messagedata['personid'];

			$query = "update reportcontact set participated=1, response=? where jobid=? and personid=? and type='phone' and sequence=?";
			QuickUpdate($query,false,array($BFXML_VARS['messageconfirm'],$messagedata['jobid'],$personid,$messagedata['sequence']));
		}

		$playintro = false;
		if (isset($BFXML_VARS['doplayback'])) {
			if ($BFXML_VARS['doplayback'] === "#" ||
				isset($BFXML_VARS['timeout']) ||
				isset($BFXML_VARS['voicereply']) ||
				isset($BFXML_VARS['messageconfirm'])) {
				// skip to next message, do nothing
			} else if ($BFXML_VARS['doplayback'] === "*") {
				// repeat last message
				$_SESSION['messageindex'] = $_SESSION['messageindex'] - 1;
			} else {
				// invalid option, repeat but only 3 times then move on
				if (!isset($_SESSION['invalidcounter']))
					$_SESSION['invalidcounter'] = 0;
				$_SESSION['invalidcounter'] ++;

				if ($_SESSION['invalidcounter'] >= 3) {
					$_SESSION['invalidcounter'] = 0;
					// let's move on... skip to next message, do nothing
				} else {
					$_SESSION['messageindex'] = $_SESSION['messageindex'] - 1;
				}
			}
		} else {
			$playintro = true;
		}

		if (isset($BFXML_VARS['doendoflist'])) {
			if ($BFXML_VARS['doendoflist'] === "#") {
				// pound # pressed on end of list, reset to beginning
				$_SESSION['messageindex'] = 0;
				$playintro = false;
			} else {
				// invalid option
				$dohangup = true;
			}
		}

		//error_log("messageindex = ".$_SESSION['messageindex']);

		if ($dohangup) {
			hangup();

		// end of list
		} else if ($_SESSION['messageindex'] == $_SESSION['messagetotal']) {
			endoflist();

		// next message
		} else {
			$messagedata = $_SESSION['messagelist'][$_SESSION['messageindex']];

			playback($_SESSION['messageindex'], $_SESSION['messagetotal'], $messagedata, $playintro);
			$_SESSION['messageindex'] = $_SESSION['messageindex'] + 1; // increment to next message
		}
	} else {
		error_log("msgcallbackplayback is missing required argument 'messageindex'");
		?>
		<error>msgcallbackplayback: continue requires messageindex </error>
		<hangup />
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
