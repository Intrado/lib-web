<?
require_once("../inc/utils.inc.php");
require_once("../obj/SpecialTask.obj.php");
require_once("../obj/Content.obj.php");

$error = 0;
$specialtask = new specialtask($_SESSION['specialtaskid']);
$phone = $specialtask->getData('phonenumber');

if($REQUEST_TYPE == "new") {
		$ERROR .= "Got new when wanted continue";
} else {
	// audio was saved, we should store the content id
	if(isset($BFXML_VARS['saveaudio']) &&  $BFXML_VARS['saveaudio']== 1){
		// get content id from xml vars
		$contentid = $BFXML_VARS['recordaudio']+0;
		if($contentid) {
			$specialtask->setData("contentid",  $contentid);
		} else {
			$error = 1;
			$specialtask->setData("error",  "saveerror");
		}

	// if no save audio and not a result request, ask listener to record a message
	} else if($REQUEST_TYPE != "result"){
		$specialtask->setData("progress", "Recording");
		$specialtask->update();
		?>
		<voice>
			<message name="record">
				<field name="recordaudio" type="record" max="300">
					<prompt>
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
