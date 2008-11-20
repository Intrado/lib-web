<?
// inbound message retrieval : playback messages, increment list, goodbye

include_once("inboundutils.inc.php");

include_once("../obj/Message.obj.php");
include_once("../obj/MessagePart.obj.php");
include_once("../obj/Person.obj.php");
include_once("../obj/Voice.obj.php");
include_once("../obj/AudioFile.obj.php");
include_once("../obj/VoiceReply.obj.php");

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


function renderMessageParts($playback) {
	$customerid = $_SESSION['customerid'];
	$msgid = $playback['messageid'];
	$person = $playback['person'];
	$fields = array();
	for ($i=1; $i<=20; $i++) {
		$fieldnum = sprintf("f%02d", $i);
		$fields[$fieldnum] = $person->$fieldnum;
	}

	$renderedparts = Message::renderMessageParts($msgid, $fields);
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
			<tts language="<?echo $voice->language?>" gender="<?echo $voice->gender?>"> <?echo $part[1]?></tts>
			<?
		}
	}
}


function playback($messageindex, $messagetotal, $playback, $playintro = false) {
	$messageparts = $playback['messageparts'];
	$person = $playback['person'];
?>
<voice>
	<message name="playback">
		<field name="doplayback" type="menu" timeout="5000">
			<prompt>
				<?if ($playintro) {
					if ($messagetotal == 1) {?>
						<tts gender="female">There is one message in the last 30 days.  You may press the star key to repeat. </tts>
					<?} else {?>
						<tts gender="female">There are <?echo $messagetotal?> messages in the last 30 days.  You may press the pound key at any time to skip to the next message, or press the star key to repeat. </tts>
					<?}?>
				<?}?>

				<? if ($messagetotal == 1) {?>
					<tts gender="female">Message for <?echo ("$person->f01 $person->f02");?>.  </tts>
				<?} else {?>
					<tts gender="female">Message <?echo($messageindex +1)?> of <?echo $messagetotal?> for <?echo ("$person->f01 $person->f02");?>.  </tts>
				<?}?>

				<?renderMessageParts($playback);?>
			</prompt>

			<choice digits="*" />
			<choice digits="#" />

			<?if ($playback['leavemessage'] === "1") {?>
				<choice digits="0">
					<goto message="recordvoicereply" />
				</choice>
			<?}?>
			<?if ($playback['messageconfirmation'] === "1") {?>
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
			<timeout>
				<setvar name="timeout" value="1" />
			</timeout>
		</field>
	</message>

	<?if ($playback['leavemessage'] === "1") {?>
	<message name="recordvoicereply">
		<field name="voicereply" type="record" max="60">
			<prompt>
				<tts>Please leave a message after the beep. When you are finished recording, press any key to continue. </tts>
			</prompt>
		</field>
		<uploadaudio name="voicereply"/>
		<tts gender="female">Thank you, your message has been saved. </tts>
	</message>
	<?}?>

	<?if ($playback['messageconfirmation'] === "1") {?>
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

function hangup() {
?>
<voice>
	<message name="hangup">
	       	<tts gender="female">You may call back to listen to your messages. Goodbye.</tts>
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
			$playback = $_SESSION['messagelist'][$_SESSION['messageindex']-1]; // get last message played
			$person = $playback['person'];

			$vr = new VoiceReply();
			$vr->personid = $person->id;
			$vr->jobid = $playback['jobid'];
			$vr->sequence = $playback['sequence'];
			$vr->userid = $playback['userid'];
			$vr->contentid = $BFXML_VARS['voicereply'];
			$vr->replytime = time()*1000;
			$vr->listened = 0;
			$vr->update();

			$query = "update reportcontact set participated=1, voicereplyid=".$vr->id." where jobid=".$playback['jobid']." and personid=".$person->id." and type='phone' and sequence=".$playback['sequence'];
			QuickUpdate($query);
		}

		if (isset($BFXML_VARS['messageconfirm'])) {
			//error_log("messageconfirm ".$BFXML_VARS['messageconfirm']);
			$playback = $_SESSION['messagelist'][$_SESSION['messageindex']-1]; // get last message played
			$person = $playback['person'];

			$query = "update reportcontact set participated=1, response=".$BFXML_VARS['messageconfirm']." where jobid=".$playback['jobid']." and personid=".$person->id." and type='phone' and sequence=".$playback['sequence'];
			QuickUpdate($query);
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
					// repeat last message with instructions
					$playintro = true;
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
			$playback = $_SESSION['messagelist'][$_SESSION['messageindex']];

			playback($_SESSION['messageindex'], $_SESSION['messagetotal'], $playback, $playintro);
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
