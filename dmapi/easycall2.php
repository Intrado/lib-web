<?
require_once("../inc/utils.inc.php");
require_once("../obj/SpecialTask.obj.php");
require_once("../obj/Content.obj.php");
require_once("../obj/MessageGroup.obj.php");


$error = 0;
$specialtask = new specialtask($_SESSION['specialtaskid']);
$phone = $specialtask->getData('phonenumber');
$_SESSION['userid'] = $specialtask->getData('userid');

if($REQUEST_TYPE == "new") {
		$ERROR .= "Got new when wanted continue";
} else {
	// audio was saved, we should store the content id
	if(isset($BFXML_VARS['saveaudio']) &&  $BFXML_VARS['saveaudio']== 1){
		// get content id from xml vars
		$contentid = $BFXML_VARS['recordaudio']+0;

		if ($contentid) {
			$specialtask->setData("contentid",  $contentid);
			
			if ($specialtask->getData("origin") == "cisco") {
				// TODO create shared code with inboundmessage for saving the message, parts, group...

				$name = $specialtask->getData('name');
				if (!$name)
					$name = "EasyCall - " . date("M j, Y g:i a");
				$namewithlanguage = $name;
				
				$languagecode = "en"; // English is the default, always the first to record
				
				$count = $specialtask->getData('count');
				if ($count) {
					$language = $specialtask->getData('language'.$count);
					$languagecode = QuickQuery("select code from language where name = ?", false, array($language));
					
					$name = $specialtask->getData('name');
					if (!$name) {
						$name = "EasyCall - $language " . date("M j, Y g:i a");
					} else {
						$namewithlanguage = $name . " - $language";
					}
				}
				$count++;
				$specialtask->setData("count", $count);
				$specialtask->update();

				
				if (isset($_SESSION['messagegroupid'])) {
					$messagegroupid = $_SESSION['messagegroupid'];
				} else {
					$messagegroup = new MessageGroup();
					$messagegroup->userid = $_SESSION['userid'];
					$messagegroup->name = $name;
					$messagegroup->description = "";
					$messagegroup->modified = date("Y-m-d G:i:s");
					$messagegroup->create();
					$messagegroupid = $messagegroup->id;
					$_SESSION['messagegroupid'] = $messagegroupid;
				}

	if ($messagegroupid) {
		$audioFile = new AudioFile();
		$audioFile->userid = $_SESSION['userid'];
		$audioFile->name = $namewithlanguage;
		$audioFile->description = "";
		$audioFile->contentid = $contentid;
		$audioFile->recordDate = date("Y-m-d G:i:s");
		$audioFile->messagegroupid = $messagegroupid;

		$message = new Message();
		$message->messagegroupid = $messagegroupid;
		$message->userid = $_SESSION['userid'];
		$message->type = "phone";
		$message->subtype = "voice";
		$message->autotranslate = "none";
		$message->languagecode = $languagecode;
		$message->name = $namewithlanguage;
		$message->description = "";

		$messagePart = new MessagePart();
		$messagePart->type = "A";

		// now commit to database
		$audioFile->create();
		$audiofileid = $audioFile->id;
		//error_log("audiofileid: ".$audiofileid);
		if ($audiofileid) {
			$message->create();
			$messageid = $message->id;
			//error_log("messageid: ".$messageid);
			if ($messageid) {
				$messagePart->messageid = $messageid;
				$messagePart->audiofileid = $audiofileid;
				$messagePart->sequence = 0;
				$messagePart->create();
				
				$messagegroup = new MessageGroup($messagegroupid);
				$messagegroup->modified = date("Y-m-d G:i:s");
				$messagegroup->update();

				//return true; // NOTE differs from inbound
			} // end message
		} // end audiofile
	} // end messagegroupid
				
	
			} // end if cisco
		} else {
			$error = 1;
			$specialtask->setData("error",  "saveerror");
		}

	} // end save audio message
	
	// if not a result request, ask listener to record a message
	if ($REQUEST_TYPE != "result") {
		$specialtask->setData("progress", "Recording");
		$specialtask->update();

		$currlang = "English";
		
		// if cisco
		if (($specialtask->getData("origin") == "cisco")) {
			$count = $specialtask->getData("count");
			$langnum = "language" . $count;
			$currlang = $specialtask->getData($langnum);
		}
		
		if ($currlang !== false) {
		?>
		<voice>
			<message name="record">
				<field name="recordaudio" type="record" max="300">
					<prompt>
						<?
							if ($currlang != "Default") {
								?>
									<audio cmid="file://prompts/NowRecordInLanguage.wav" />
									<tts gender="female" language="english"><?=escapehtml($currlang)?></tts>
								<?
							}
						?>
						<audio cmid="file://prompts/Record.wav" />
					</prompt>
				</field>
				<goto message="confirm" />
			</message>

			<message name="confirm">
				<setvar name="playedprompt" value="no" />
				<field name="saveaudio" type="menu" timeout="5000" sticky="true">
					<prompt repeat="2">
						<if name="playedprompt" value="no">
							<then>
								<audio cmid="file://prompts/PlayBack.wav" />
								<audio var="recordaudio" />
							</then>
							<else />
						</if>
						<audio cmid="file://prompts/inbound/SaveMessage2.wav" />
						<setvar name="playedprompt" value="yes" />
					</prompt>

					<choice digits="1">
						<uploadaudio name="recordaudio" />
					</choice>

					<choice digits="2">
						<goto message="confirm" />
					</choice>

					<choice digits="3">
						<goto message="record" />
					</choice>

					<default>
						<audio cmid="file://prompts/ImSorry.wav" />
					</default>
					<timeout>
						<audio cmid="file://prompts/GoodBye.wav" />
						<hangup />
					</timeout>
				</field>
			</message>
		</voice>
		<?
		
		return;
		}
	}

	// if they hung up and no audio was saved then set an error
	if ($REQUEST_TYPE == "result" && !$specialtask->getData("contentid"))
		$specialtask->setData("error", "callended");

	// task is all done. make it so and update the task
	$specialtask->setData('progress', 'Done');
	$specialtask->status = "done";
	$specialtask->update();

	// if there was an error. notify the user
	if($error) {
		?>
		<voice>
			<message>
				<audio cmid="file://prompts/inbound/ExitWithError.wav" />
				<hangup />
			</message>
		</voice>
		<?

	// no errors just end the call normaly
	} else {
		forwardToPage("easycall3.php");
	}

}

?>
