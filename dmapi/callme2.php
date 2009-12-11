<?
include_once("../inc/utils.inc.php");
include_once("../obj/Job.obj.php");
include_once("../obj/User.obj.php");
include_once("../obj/SpecialTask.obj.php");
include_once("../obj/Message.obj.php");
include_once("../obj/MessagePart.obj.php");
include_once("../obj/AudioFile.obj.php");

$specialtask = new specialtask($_SESSION['specialtaskid']);
$phone = $specialtask->getData('phonenumber');
//$callerid = $specialtask->getData('callerid');
$error = 0;
if($REQUEST_TYPE == "new") {
		$ERROR .= "Got new when wanted continue";
} else{

	if(isset($BFXML_VARS['saveaudio']) && $BFXML_VARS['saveaudio'] == 1){
		$contentid = $BFXML_VARS['recordaudio']+0;
		if($contentid > 0){
			$user = new user($specialtask->getData('userid'));
			$audio = new AudioFile();
			$audio->userid =$specialtask->getData('userid');
			$name = $specialtask->getData('name') . " - " . $specialtask->getData('count');
			if(QuickQuery("select count(*) from audiofile where userid = '$user->id' and deleted = 0
						and name = '" . DBSafe($name) ."'"))
				$name = $name ." - ". date("M j, Y G:i:s");

			$audio->name = $name;
			$audio->contentid = $contentid;
			$audio->recorddate = date("Y-m-d G:i:s");
			$audio->update();

			$BFXML_VARS['audiofileid'] = $audio->id;
			$BFXML_VARS['saveaudio']=NULL;
			$BFXML_VARS['recordaudio']=NULL;

			//then make a message if not from audio
			$origin = $specialtask->getData('origin');
			if($origin != "audio"){

				$message = new Message();
				$messagename = $specialtask->getData('name') . " - " . $specialtask->getData('count');
				if(QuickQuery("Select count(*) from message where userid=$user->id and deleted = '0'
								and name = '" . DBSafe($messagename) . "'"))
					$messagename = $messagename . " - " . date("M j, Y G:i:s");
				$message->name = $messagename;
				$message->description = "Call Me - " . $messagename;
				$message->type = "phone";
				$message->userid = $user->id;
				$message->create();

				$part = new MessagePart();
				$part->messageid = $message->id;
				$part->type = "A";
				$part->audiofileid = $audio->id;
				$part->sequence = 0;

				$part->create();

				$count = $specialtask->getData('count');
				$message = $message->id;
				$messnum = "message" . $count;
				$count++;
				$specialtask->setData('count', $count);
				$specialtask->setData($messnum, $message);
				$specialtask->update();
			} else {
				$count = $specialtask->getData('count');
				$message = $audio->id;
				$messnum = "message" . $count;
				$count++;
				$specialtask->setData('count', $count);
				$specialtask->setData($messnum, $message);
				$specialtask->update();
			}
		} else {
			$error = 1;
		}
	}

	if(!$error && ((isset($BFXML_VARS['recordnext']) &&  $BFXML_VARS['recordnext'] == 1) || (isset($BFXML_VARS['continue']) && $BFXML_VARS['continue']==1))) {
		$count = $specialtask->getData('count');
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
						<audio cmid="file://prompts/Saved.wav" />
						<audio cmid="file://prompts/GoodBye.wav" />
						<hangup />
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
		$specialtask->setData('progress', 'Done');
		$specialtask->status = "done";
		$specialtask->update();
		forwardToPage("callme3.php");
	}
}
?>
