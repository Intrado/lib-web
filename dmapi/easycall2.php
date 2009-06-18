<?
include_once("../inc/utils.inc.php");
include_once("../obj/Job.obj.php");
include_once("../obj/User.obj.php");
include_once("../obj/SpecialTask.obj.php");
include_once("../obj/Message.obj.php");
include_once("../obj/MessagePart.obj.php");
include_once("../obj/AudioFile.obj.php");
$error = 0;
$specialtask = new specialtask($_SESSION['specialtaskid']);
$phone = $specialtask->getData('phonenumber');
//$callerid = $specialtask->getData('callerid');
if($REQUEST_TYPE == "new") {
		$ERROR .= "Got new when wanted continue";
} else {
	if(isset($BFXML_VARS['saveaudio']) &&  $BFXML_VARS['saveaudio']== 1){
		$contentid = $BFXML_VARS['recordaudio']+0;
		if($contentid > 0){

			$user = new user($specialtask->getData('userid'));
			$audio = new AudioFile();
			$audio->userid =$specialtask->getData('userid');
			$name = $specialtask->getData('name') . " - " . $specialtask->getData('currlang');
			if(QuickQuery("select count(*) from audiofile where userid = '$user->id' and deleted = 0
						and name = '" . DBSafe($name) ."'"))
				$name = $name ."-". date("M j, Y G:i:s");

			$audio->name = $name;
			$audio->contentid = $contentid;
			$audio->recorddate = date("Y-m-d G:i:s");
			if ($specialtask->getData("origin") === "jobwizard")
				$audio->deleted = true;
			$audio->update();

			$BFXML_VARS['audiofileid'] = $audio->id;
			$BFXML_VARS['saveaudio']=NULL;
			$BFXML_VARS['recordaudio']=NULL;

			$message = new Message();
			if ($specialtask->getData("origin") == "cisco") {
				$messagename = $specialtask->getData('name') . " - " . $specialtask->getData('currlang');
			} else {
				$messagename = $specialtask->getData('name');
			}
			if(QuickQuery("Select count(*) from message where userid=? and not deleted and name =?", false, array($user->id, $messagename))) {
				$messagename = $messagename . " - " . date("M j, Y G:i:s");
			}
			$message->name = $messagename;
			$message->description = $messagename;
			$message->type = "phone";
			$message->userid = $user->id;
			if ($specialtask->getData("origin") === "jobwizard")
				$message->deleted = true;
			$message->create();

			$part = new MessagePart();
			$part->messageid = $message->id;
			$part->type = "A";
			$part->audiofileid = $audio->id;
			$part->sequence = 0;
			$part->create();

			$count = $specialtask->getData('count');
			$messnum = "message" . $count;
			$messageid = $message->id;
			$specialtask->setData($messnum, $messageid);
			$count++;
			$specialtask->setData("count", $count);
			$specialtask->update();
		} else {
			$error = 1;
		}
	}
	if($REQUEST_TYPE == "result") {

		$count = $specialtask->getData("count");
		$totalamount = $specialtask->getData("totalamount");
		if($error){
			$specialtask->status = "done";
			$specialtask->setData("progress", "Call Ended");
			$specialtask->setData("error",  "saveerror");
			$specialtask->update();
			forwardToPage("easycall3.php");
		}else if($count < $totalamount) {
			$specialtask->status = "done";
			$specialtask->setData("progress", "Call Ended");
			$specialtask->setData("error", "messagesremain");
			$specialtask->update();
			forwardToPage("easycall3.php");
		} else {
			$specialtask->status = "done";
			$specialtask->setData("progress", "Done");
			$specialtask->update();
			forwardToPage("easycall3.php");
		}
	} else {
		$count = $specialtask->getData("count");
		$langnum = "language" . $count;
		$currlang = $specialtask->getData($langnum);
		if($currlang) {
			$specialtask->setData("progress", "Recording");
			$specialtask->setData('currlang', $currlang);
			$specialtask->update();
			?>

			<voice>

				<message name="record">
					<field name="recordaudio" type="record" max="300">
						<prompt>
						<?
							if($currlang != "Default"){
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
							<audio cmid="file://prompts/Saved.wav" />
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
		} else {
			$specialtask->status = "done";
			$specialtask->setData("progress", "Done");
			$specialtask->update();
			forwardToPage("easycall3.php");
		}
	}
}

?>
